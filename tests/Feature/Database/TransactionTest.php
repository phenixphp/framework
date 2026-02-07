<?php

declare(strict_types=1);

use Phenix\Auth\User;
use Phenix\Database\TransactionManager;
use Phenix\Facades\DB;
use Phenix\Testing\Concerns\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    DB::connection('sqlite')->unprepared("DROP TABLE IF EXISTS users");

    DB::connection('sqlite')->unprepared("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL
        )
    ");
});

it('execute database transaction successfully', function (): void {
    DB::connection('sqlite')->transaction(function (TransactionManager $transactionManager): void {
        $transactionManager->from('users')->insert([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);
    });

    $users = DB::connection('sqlite')->from('users')->get();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('John Doe');
    expect($users[0]['email'])->toBe('john.doe@example.com');
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
