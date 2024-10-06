<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connections;
use Phenix\Database\Models\Collections\DatabaseModelCollection;
use Phenix\Facades\Route;
use Phenix\Http\Request;
use Phenix\Http\Response;
use Phenix\Util\Date;
use Tests\Feature\Database\Models\User;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

afterEach(function () {
    $this->app->stop();
});

it('creates models with query builders successfully', function () {
    $data = [
        [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@email.com',
            'created_at' => Date::now()->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($data)),
        );

    $this->app->swap(Connections::default(), $connection);

    Route::post('/users', function (Request $request) use ($data): Response {
        $users = User::query()->selectAllColumns()->get();

        expect($users)->toBeInstanceOf(DatabaseModelCollection::class);
        expect($users->first())->toBeInstanceOf(User::class);

        /** @var User $user */
        $user = $users->first();

        expect($user->id)->toBe($data[0]['id']);
        expect($user->name)->toBe($data[0]['name']);
        expect($user->email)->toBe($data[0]['email']);
        expect($user->createdAt)->toBeInstanceOf(Date::class);
        expect($user->updatedAt)->toBeNull();

        return response()->json($users);
    });

    $this->app->run();

    $this->post('/users', $data)
        ->assertOk();
});
