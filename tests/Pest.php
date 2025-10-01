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

    beforeEach(function () use (&$___phenixTestStartTimes): void {
        // Pest current test id reference
        $name = test()->getFile() . '::' . test()->getName();
        $___phenixTestStartTimes[$name] = microtime(true);
    });

    afterEach(function () use (&$___phenixTestStartTimes, &$___phenixTestDurations): void {
        $name = test()->getFile() . '::' . test()->getName();
        $start = $___phenixTestStartTimes[$name] ?? microtime(true);
        $duration = microtime(true) - $start;
        $___phenixTestDurations[$name] = [
            'duration_sec' => round($duration, 4),
            'memory_peak' => memory_get_peak_usage(true),
        ];
    });

    afterAll(function () use (&$___phenixTestDurations): void {
        $dir = __DIR__ . '/../build/diagnostics';
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        // 1. Persist per-test durations
        $durationFile = $dir . '/test_durations.json';
        @file_put_contents($durationFile, json_encode($___phenixTestDurations, JSON_PRETTY_PRINT));

        // 2. Capture child processes (potential worker pool processes)
        $parentPid = getmypid();
        $psOutput = [];
        @exec('ps -o pid,ppid,etime,command -ax', $psOutput);
        $children = array_values(array_filter($psOutput, static function (string $line) use ($parentPid): bool {
            return preg_match('/^\s*(\d+)\s+' . $parentPid . '\s+/', $line) === 1;
        }));
        @file_put_contents($dir . '/child_processes.log', implode(PHP_EOL, $children));

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
            file_put_contents('php://stderr', "[PHENIX_DIAG] Detected " . count($children) . " child process(es) still alive after tests. See build/diagnostics/child_processes.log\n");
        }
    });
}
