<?php

declare(strict_types=1);

use Phenix\Util\Date;
use function Pest\Faker\faker;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;
use Phenix\Database\Models\Collection;
use Tests\Feature\Database\Models\Post;
use Tests\Feature\Database\Models\User;
use Phenix\Database\Constants\Connections;
use Phenix\Database\Models\Relationships\HasMany;
use Tests\Feature\Database\Models\Comment;

use Tests\Feature\Database\Models\Product;
use Tests\Mocks\Database\MysqlConnectionPool;

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
});

it('loads the relationship when the model has many child models without chaperone', function () {
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

    /** @var User $user */
    $user = User::query()
        ->selectAllColumns()
        ->whereEqual('id', 1)
        ->with(['products'])
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

    expect(isset($product->user))->toBeFalse();
});

it('loads the relationship when the model has many child models loading chaperone from relationship method', function () {
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

    /** @var User $user */
    $user = User::query()
        ->selectAllColumns()
        ->whereEqual('id', 1)
        ->with([
            'products' => function (HasMany $hasMany): void {
                $hasMany->withChaperone();
            },
        ])
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

    expect(isset($product->user))->toBeTrue();
    expect($product->user->id)->toBe($userData['id']);
});

it('loads the relationship when the model has many child models loading chaperone by default', function () {
    $userData = [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john.doe@email.com',
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $userCollection[] = $userData;

    $commentData = [
        'id' => 1,
        'content' => 'PHP is awesome',
        'user_id' => $userData['id'],
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $commentCollection[] = $commentData;

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($userCollection)),
            new Statement(new Result($commentCollection)),
        );

    $this->app->swap(Connections::default(), $connection);

    /** @var User $user */
    $user = User::query()
        ->selectAllColumns()
        ->whereEqual('id', 1)
        ->with(['comments'])
        ->first();

    expect($user)->toBeInstanceOf(User::class);

    expect($user->id)->toBe($userData['id']);
    expect($user->name)->toBe($userData['name']);
    expect($user->email)->toBe($userData['email']);

    expect($user->comments)->toBeInstanceOf(Collection::class);
    expect($user->comments->count())->toBe(1);

    /** @var Comment $comments */
    $comment = $user->comments->first();

    expect($comment->id)->toBe($commentData['id']);
    expect($comment->content)->toBe($commentData['content']);
    expect($comment->createdAt)->toBeInstanceOf(Date::class);
    expect($comment->userId)->toBe($userData['id']);

    expect(isset($comment->user))->toBeTrue();
    expect($comment->user->id)->toBe($userData['id']);
});
