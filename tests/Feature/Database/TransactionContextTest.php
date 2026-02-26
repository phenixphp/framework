<?php

declare(strict_types=1);

use Phenix\Database\TransactionContext;
use Phenix\Database\TransactionNode;
use Phenix\Facades\DB;

beforeEach(function (): void {
    DB::connection('sqlite')->unprepared("DROP TABLE IF EXISTS users");

    DB::connection('sqlite')->unprepared("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE
        )
    ");
});

it('cleans up context after transaction callback completes', function (): void {
    expect(TransactionContext::has())->toBeFalse();
    expect(TransactionContext::depth())->toBe(0);

    DB::connection('sqlite')->transaction(function (): void {
        $root = TransactionContext::getRoot();

        expect($root)->toBeInstanceOf(TransactionNode::class);
        expect($root->getSavepointIdentifier())->toBeNull();
        expect($root->isActive())->toBeTrue();
        expect(TransactionContext::has())->toBeTrue();
        expect(TransactionContext::depth())->toBe(1);
    });

    expect(TransactionContext::getRoot())->toBeNull();
    expect(TransactionContext::has())->toBeFalse();
    expect(TransactionContext::depth())->toBe(0);
});

it('cleans up context after transaction callback throws exception', function (): void {
    expect(TransactionContext::depth())->toBe(0);

    try {
        DB::connection('sqlite')->transaction(function (): void {
            expect(TransactionContext::depth())->toBe(1);

            throw new Exception('Test exception');
        });
    } catch (Exception $e) {
        // Expected
    }

    expect(TransactionContext::depth())->toBe(0);
    expect(TransactionContext::has())->toBeFalse();
});

it('maintains separate contexts for different connections', function (): void {
    // This test validates that each transaction maintains its own context
    DB::connection('sqlite')->transaction(function (): void {
        $node = TransactionContext::getCurrentNode();
        expect($node)->not()->toBeNull();

        DB::from('users')->insert(['name' => 'Test', 'email' => 'test@example.com']);
    });

    expect(TransactionContext::depth())->toBe(0);
});

it('properly cleans nested transaction contexts', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        expect(TransactionContext::depth())->toBe(1);

        DB::transaction(function (): void {
            expect(TransactionContext::depth())->toBe(2);

            DB::transaction(function (): void {
                expect(TransactionContext::depth())->toBe(3);
            });

            expect(TransactionContext::depth())->toBe(2);
        });

        expect(TransactionContext::depth())->toBe(1);
    });

    expect(TransactionContext::depth())->toBe(0);
});

it('cleans nested contexts even when inner transaction fails', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        expect(TransactionContext::depth())->toBe(1);

        try {
            DB::transaction(function (): void {
                expect(TransactionContext::depth())->toBe(2);

                DB::transaction(function (): void {
                    expect(TransactionContext::depth())->toBe(3);

                    throw new Exception('Inner error');
                });
            });
        } catch (Exception $e) {
            // Caught error
        }

        expect(TransactionContext::depth())->toBe(1);
    });

    expect(TransactionContext::depth())->toBe(0);
});

it('tracks transaction age correctly', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        $node = TransactionContext::getCurrentNode();

        expect($node)->not()->toBeNull();
        expect($node->age())->toBeGreaterThanOrEqual(0);
        expect($node->age())->toBeLessThan(1); // Should be very quick

        usleep(100000); // Sleep 100ms

        $newAge = $node->age();
        expect($newAge)->toBeGreaterThan(0.09); // At least 90ms
    });
});

it('can detect long-running transactions', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        usleep(100000); // Sleep 100ms

        $chain = TransactionContext::getChain();
        expect($chain)->toBeObject();

        $longRunning = $chain->getLongRunning(0.05); // 50ms threshold
        expect(count($longRunning))->toBe(1);
    });
});

it('identifies root transactions correctly', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        $node = TransactionContext::getCurrentNode();

        expect($node->isRoot())->toBeTrue();
        expect($node->depth)->toBe(0);

        DB::transaction(function (): void {
            $nestedNode = TransactionContext::getCurrentNode();

            expect($nestedNode->isRoot())->toBeFalse();
            expect($nestedNode->depth)->toBe(1);
        });
    });
});

it('maintains chain integrity through complex nesting', function (): void {
    $chainDepths = [];

    DB::connection('sqlite')->transaction(function () use (&$chainDepths): void {
        $chain = TransactionContext::getChain();
        $chainDepths[] = $chain->depth();

        DB::transaction(function () use (&$chainDepths): void {
            $chain = TransactionContext::getChain();
            $chainDepths[] = $chain->depth();

            try {
                DB::transaction(function () use (&$chainDepths): void {
                    $chain = TransactionContext::getChain();
                    $chainDepths[] = $chain->depth();

                    throw new Exception('Test');
                });
            } catch (Exception $e) {
                //
            }

            $chain = TransactionContext::getChain();
            $chainDepths[] = $chain->depth();
        });

        $chain = TransactionContext::getChain();
        $chainDepths[] = $chain->depth();
    });

    expect($chainDepths)->toBe([1, 2, 3, 2, 1]);
    expect(TransactionContext::depth())->toBe(0);
});

it('prevents context pollution between sequential transactions', function (): void {
    // First transaction
    DB::connection('sqlite')->transaction(function (): void {
        DB::from('users')->insert(['name' => 'User1', 'email' => 'user1@example.com']);
        expect(TransactionContext::depth())->toBe(1);
    });

    expect(TransactionContext::depth())->toBe(0);

    // Second transaction should have clean context
    DB::connection('sqlite')->transaction(function (): void {
        DB::from('users')->insert(['name' => 'User2', 'email' => 'user2@example.com']);
        expect(TransactionContext::depth())->toBe(1);

        $node = TransactionContext::getCurrentNode();
        expect($node->depth)->toBe(0); // Fresh top-level
    });

    expect(TransactionContext::depth())->toBe(0);

    $users = DB::connection('sqlite')->from('users')->get();
    expect($users->count())->toBe(2);
});

it('handles rapid sequential nested transactions', function (): void {
    for ($i = 0; $i < 5; $i++) {
        DB::connection('sqlite')->transaction(function () use ($i): void {
            DB::from('users')->insert([
                'name' => "User{$i}",
                'email' => "user{$i}@example.com",
            ]);

            DB::transaction(function () use ($i): void {
                // Nested operation
                $count = DB::from('users')->count();
                expect($count)->toBe($i + 1);
            });
        });

        expect(TransactionContext::depth())->toBe(0);
    }

    $users = DB::connection('sqlite')->from('users')->get();
    expect($users->count())->toBe(5);
});

it('correctly handles manual begin/commit with context cleanup', function (): void {
    expect(TransactionContext::depth())->toBe(0);

    $tm = DB::connection('sqlite')->beginTransaction();

    expect(TransactionContext::depth())->toBe(1);

    $tm->from('users')->insert(['name' => 'Test', 'email' => 'test@example.com']);

    $tm->commit();

    expect(TransactionContext::depth())->toBe(0);
});

it('correctly handles manual begin/rollback with context cleanup', function (): void {
    expect(TransactionContext::depth())->toBe(0);

    $tm = DB::connection('sqlite')->beginTransaction();

    expect(TransactionContext::depth())->toBe(1);

    $tm->from('users')->insert(['name' => 'Test', 'email' => 'test@example.com']);

    $tm->rollBack();

    expect(TransactionContext::depth())->toBe(0);

    $users = DB::connection('sqlite')->from('users')->get();
    expect($users->count())->toBe(0);
});

it('provides all chain nodes through getChain', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        DB::transaction(function (): void {
            DB::transaction(function (): void {
                $chain = TransactionContext::getChain();
                $all = $chain->all();

                expect(count($all))->toBe(3);
                expect($all[0]->depth)->toBe(0);
                expect($all[1]->depth)->toBe(1);
                expect($all[2]->depth)->toBe(2);
            });
        });
    });
});
