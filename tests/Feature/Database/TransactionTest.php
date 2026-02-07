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
    DB::connection('sqlite')->transaction(function (TransactionManager $tx): void {
        $tx->from('users')->insert([
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
    DB::connection('sqlite')->transaction(function (TransactionManager $tx): void {
        $tx->from('users')->insert([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $tx->from('users')->insert([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
        ]);

        $tx->from('users')->insert([
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@example.com',
        ]);

        $tx->from('users')
            ->whereEqual('name', 'Jane Smith')
            ->update(['email' => 'jane.updated@example.com']);

        $tx->from('users')
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
    $tx = DB::connection('sqlite')->beginTransaction();

    try {
        $tx->from('users')->insert([
            'name' => 'Alice Brown',
            'email' => 'alice.brown@example.com',
        ]);

        $tx->from('users')->insert([
            'name' => 'Charlie Wilson',
            'email' => 'charlie.wilson@example.com',
        ]);

        $tx->from('users')->insert([
            'name' => 'Diana Prince',
            'email' => 'diana.prince@example.com',
        ]);

        $tx->from('users')
            ->whereEqual('name', 'Charlie Wilson')
            ->update(['name' => 'Charles Wilson']);

        $tx->from('users')
            ->whereEqual('name', 'Diana Prince')
            ->delete();

        $tx->commit();
    } catch (Throwable $e) {
        $tx->rollBack();
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
