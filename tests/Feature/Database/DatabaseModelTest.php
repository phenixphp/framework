<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connections;
use Phenix\Database\Models\Collection;
use Phenix\Facades\Route;
use Phenix\Http\Request;
use Phenix\Http\Response;
use Phenix\Util\Date;
use Tests\Feature\Database\Models\Post;
use Tests\Feature\Database\Models\Product;
use Tests\Feature\Database\Models\User;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

use function Pest\Faker\faker;

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

    Route::get('/users', function (Request $request) use ($data): Response {
        $users = User::query()->selectAllColumns()->get();

        expect($users)->toBeInstanceOf(Collection::class);
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

    $this->get('/users', $data)
        ->assertOk();
});

it('loads the relationship when the model belongs to a parent model', function () {
    $userData = [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john.doe@email.com',
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $userCollection[] = $userData;

    $postData = [
        'id' => 1,
        'title' => 'PHP is great',
        'content' => faker()->sentence(),
        'user_id' => $userData['id'],
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $postCollection[] = $postData;

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($postCollection)),
            new Statement(new Result($userCollection)),
        );

    $this->app->swap(Connections::default(), $connection);

    Route::get('/posts/{post}', function (Request $request) use ($postData, $userData): Response {
        /** @var Post $post */
        $post = Post::query()->selectAllColumns()
            ->with('user')
            ->first();

        expect($post)->toBeInstanceOf(Post::class);

        expect($post->id)->toBe($postData['id']);
        expect($post->title)->toBe($postData['title']);
        expect($post->content)->toBe($postData['content']);
        expect($post->createdAt)->toBeInstanceOf(Date::class);
        expect($post->updatedAt)->toBeNull();

        expect($post->user)->toBeInstanceOf(User::class);

        expect($post->user->id)->toBe($userData['id']);
        expect($post->user->name)->toBe($userData['name']);
        expect($post->user->email)->toBe($userData['email']);

        return response()->json($post);
    });

    $this->app->run();

    $this->get('/posts/' . $postData['id'])
        ->assertOk();
});

it('loads the relationship when the model has many child models', function () {
    $userData = [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john.doe@email.com',
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $userCollection[] = $userData;

    $productData = [
        'id' => 1,
        'description' => 'Phenix shirt',
        'price' => 100,
        'stock' => 6,
        'user_id' => $userData['id'],
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $productCollection[] = $productData;

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($userCollection)),
            new Statement(new Result($productCollection)),
        );

    $this->app->swap(Connections::default(), $connection);

    Route::get('/users/{user}', function (Request $request) use ($userData, $productData): Response {
        /** @var User $user */
        $user = User::query()
            ->selectAllColumns()
            ->whereEqual('id', $request->route('user'))
            ->with('products')
            ->first();

        expect($user)->toBeInstanceOf(User::class);

        expect($user->id)->toBe($userData['id']);
        expect($user->name)->toBe($userData['name']);
        expect($user->email)->toBe($userData['email']);

        expect($user->products)->toBeInstanceOf(Collection::class);
        expect($user->products->count())->toBe(1);

        /** @var Product $products */
        $product = $user->products->first();

        expect($product->id)->toBe($productData['id']);
        expect($product->description)->toBe($productData['description']);
        expect($product->price)->toBe((float) $productData['price']);
        expect($product->createdAt)->toBeInstanceOf(Date::class);
        expect($product->userId)->toBe($userData['id']);

        expect($product->user)->toBeInstanceOf(User::class);

        return response()->json($user);
    });

    $this->app->run();

    $this->get('/users/' . $userData['id'])
        ->assertOk();
});
