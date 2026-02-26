<?php

declare(strict_types=1);

use Phenix\Database\TransactionContext;
use Phenix\Facades\DB;
use Tests\Feature\Database\Models\SimpleUser as User;

beforeEach(function (): void {
    DB::connection('sqlite')->unprepared("DROP TABLE IF EXISTS users");
    DB::connection('sqlite')->unprepared("DROP TABLE IF EXISTS logs");

    DB::connection('sqlite')->unprepared("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    DB::connection('sqlite')->unprepared("
        CREATE TABLE logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
});

it('executes nested transactions with savepoints', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        // Level 0: Main transaction
        DB::from('users')->insert(['name' => 'John Doe', 'email' => 'john@example.com']);

        expect(TransactionContext::depth())->toBe(1);

        // Level 1: Nested transaction (savepoint)
        DB::transaction(function (): void {
            DB::from('logs')->insert(['user_id' => 1, 'action' => 'user_created']);

            expect(TransactionContext::depth())->toBe(2);
        });

        expect(TransactionContext::depth())->toBe(1);
    });

    $users = DB::connection('sqlite')->from('users')->get();
    $logs = DB::connection('sqlite')->from('logs')->get();

    expect($users->count())->toBe(1);
    expect($users[0]['name'])->toBe('John Doe');
    expect($logs->count())->toBe(1);
    expect($logs[0]['action'])->toBe('user_created');
});

it('rolls back nested transaction only on exception in nested block', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        DB::from('users')->insert(['name' => 'John Doe', 'email' => 'john@example.com']);

        try {
            DB::transaction(function (): void {
                DB::from('logs')->insert(['user_id' => 1, 'action' => 'test']);

                // Force an error
                throw new Exception('Nested transaction error');
            });
        } catch (Exception $e) {
            // Catch the exception to continue with parent transaction
        }

        // User should still be inserted after parent commits
    });

    $users = DB::connection('sqlite')->from('users')->get();
    $logs = DB::connection('sqlite')->from('logs')->get();

    expect($users->count())->toBe(1);
    expect($users[0]['name'])->toBe('John Doe');
    expect($logs->count())->toBe(0); // Log should be rolled back
});

it('supports multiple levels of nested transactions', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        DB::from('users')->insert(['name' => 'Level 0', 'email' => 'level0@example.com']);

        expect(TransactionContext::depth())->toBe(1);

        DB::transaction(function (): void {
            DB::from('logs')->insert(['user_id' => 1, 'action' => 'Level 1']);

            expect(TransactionContext::depth())->toBe(2);

            DB::transaction(function (): void {
                DB::from('logs')->insert(['user_id' => 1, 'action' => 'Level 2']);

                expect(TransactionContext::depth())->toBe(3);
            });

            expect(TransactionContext::depth())->toBe(2);
        });

        expect(TransactionContext::depth())->toBe(1);
    });

    $users = DB::connection('sqlite')->from('users')->get();
    $logs = DB::connection('sqlite')->from('logs')->get();

    expect($users->count())->toBe(1);
    expect($logs->count())->toBe(2);
    expect($logs[0]['action'])->toBe('Level 1');
    expect($logs[1]['action'])->toBe('Level 2');
});

it('rolls back specific nested level without affecting others', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        DB::from('users')->insert(['name' => 'John', 'email' => 'john@example.com']);

        DB::transaction(function (): void {
            DB::from('logs')->insert(['user_id' => 1, 'action' => 'First log']);

            try {
                DB::transaction(function (): void {
                    DB::from('logs')->insert(['user_id' => 1, 'action' => 'Second log']);

                    throw new Exception('Error in level 2');
                });
            } catch (Exception $e) {
                // Ignore error in nested level
            }

            // First log should persist
            DB::from('logs')->insert(['user_id' => 1, 'action' => 'Third log']);
        });
    });

    $users = DB::connection('sqlite')->from('users')->get();
    $logs = DB::connection('sqlite')->from('logs')->get();

    expect($users->count())->toBe(1);
    expect($logs->count())->toBe(2);
    expect($logs[0]['action'])->toBe('First log');
    expect($logs[1]['action'])->toBe('Third log');
});

it('clears transaction context after top-level commit', function (): void {
    expect(TransactionContext::depth())->toBe(0);

    DB::connection('sqlite')->transaction(function (): void {
        expect(TransactionContext::depth())->toBe(1);
        DB::from('users')->insert(['name' => 'Test', 'email' => 'test@example.com']);
    });

    expect(TransactionContext::depth())->toBe(0);
});

it('works with models in nested transactions', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        User::on('sqlite')->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        DB::transaction(function (): void {
            DB::from('logs')->insert(['user_id' => 1, 'action' => 'model_created']);
        });
    });

    $users = DB::connection('sqlite')->from('users')->get();
    $logs = DB::connection('sqlite')->from('logs')->get();

    expect($users->count())->toBe(1);
    expect($logs->count())->toBe(1);
});

it('handles exception in parent transaction after nested success', function (): void {
    try {
        DB::connection('sqlite')->transaction(function (): void {
            DB::from('users')->insert(['name' => 'John', 'email' => 'john@example.com']);

            DB::transaction(function (): void {
                DB::from('logs')->insert(['user_id' => 1, 'action' => 'test']);
            });

            // Throw error after nested transaction succeeded
            throw new Exception('Parent error');
        });
    } catch (Exception $e) {
        // Expected
    }

    $users = DB::connection('sqlite')->from('users')->get();
    $logs = DB::connection('sqlite')->from('logs')->get();

    // Everything should be rolled back
    expect($users->count())->toBe(0);
    expect($logs->count())->toBe(0);
});

it('maintains separate update operations in nested transactions', function (): void {
    // Insert initial data
    DB::connection('sqlite')->from('users')->insert(['name' => 'Original', 'email' => 'original@example.com']);

    DB::connection('sqlite')->transaction(function (): void {
        DB::from('users')->whereEqual('id', 1)->update(['name' => 'Updated Level 0']);

        DB::transaction(function (): void {
            DB::from('users')->whereEqual('id', 1)->update(['name' => 'Updated Level 1']);

            try {
                DB::transaction(function (): void {
                    DB::from('users')->whereEqual('id', 1)->update(['name' => 'Updated Level 2']);

                    throw new Exception('Rollback level 2');
                });
            } catch (Exception $e) {
                // Ignore
            }
        });
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users->count())->toBe(1);
    expect($users[0]['name'])->toBe('Updated Level 1'); // Level 2 rolled back
});

it('correctly reports transaction depth throughout nested calls', function (): void {
    $depths = [];

    DB::connection('sqlite')->transaction(function () use (&$depths): void {
        $depths[] = TransactionContext::depth(); // Should be 1

        DB::transaction(function () use (&$depths): void {
            $depths[] = TransactionContext::depth(); // Should be 2

            DB::transaction(function () use (&$depths): void {
                $depths[] = TransactionContext::depth(); // Should be 3
            });

            $depths[] = TransactionContext::depth(); // Should be 2
        });

        $depths[] = TransactionContext::depth(); // Should be 1
    });

    $depths[] = TransactionContext::depth(); // Should be 0

    expect($depths)->toBe([1, 2, 3, 2, 1, 0]);
});

it('can access current transaction node information', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        $node = TransactionContext::getCurrentNode();

        expect($node)->not()->toBeNull();
        expect($node->depth)->toBe(0);
        expect($node->isRoot())->toBeTrue();
        expect($node->hasSavepoint())->toBeFalse();

        DB::transaction(function (): void {
            $node = TransactionContext::getCurrentNode();

            expect($node)->not()->toBeNull();
            expect($node->depth)->toBe(1);
            expect($node->isRoot())->toBeFalse();
            expect($node->hasSavepoint())->toBeTrue();
        });
    });
});

it('handles complex nested scenario with multiple branches', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        DB::from('users')->insert(['name' => 'User1', 'email' => 'user1@example.com']);

        // Branch 1: Success
        DB::transaction(function (): void {
            DB::from('logs')->insert(['user_id' => 1, 'action' => 'branch1']);
        });

        // Branch 2: Failure
        try {
            DB::transaction(function (): void {
                DB::from('logs')->insert(['user_id' => 1, 'action' => 'branch2']);

                throw new Exception('Branch 2 failed');
            });
        } catch (Exception $e) {
            // Ignore
        }

        // Branch 3: Success
        DB::transaction(function (): void {
            DB::from('logs')->insert(['user_id' => 1, 'action' => 'branch3']);
        });
    });

    $logs = DB::connection('sqlite')->from('logs')->get();

    expect($logs->count())->toBe(2);
    expect($logs[0]['action'])->toBe('branch1');
    expect($logs[1]['action'])->toBe('branch3');
});
