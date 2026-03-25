<?php

declare(strict_types=1);

use Phenix\Database\Constants\Order;
use Phenix\Database\Exceptions\QueryErrorException;
use Phenix\Database\TransactionManager;
use Phenix\Facades\DB;
use Phenix\Testing\Concerns\WithFaker;
use Tests\Feature\Database\Models\SimpleUser as User;

uses(WithFaker::class);

beforeEach(function (): void {
    DB::connection('sqlite')->unprepared("DROP TABLE IF EXISTS users");

    DB::connection('sqlite')->unprepared("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            password TEXT,
            created_at TEXT,
            updated_at TEXT
        )
    ");
});

it('execute database transaction successfully', function (): void {
    $email = $this->faker()->freeEmail();

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager) use ($email): void {
        $transactionManager->from('users')->insert([
            'name' => 'John Doe',
            'email' => $email,
        ]);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('John Doe');
    expect($users[0]['email'])->toBe($email);
});

it('executes multiple operations within transaction callback', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $transactionManager->from('users')->insert([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $transactionManager->from('users')->insert([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
        ]);

        $transactionManager->from('users')->insert([
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@example.com',
        ]);

        $transactionManager->from('users')
            ->whereEqual('name', 'Jane Smith')
            ->update(['email' => 'jane.updated@example.com']);

        $transactionManager->from('users')
            ->whereEqual('name', 'Bob Johnson')
            ->delete();
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(2);
    expect($users[0]['name'])->toBe('John Doe');
    expect($users[0]['email'])->toBe('john.doe@example.com');
    expect($users[1]['name'])->toBe('Jane Smith');
    expect($users[1]['email'])->toBe('jane.updated@example.com');
});

it('executes transaction with manual begin, commit and rollback', function (): void {
    $transactionManager = DB::connection('sqlite')->beginTransaction();

    try {
        $transactionManager->from('users')->insert([
            'name' => 'Alice Brown',
            'email' => 'alice.brown@example.com',
        ]);

        $transactionManager->from('users')->insert([
            'name' => 'Charlie Wilson',
            'email' => 'charlie.wilson@example.com',
        ]);

        $transactionManager->from('users')->insert([
            'name' => 'Diana Prince',
            'email' => 'diana.prince@example.com',
        ]);

        $transactionManager->from('users')
            ->whereEqual('name', 'Charlie Wilson')
            ->update(['name' => 'Charles Wilson']);

        $transactionManager->from('users')
            ->whereEqual('name', 'Diana Prince')
            ->delete();

        $transactionManager->commit();
    } catch (Throwable $e) {
        $transactionManager->rollBack();

        throw $e;
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(2);
    expect($users[0]['name'])->toBe('Alice Brown');
    expect($users[0]['email'])->toBe('alice.brown@example.com');
    expect($users[1]['name'])->toBe('Charles Wilson');
    expect($users[1]['email'])->toBe('charlie.wilson@example.com');
});

it('execute database transaction successfully using models', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        User::query($transactionManager)->insert([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('John Doe');
    expect($users[0]['email'])->toBe('john.doe@example.com');
});

it('executes multiple model operations with explicit transaction', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        User::query($transactionManager)->insert(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::query($transactionManager)->insert(['name' => 'Bob', 'email' => 'bob@example.com']);
        User::query($transactionManager)->insert(['name' => 'Charlie', 'email' => 'charlie@example.com']);

        User::query($transactionManager)
            ->whereEqual('name', 'Bob')
            ->update(['email' => 'bob.updated@example.com']);

        User::query($transactionManager)
            ->whereEqual('name', 'Charlie')
            ->delete();
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(2);
    expect($users[0]['name'])->toBe('Alice');
    expect($users[1]['name'])->toBe('Bob');
    expect($users[1]['email'])->toBe('bob.updated@example.com');
});

it('executes hybrid approach mixing query builder and models', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        User::query($transactionManager)->insert(['name' => 'Diana', 'email' => 'diana@example.com']);

        User::query($transactionManager)->insert(['name' => 'Eve', 'email' => 'eve@example.com']);

        $transactionManager->from('users')->insert(['name' => 'Frank', 'email' => 'frank@example.com']);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(3);
});

it('executes transaction with manual begin and commit using models', function (): void {
    $transactionManager = DB::connection('sqlite')->beginTransaction();

    try {
        User::query($transactionManager)->insert([
            'name' => 'Alice Brown',
            'email' => 'alice.brown@example.com',
        ]);

        $transactionManager->commit();
    } catch (Throwable $e) {
        $transactionManager->rollBack();

        throw $e;
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Alice Brown');
    expect($users[0]['email'])->toBe('alice.brown@example.com');
});

it('can select specific columns within transaction', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $transactionManager->from('users')->insert([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $transactionManager->from('users')->insert([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
        ]);
    });

    $transactionManager = DB::connection('sqlite')->beginTransaction();

    try {
        $users = $transactionManager->select(['name'])
            ->from('users')
            ->whereEqual('name', 'John Doe')
            ->get();

        expect($users)->toHaveCount(1);
        expect($users[0])->toHaveKey('name');
        expect($users[0])->not->toHaveKey('email');
        expect($users[0]['name'])->toBe('John Doe');

        $transactionManager->commit();
    } catch (Throwable $e) {
        $transactionManager->rollBack();

        throw $e;
    }
});

it('can select all columns within transaction', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $transactionManager->from('users')->insert([
            'name' => 'Alice Johnson',
            'email' => 'alice.johnson@example.com',
        ]);

        $transactionManager->from('users')->insert([
            'name' => 'Bob Wilson',
            'email' => 'bob.wilson@example.com',
        ]);
    });

    $transactionManager = DB::connection('sqlite')->beginTransaction();

    try {
        $users = $transactionManager->selectAllColumns()
            ->from('users')
            ->whereEqual('name', 'Alice Johnson')
            ->get();

        expect($users)->toHaveCount(1);
        expect($users[0])->toHaveKey('id');
        expect($users[0])->toHaveKey('name');
        expect($users[0])->toHaveKey('email');
        expect($users[0]['name'])->toBe('Alice Johnson');
        expect($users[0]['email'])->toBe('alice.johnson@example.com');

        $transactionManager->commit();
    } catch (Throwable $e) {
        $transactionManager->rollBack();

        throw $e;
    }
});

it('can execute unprepared statements within transaction', function (): void {
    $transactionManager = DB::connection('sqlite')->beginTransaction();

    try {
        $transactionManager->unprepared("
            INSERT INTO users (name, email) VALUES
            ('David Brown', 'david.brown@example.com'),
            ('Emma Davis', 'emma.davis@example.com'),
            ('Frank Miller', 'frank.miller@example.com')
        ");

        $transactionManager->unprepared("
            UPDATE users SET email = 'emma.updated@example.com' WHERE name = 'Emma Davis'
        ");

        $transactionManager->unprepared("
            DELETE FROM users WHERE name = 'Frank Miller'
        ");

        $transactionManager->commit();
    } catch (Throwable $e) {
        $transactionManager->rollBack();

        throw $e;
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(2);
    expect($users[0]['name'])->toBe('David Brown');
    expect($users[0]['email'])->toBe('david.brown@example.com');
    expect($users[1]['name'])->toBe('Emma Davis');
    expect($users[1]['email'])->toBe('emma.updated@example.com');
});

it('can combine select operations with other operations in transaction', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $transactionManager->from('users')->insert([
            'name' => 'George Clark',
            'email' => 'george.clark@example.com',
        ]);

        $transactionManager->from('users')->insert([
            'name' => 'Helen White',
            'email' => 'helen.white@example.com',
        ]);

        $users = $transactionManager->select(['name', 'email'])
            ->from('users')
            ->whereEqual('name', 'George Clark')
            ->get();

        expect($users)->toHaveCount(1);
        expect($users[0]['name'])->toBe('George Clark');

        $transactionManager->from('users')
            ->whereEqual('name', 'Helen White')
            ->update(['email' => 'helen.updated@example.com']);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(2);
    expect($users[1]['name'])->toBe('Helen White');
    expect($users[1]['email'])->toBe('helen.updated@example.com');
});

it('rolls back transaction on exception', function (): void {
    try {
        DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
            $transactionManager->from('users')->insert([
                'name' => 'Ian Scott',
                'email' => 'ian.scott@example.com',
            ]);

            throw new QueryErrorException('Simulated exception to trigger rollback');
        });
    } catch (QueryErrorException $e) {
        //
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(0);
});

it('creates a model using static create method within transaction callback', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        User::create([
            'name' => 'Transaction User',
            'email' => 'transaction@example.com',
        ], $transactionManager);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Transaction User');
    expect($users[0]['email'])->toBe('transaction@example.com');
});

it('creates multiple models using static create method within transaction', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com'], $transactionManager);
        User::create(['name' => 'Bob', 'email' => 'bob@example.com'], $transactionManager);
        User::create(['name' => 'Charlie', 'email' => 'charlie@example.com'], $transactionManager);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(3);
    expect($users[0]['name'])->toBe('Alice');
    expect($users[1]['name'])->toBe('Bob');
    expect($users[2]['name'])->toBe('Charlie');
});

it('rolls back model create on transaction failure', function (): void {
    try {
        DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
            User::create(['name' => 'Will Rollback', 'email' => 'rollback@example.com'], $transactionManager);

            throw new QueryErrorException('Force rollback');
        });
    } catch (QueryErrorException $e) {
        //
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(0);
});

it('creates model with manual transaction control', function (): void {
    $transactionManager = DB::connection('sqlite')->beginTransaction();

    try {
        User::create([
            'name' => 'Manual Transaction User',
            'email' => 'manual@example.com',
        ], $transactionManager);

        $transactionManager->commit();
    } catch (Throwable $e) {
        $transactionManager->rollBack();

        throw $e;
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Manual Transaction User');
});

it('finds a model within transaction using static find method', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Existing User', 'existing@example.com')
    ");

    $transactionManager = DB::connection('sqlite')->beginTransaction();

    try {
        $user = User::find(1, ['*'], $transactionManager);

        expect($user)->not->toBeNull();
        expect($user->name)->toBe('Existing User');
        expect($user->email)->toBe('existing@example.com');

        $transactionManager->commit();
    } catch (Throwable $e) {
        $transactionManager->rollBack();

        throw $e;
    }
});

it('finds model within transaction callback', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Find Me', 'findme@example.com')
    ");

    $foundUser = null;

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager) use (&$foundUser): void {
        $foundUser = User::find(1, ['*'], $transactionManager);
    });

    expect($foundUser)->not->toBeNull();
    expect($foundUser->name)->toBe('Find Me');
});

it('saves a model instance within transaction callback', function (): void {
    $user = new User();
    $user->name = 'Save Test';
    $user->email = 'save@example.com';

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager) use ($user): void {
        $result = $user->save($transactionManager);

        expect($result)->toBeTrue();
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Save Test');
    expect($users[0]['email'])->toBe('save@example.com');
});

it('updates existing model within transaction using save', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Original Name', 'original@example.com')
    ");

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $user = User::find(1, ['*'], $transactionManager);

        $user->name = 'Updated Name';
        $user->email = 'updated@example.com';

        $result = $user->save($transactionManager);

        expect($result)->toBeTrue();
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Updated Name');
    expect($users[0]['email'])->toBe('updated@example.com');
});

it('saves multiple model instances within transaction', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $user1 = new User();
        $user1->name = 'User One';
        $user1->email = 'one@example.com';
        $user1->save($transactionManager);

        $user2 = new User();
        $user2->name = 'User Two';
        $user2->email = 'two@example.com';
        $user2->save($transactionManager);

        $user3 = new User();
        $user3->name = 'User Three';
        $user3->email = 'three@example.com';
        $user3->save($transactionManager);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(3);
});

it('rolls back save on transaction failure', function (): void {
    $user = new User();
    $user->name = 'Rollback Save';
    $user->email = 'rollback@example.com';

    try {
        DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager) use ($user): void {
            $user->save($transactionManager);

            throw new QueryErrorException('Force rollback after save');
        });
    } catch (QueryErrorException $e) {
        //
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(0);
});

it('deletes a model within transaction callback', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'To Delete', 'delete@example.com')
    ");

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $user = User::find(1, ['*'], $transactionManager);

        $result = $user->delete($transactionManager);

        expect($result)->toBeTrue();
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(0);
});

it('deletes multiple models within transaction', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Delete One', 'delete1@example.com'),
        (2, 'Delete Two', 'delete2@example.com'),
        (3, 'Keep Three', 'keep3@example.com')
    ");

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $user1 = User::find(1, ['*'], $transactionManager);
        $user1->delete($transactionManager);

        $user2 = User::find(2, ['*'], $transactionManager);
        $user2->delete($transactionManager);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Keep Three');
});

it('rolls back delete on transaction failure', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Should Not Delete', 'should-not-delete@example.com')
    ");

    try {
        DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
            $user = User::find(1, ['*'], $transactionManager);

            $user->delete($transactionManager);

            throw new QueryErrorException('Force rollback after delete');
        });
    } catch (QueryErrorException $e) {
        //
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Should Not Delete');
});

it('performs complex operations mixing create, find, save, and delete in transaction', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Existing User', 'existing@example.com')
    ");

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        User::on('sqlite')->create(['name' => 'New User', 'email' => 'new@example.com'], $transactionManager);

        $existingUser = User::on('sqlite')->find(1, ['*'], $transactionManager);
        $existingUser->name = 'Updated Existing';
        $existingUser->save($transactionManager);

        $temporaryUser = User::on('sqlite')->create(['name' => 'Temporary', 'email' => 'temp@example.com'], $transactionManager);

        $foundTemp = User::on('sqlite')->find($temporaryUser->id, ['*'], $transactionManager);
        $foundTemp->delete($transactionManager);
    });

    $users = DB::connection('sqlite')->from('users')->orderBy('id', Order::ASC)->get();

    expect($users)->toHaveCount(2);
    expect($users[0]['id'])->toBe(1);
    expect($users[0]['name'])->toBe('Updated Existing');
    expect($users[1]['name'])->toBe('New User');
});

it('works without transaction manager when parameter is null', function (): void {
    $user = User::on('sqlite')->create(['name' => 'No Transaction', 'email' => 'no-tx@example.com'], null);

    expect($user->id)->toBeGreaterThan(0);
    expect($user->isExisting())->toBeTrue();

    $foundUser = User::on('sqlite')->find($user->id, ['*'], null);

    expect($foundUser)->not->toBeNull();
    expect($foundUser->name)->toBe('No Transaction');

    $foundUser->name = 'Updated No Transaction';
    $foundUser->save(null);

    $verifyUser = User::on('sqlite')->find($user->id);

    expect($verifyUser->name)->toBe('Updated No Transaction');

    $verifyUser->delete(null);

    $deletedUser = User::on('sqlite')->find($user->id);

    expect($deletedUser)->toBeNull();
});

it('creates model using fluent connection syntax with on method', function (): void {
    $user = User::on('sqlite')->create([
        'name' => 'Fluent User',
        'email' => 'fluent@example.com',
    ]);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->id)->toBeGreaterThan(0);
    expect($user->name)->toBe('Fluent User');
    expect($user->email)->toBe('fluent@example.com');
    expect($user->isExisting())->toBeTrue();

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Fluent User');
});

it('queries models using fluent connection syntax', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'User One', 'one@example.com'),
        (2, 'User Two', 'two@example.com')
    ");

    $users = User::on('sqlite')->get();

    expect($users)->toHaveCount(2);
    expect($users[0]->name)->toBe('User One');
    expect($users[1]->name)->toBe('User Two');
});

it('finds model using fluent connection syntax', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Find Me', 'findme@example.com')
    ");

    $user = User::on('sqlite')->whereEqual('id', 1)->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Find Me');
});

it('finds model by id using fluent connection syntax with find method', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Find By ID', 'findbyid@example.com'),
        (2, 'Another User', 'another@example.com')
    ");

    $user = User::on('sqlite')->find(1);

    expect($user)->not->toBeNull();
    expect($user->id)->toBe(1);
    expect($user->name)->toBe('Find By ID');
    expect($user->email)->toBe('findbyid@example.com');

    $user2 = User::on('sqlite')->find(2);

    expect($user2)->not->toBeNull();
    expect($user2->name)->toBe('Another User');

    $nonExistent = User::on('sqlite')->find(999);

    expect($nonExistent)->toBeNull();
});

it('finds model with specific columns using fluent connection syntax', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Partial User', 'partial@example.com')
    ");

    $user = User::on('sqlite')->find(1, ['id', 'name']);

    expect($user)->not->toBeNull();
    expect($user->id)->toBe(1);
    expect($user->name)->toBe('Partial User');
});

it('creates model using fluent connection with transaction using with transaction', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $user = User::on('sqlite')
            ->withTransaction($transactionManager)
            ->create([
                'name' => 'Transaction Fluent User',
                'email' => 'txfluent@example.com',
            ]);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->id)->toBeGreaterThan(0);
        expect($user->name)->toBe('Transaction Fluent User');
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Transaction Fluent User');
});

it('finds model using fluent connection with transaction using withTransaction()', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Find In Transaction', 'findintx@example.com')
    ");

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $user = User::on('sqlite')
            ->withTransaction($transactionManager)
            ->find(1);

        expect($user)->not->toBeNull();
        expect($user->name)->toBe('Find In Transaction');

        $user->name = 'Updated In Transaction';
        $user->save($transactionManager);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Updated In Transaction');
});

it('queries models using fluent connection with transaction using withTransaction()', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        User::on('sqlite')
            ->withTransaction($transactionManager)
            ->create(['name' => 'User 1', 'email' => 'user1@example.com']);

        User::on('sqlite')
            ->withTransaction($transactionManager)
            ->create(['name' => 'User 2', 'email' => 'user2@example.com']);

        $users = User::on('sqlite')
            ->withTransaction($transactionManager)
            ->whereEqual('name', 'User 1')
            ->get();

        expect($users)->toHaveCount(1);
        expect($users[0]->name)->toBe('User 1');
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(2);
});

it('rolls back when using fluent connection with transaction', function (): void {
    try {
        DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
            User::on('sqlite')
                ->withTransaction($transactionManager)
                ->create(['name' => 'Will Rollback', 'email' => 'rollback@example.com']);

            throw new QueryErrorException('Force rollback');
        });
    } catch (QueryErrorException $e) {
        //
    }

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(0);
});

it('performs complex operations with fluent connection and transaction', function (): void {
    DB::connection('sqlite')->unprepared("
        INSERT INTO users (id, name, email) VALUES
        (1, 'Existing', 'existing@example.com')
    ");

    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $newUser = User::on('sqlite')
            ->withTransaction($transactionManager)
            ->create(['name' => 'New User', 'email' => 'new@example.com']);

        expect($newUser->id)->toBeGreaterThan(0);

        $existingUser = User::on('sqlite')
            ->withTransaction($transactionManager)
            ->find(1);

        $existingUser->name = 'Updated Existing';
        $existingUser->save($transactionManager);

        $tempUser = User::on('sqlite')
            ->withTransaction($transactionManager)
            ->create(['name' => 'Temp', 'email' => 'temp@example.com']);

        $foundTemp = User::on('sqlite')
            ->withTransaction($transactionManager)
            ->find($tempUser->id);

        $foundTemp->delete($transactionManager);
    });

    $users = DB::connection('sqlite')->from('users')->orderBy('id', Order::ASC)->get();

    expect($users)->toHaveCount(2);
    expect($users[0]['name'])->toBe('Updated Existing');
    expect($users[1]['name'])->toBe('New User');
});

it('can execute queries without passing transaction manager explicitly', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        DB::connection('sqlite')->from('users')->insert(['name' => 'Test 1', 'email' => 'test1@example.com']);
        DB::connection('sqlite')->from('users')->insert(['name' => 'Test 2', 'email' => 'test2@example.com']);
    });

    $results = DB::connection('sqlite')->from('users')->get();

    expect($results)->toHaveCount(2);
    expect($results[0]['name'])->toBe('Test 1');
    expect($results[1]['name'])->toBe('Test 2');
});

it('rolls back automatically on exception without passing transaction manager', function (): void {
    try {
        DB::connection('sqlite')->transaction(function (): void {
            DB::connection('sqlite')->from('users')->insert(['name' => 'Test 1', 'email' => 'test1@example.com']);

            throw new Exception('Simulated error');
        });
    } catch (Exception $e) {
        //
    }

    $results = DB::connection('sqlite')->from('users')->get();

    expect($results)->toHaveCount(0);
});

it('works with nested query builder instances', function (): void {
    DB::connection('sqlite')->transaction(function (): void {
        $qb1 = DB::connection('sqlite')->from('users');
        $qb2 = DB::connection('sqlite')->from('users');

        $qb1->insert(['name' => 'From QB1', 'email' => 'qb1@example.com']);
        $qb2->insert(['name' => 'From QB2', 'email' => 'qb2@example.com']);
    });

    $results = DB::connection('sqlite')->from('users')->get();

    expect($results)->toHaveCount(2);
});
