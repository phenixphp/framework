# Phenix Queue & Test Runtime Diagnostics Memo

Date: 2025-10-01
Branch: bugfix/tracking-zombie-processes
Related PR: #76 (Diagnostics support)

## 1. Context & Problem Statement
CI jobs (GitHub Actions) intermittently time out after long idle periods (up to the 6h max) when running the full Pest test suite. The job eventually gets cancelled, with no failing assertions. Hypothesis: leaked child processes (worker pool) or an async event loop deadlock preventing test completion. Need definitive instrumentation, containment, and a path to a safer parallel queue lifecycle.

## 2. Platform & Runtime Stack
- Language: PHP 8.2
- Async runtime: Amp v3 (Futures, Fibers, Interval, WorkerPool)
- Test framework: PestPHP
- Queue subsystem: `ParallelQueue`, plus `DatabaseQueue` & `RedisQueue` (less implicated)
- Worker execution: `Amp\Parallel\Worker\WorkerPool` spawned subprocesses executing `process-runner.php` & `task-runner.php`

## 3. Initial Findings
- Original `ParallelQueue` uses an `Interval` whose callback schedules a batch of tasks, submits to `WorkerPool`, and calls `future->await()` synchronously on an async wrapper future.
- Potential risks:
  1. Blocking inside an `Interval` callback could stall the scheduler if nested awaits conflict with event loop fairness.
  2. Running tasks tracked in `$runningTasks` cleaned only when fully finished; partial completion mid-await not observable until loop iteration returns.
  3. No explicit shutdown of `WorkerPool` at test teardown.
  4. If a task never resolves (timeout edge, hung I/O, or mis-handled cancellation), the loop remains alive indefinitely.
- Redis/DB queue drivers not exhibiting same pattern of lingering workers.

## 4. Experiment Timeline
| Step | Action | Result |
|------|--------|--------|
| A | Added initial diagnostics (per-test timing, child processes list at end) | No artifact when job cancelled early (dir missing) |
| B | Refactored ParallelQueue to non-blocking async fire-and-forget | Massive fiber deadlocks (347 test failures) → Reverted |
| C | Added opt-in diagnostics via `PHENIX_DIAG=1` | Stable, but still lacked early flush |
| D | Introduced early directory creation & shutdown hook | `shutdown_summary.json` captured but tests collected = 0 in timeout case |
| E | Added periodic snapshots & heartbeat, progress file, baseline & differential child process capture | Gave visibility into early hangs |
| F | Implemented `ParallelQueue::drain()` + diagnostic afterAll drain attempt | Provides forced attempt at worker completion |

## 5. Key Artifacts & Interpretation
- `shutdown_summary.json` with `tests_collected: 0`: Process terminated before any `afterEach` executed → early stall or external cancel.
- `shutdown_children.log`: Lists multiple `process-runner.php` processes (Amp workers) with elapsed times > 1 minute, confirming worker pool spawned and remained.
- `baseline_children.log`: Only contains the ps sampling process, establishing baseline (no pre-existing stray workers at start).
- Absence of `snapshot_*.json`: Confirms no periodic flush triggered (i.e., suite never progressed enough tests or stalled pre-first flush threshold).

## 6. Root Cause Hypotheses (Ranked)
1. HANGING WORKER TASKS: A submitted task never returns (network I/O, infinite loop), causing `future->await()` in interval callback to block further progress & preventing test completion.
2. DEADLOCK / FIBER STALL: Blocking await inside `Interval` leads to starvation or unresolved suspension while other pending fibers wait on queue state.
3. MISSING SHUTDOWN HOOK FOR WORKERPOOL: Workers remain idle but alive; test process waits for event loop quiescence that never occurs because interval remains enabled or references persist.
4. RETRY LOOP WITH DELAY: `handleTaskFailure()` uses `delay($retryDelay)` synchronously; if repeated near the end of suite, could accumulate unresolved scheduling windows.
5. MEMORY LEAK LESS LIKELY: No evidence yet of OOM or high peak memory; `peak_memory_bytes` moderate in captured diagnostics.

## 7. Risk Surfaces in Current Code
| Area | Risk | Mitigation Direction |
|------|------|----------------------|
| Interval blocking | Single long-running batch blocks scheduling | Move to non-blocking pattern with guarded concurrency and result callbacks |
| Task retries | Synchronous `delay()` blocks fiber | Replace with scheduling a future timestamp and requeue without blocking loop |
| Worker lifecycle | No explicit drain/shutdown in tests | Provide `drain()` (added) + test harness cleanup |
| Diagnostics reliability | Artifacts missing on early cancel | Early dir creation + periodic snapshot (implemented) |
| Stalled detection | No watchdog | Add optional watchdog timer with inactivity threshold |

## 8. Implemented Mitigations
- Diagnostics enhancements: per-test timing, progress, snapshots, child process baseline & diff, shutdown fallback, queue drain attempt.
- `ParallelQueue::drain()` to assist teardown.
- Separation of instrumentation behind `PHENIX_DIAG` env variable to avoid production overhead.

## 9. Outstanding Gaps
1. No real watchdog timer yet (inactivity > N seconds → forced diagnostic dump).
2. `drain()` best-effort; does not forcibly terminate stuck workers.
3. Still using blocking pattern in production path (refactor attempt rolled back after regression).
4. No test simulating a deliberately hung task to validate watchdog instrumentation.
5. Retry logic still uses blocking `delay()` inside failure path.

## 10. Recommended Next Steps (Phased)
### Phase 1 (Low Risk)
- Add `PHENIX_DIAG_WATCHDOG_SEC` env-controlled timer: if no increment in `progress.json` timestamp within threshold, emit `watchdog.json` (with child processes snapshot, memory, queue sizes) and optionally trigger a soft `drain()`.
- Lower flush interval in CI to 5 for earlier artifact creation.

### Phase 2 (Controlled Refactor)
- Introduce non-blocking scheduler prototype behind feature flag `PHENIX_PARALLEL_NONBLOCK=1`:
  - Submit tasks.
  - Store futures + callback on completion.
  - Remove synchronous `$future->await()`.
  - Disable interval when queue empty & no running tasks.
- Add unit tests for concurrency boundaries, partial completion, and stuck future simulation.

### Phase 3 (Robust Worker Management)
- Provide an abstraction (e.g., `WorkerPoolManager`) tracking submitted task count, completed count, and offering explicit `shutdown()` (even if that just nets a final `await()` + releasing references).
- Investigate Amp worker pool API for graceful termination patterns (if future versions expose them) or implement sentinel tasks that trigger internal loop exit.

### Phase 4 (Retry & Backoff Improvements)
- Replace blocking `delay()` with schedule-based requeue (store `available_at` timestamp + non-blocking return to loop).
- Add exponential backoff & circuit breaker state (avoid infinite pressure on same failing task).

## 11. Observability Extensions (Optional)
- Lightweight trace file `queue_events.log` capturing events: submit, start, complete, retry schedule, drain invoked.
- Include hash of task class + attempt number for correlation.
- Memory watermark deltas per snapshot.

## 12. Success Criteria
| Metric | Target |
|--------|--------|
| CI timeouts due to idle | 0 occurrences over 10 consecutive runs |
| Orphan worker processes at shutdown | 0 (child diff = 0 beyond ps sampling) |
| Diagnostic artifact availability on early abort | ≥ 1 file present (snapshot or shutdown_summary) |
| Non-blocking scheduler regression tests | 100% pass including hung task simulation |

## 13. Rollback / Safety Strategy
- Maintain blocking implementation as default until feature flag path proves stable in at least 5 full CI runs.
- Keep diagnostics instrumentation optional to avoid noise in normal development.
- Provide a kill switch env var (`PHENIX_DIAG_DISABLE_DRAIN=1`) if `drain()` ever misbehaves.

## 14. Open Questions
1. Are there known long-running tasks in test fixtures that exceed default timeouts? (If yes, move them to explicit integration scenarios.)
2. Do we need per-task timeout overrides surfaced in diagnostics? (Could log each task's timeout vs actual duration.)
3. Should worker count be capped lower during tests to reduce surface of stuck processes (e.g., set max_concurrent=2 under `APP_ENV=test`)?

## 15. Immediate Action Checklist
- [ ] Add watchdog timer (Phase 1)
- [ ] Adjust CI workflow: create diagnostics dir + `PHENIX_DIAG_FLUSH_INTERVAL=5`
- [ ] Add hung task test (artificial infinite loop with small timeout to ensure state cleanup)
- [ ] Document `drain()` usage in README / developer docs

## 16. Appendix: Current Relevant Code Snippets (Abstracted)
- ParallelQueue (existing): Interval -> reserve tasks -> submit -> synchronous await results.
- Added: `drain($timeoutSeconds, $pollInterval)` loops while size or running tasks > 0.
- Diagnostics: `tests/Pest.php` gating all instrumentation by `PHENIX_DIAG`.

---
Maintainer Guidance: Keep this memo updated as phases complete; append changelog entries for each mitigation merged.
