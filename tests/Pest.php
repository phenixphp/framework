<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Unit');
uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// --------------------------------------------------------------------------
// Diagnostics (opt-in via PHENIX_DIAG=1)
// --------------------------------------------------------------------------
// These hooks gather timing, process, and queue driver status information to
// help detect hanging async workers or orphaned processes in CI. They do NOT
// modify framework core behavior and only run when PHENIX_DIAG env var is set.

if (getenv('PHENIX_DIAG') === '1') {
    /** @var array<string, float> $___phenixTestStartTimes */
    $___phenixTestStartTimes = [];
    /** @var array<string, array<string, mixed>> $___phenixTestDurations */
    $___phenixTestDurations = [];
    $___phenixDiagDir = __DIR__ . '/../build/diagnostics';
    $___phenixFlushInterval = (int) (getenv('PHENIX_DIAG_FLUSH_INTERVAL') ?: 25);
    $___phenixTestCounter = 0;
    $___phenixBaselineChildren = [];
    $___phenixPsCmd = 'ps -o pid,ppid,etime,command -ax';
    $___phenixPsBaselineCmd = 'ps -o pid,ppid,command -ax';
    if (! defined('PHENIX_CHILD_PROC_REGEX')) {
        define('PHENIX_CHILD_PROC_REGEX', '/^\s*(\d+)\s+');
    }
    $___phenixStderr = 'php://stderr';

    // Ensure diagnostics directory exists as early as possible so artifact upload finds it even on early cancel.
    if (! is_dir($___phenixDiagDir)) {
        @mkdir($___phenixDiagDir, 0777, true);
    }

    // Capture baseline child processes (if any) for later diffing.
    $psOutput = [];
    @exec($___phenixPsBaselineCmd, $psOutput);
    $parentPid = getmypid();
    $___phenixBaselineChildren = array_values(array_filter($psOutput, static function (string $line) use ($parentPid): bool {
        return preg_match('/^\s*(\d+)\s+' . $parentPid . '\s+/', $line) === 1;
    }));
    @file_put_contents($___phenixDiagDir . '/baseline_children.log', implode(PHP_EOL, $___phenixBaselineChildren));

    beforeEach(function () use (&$___phenixTestStartTimes): void {
        // Pest current test id reference
        $name = test()->getFile() . '::' . test()->getName();
        $___phenixTestStartTimes[$name] = microtime(true);
    });

    afterEach(function () use (&$___phenixTestStartTimes, &$___phenixTestDurations, &$___phenixTestCounter, $___phenixFlushInterval, $___phenixDiagDir, $parentPid, &$___phenixBaselineChildren, $___phenixPsCmd, $___phenixStderr): void {
        $name = test()->getFile() . '::' . test()->getName();
        $start = $___phenixTestStartTimes[$name] ?? microtime(true);
        $duration = microtime(true) - $start;
        $___phenixTestDurations[$name] = [
            'duration_sec' => round($duration, 4),
            'memory_peak' => memory_get_peak_usage(true),
        ];

        $___phenixTestCounter++;

        // Periodic snapshot flush to disk for early artifact availability & heartbeat
        if ($___phenixTestCounter % $___phenixFlushInterval === 0) {
            $snapshotFile = $___phenixDiagDir . '/snapshot_' . $___phenixTestCounter . '.json';
            @file_put_contents($snapshotFile, json_encode([
                'count' => $___phenixTestCounter,
                'time' => date(DATE_ATOM),
                'durations_collected' => count($___phenixTestDurations),
                'peak_memory' => memory_get_peak_usage(true),
            ], JSON_PRETTY_PRINT));

            // Child process diff at snapshot
            $ps = [];
            @exec($___phenixPsCmd, $ps);
            $currentChildren = array_values(array_filter($ps, static function (string $line) use ($parentPid): bool {
                return preg_match(PHENIX_CHILD_PROC_REGEX . $parentPid . '\s+/', $line) === 1;
            }));
            @file_put_contents($___phenixDiagDir . '/children_latest.log', implode(PHP_EOL, $currentChildren));

            // Diff new child lines not in baseline
            $new = array_diff($currentChildren, $___phenixBaselineChildren);
            if (! empty($new)) {
                @file_put_contents($___phenixDiagDir . '/children_new.log', implode(PHP_EOL, $new));
            }

            // Heartbeat to stderr (keeps CI step from thinking it's idle if something else becomes quiet)
            file_put_contents($___phenixStderr, sprintf("[PHENIX_DIAG] heartbeat tests=%d peak_mem=%.2fMB children=%d new_children=%d\n",
                $___phenixTestCounter,
                memory_get_peak_usage(true) / 1024 / 1024,
                count($currentChildren),
                isset($new) ? count($new) : 0
            ));
        }
    });

    afterAll(function () use (&$___phenixTestDurations, $___phenixDiagDir, $parentPid, $___phenixBaselineChildren, $___phenixPsCmd, $___phenixStderr): void {
        $dir = $___phenixDiagDir;

        // 1. Persist per-test durations
        $durationFile = $dir . '/test_durations.json';
        @file_put_contents($durationFile, json_encode($___phenixTestDurations, JSON_PRETTY_PRINT));

        // 2. Capture child processes (potential worker pool processes)
        $psOutput = [];
        @exec($___phenixPsCmd, $psOutput);
        $children = array_values(array_filter($psOutput, static function (string $line) use ($parentPid): bool {
            return preg_match(PHENIX_CHILD_PROC_REGEX . $parentPid . '\s+/', $line) === 1;
        }));
        @file_put_contents($dir . '/child_processes.log', implode(PHP_EOL, $children));
        $newChildren = array_diff($children, $___phenixBaselineChildren);
        if (! empty($newChildren)) {
            @file_put_contents($dir . '/child_processes_new.log', implode(PHP_EOL, $newChildren));
        }

        // 3. Queue driver status (if container & facade available)
        $queueStatus = null;

        try {
            if (class_exists(Phenix\Facades\Queue::class)) {
                /** @var mixed $driver */
                $driver = Phenix\Facades\Queue::driver();
                $queueStatus = [
                    'driver_class' => is_object($driver) ? get_class($driver) : gettype($driver),
                ];
                if (is_object($driver) && method_exists($driver, 'getProcessorStatus')) {
                    try {
                        $queueStatus['processor'] = $driver->getProcessorStatus();
                    } catch (\Throwable $e) {
                        $queueStatus['processor_error'] = $e->getMessage();
                    }
                }
            }
        } catch (\Throwable $e) {
            $queueStatus = ['error' => $e->getMessage()];
        }
        @file_put_contents($dir . '/queue_status.json', json_encode($queueStatus, JSON_PRETTY_PRINT));

        // 4. Memory summary
        $mem = [
            'peak_memory_bytes' => memory_get_peak_usage(true),
            'time' => date(DATE_ATOM),
        ];
        @file_put_contents($dir . '/memory.json', json_encode($mem, JSON_PRETTY_PRINT));

        // 5. Highlight slow tests ( > 5s ) in stderr for quick scan
        $slow = array_filter($___phenixTestDurations, static fn ($d) => ($d['duration_sec'] ?? 0) > 5);
        if (! empty($slow)) {
            $lines = ["[PHENIX_DIAG] Slow tests (>5s):"]; // phpcs:ignore
            foreach ($slow as $testName => $data) {
                $lines[] = sprintf('%s — %.2fs', $testName, $data['duration_sec']);
            }
            file_put_contents('php://stderr', implode(PHP_EOL, $lines) . PHP_EOL);
        }

        // 6. Flag if child processes still present (indication of leaked workers)
        if (! empty($children)) {
            file_put_contents($___phenixStderr, "[PHENIX_DIAG] Detected " . count($children) . " child process(es) still alive after tests. See build/diagnostics/child_processes.log\n");
        }
    });

    // Fallback: if process is killed before afterAll runs, still dump minimal snapshot.
    register_shutdown_function(function () use (&$___phenixTestDurations, $___phenixDiagDir, $parentPid, $___phenixPsCmd): void {
        if (! is_dir($___phenixDiagDir)) {
            @mkdir($___phenixDiagDir, 0777, true);
        }
        $summary = [
            'tests_collected' => count($___phenixTestDurations),
            'time' => date(DATE_ATOM),
            'peak_memory' => memory_get_peak_usage(true),
            'shutdown' => true,
        ];
        @file_put_contents($___phenixDiagDir . '/shutdown_summary.json', json_encode($summary, JSON_PRETTY_PRINT));

        $psOutput = [];
        @exec($___phenixPsCmd, $psOutput);
        $children = array_values(array_filter($psOutput, static function (string $line) use ($parentPid): bool {
            return preg_match(PHENIX_CHILD_PROC_REGEX . $parentPid . '\s+/', $line) === 1;
        }));
        @file_put_contents($___phenixDiagDir . '/shutdown_children.log', implode(PHP_EOL, $children));
    });
}
