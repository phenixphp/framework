<?php

declare(strict_types=1);

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
    DB::connection('sqlite')->transaction(function (TransactionManager $tx) {
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
