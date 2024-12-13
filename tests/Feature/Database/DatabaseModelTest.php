<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connections;
use Phenix\Database\Models\Collection;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Database\Models\Relationships\BelongsTo;
use Phenix\Database\Models\Relationships\BelongsToMany;
use Phenix\Database\Models\Relationships\HasMany;
use Phenix\Exceptions\Database\ModelException;
use Phenix\Util\Date;
use Tests\Feature\Database\Models\Comment;
use Tests\Feature\Database\Models\Invoice;
use Tests\Feature\Database\Models\Post;
use Tests\Feature\Database\Models\Product;
use Tests\Feature\Database\Models\User;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

use function Pest\Faker\faker;

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

    $data[0]['createdAt'] = Date::parse($data[0]['created_at'])->toIso8601String();
    unset($data[0]['created_at']);
    $data[0]['updatedAt'] = null;

    expect($user->toJson())->toBe(json_encode($data[0]));
});

it('loads relationship when the model belongs to a parent model', function () {
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

it('loads relationship with short syntax to select columns', function () {
    $userData = [
        'id' => 1,
        'name' => 'John Doe',
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
        ->with('user:id,name')
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
    expect(isset($post->user->email))->toBeFalse();
});

it('loads relationship when the model belongs to a parent model with column selection', function () {
    $userData = [
        'id' => 1,
        'name' => 'John Doe',
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
        ->with([
            'user' => function (BelongsTo $belongsTo) {
                $belongsTo->query()
                    ->select(['id', 'name']);
            },
        ])
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
    expect(isset($post->user->email))->toBeFalse();
});

it('loads relationship when the model has many child models without chaperone', function () {
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

it('loads relationship when the model has many child models loading chaperone from relationship method', function () {
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

    $userData['createdAt'] = Date::parse($userData['created_at'])->toIso8601String();
    unset($userData['created_at']);
    $userData['updatedAt'] = null;

    $productData['createdAt'] = Date::parse($productData['created_at'])->toIso8601String();
    $productData['updatedAt'] = null;
    $productData['price'] = (float) $productData['price'];
    $productData['userId'] = $userData['id'];
    unset($productData['user_id'], $productData['created_at']);
    $productData['user'] = $userData;

    $userData['products'][] = $productData;
    $output = $user->toArray();

    ksort($output['products'][0]);
    ksort($userData['products'][0]);

    expect($output)->toBe($userData);
});

it('loads relationship when the model has many child models loading chaperone by default', function () {
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

it('loads relationship when the model belongs to many models', function () {
    $invoiceData = [
        'id' => 20,
        'reference' => '1234',
        'value' => 100.0,
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $invoiceCollection[] = $invoiceData;

    $productData = [
        'id' => 122,
        'description' => 'PHP Plush',
        'price' => 50.0,
        'created_at' => Date::now()->toDateTimeString(),
        'pivot_product_id' => 122,
        'pivot_invoice_id' => 20,
    ];

    $productCollection[] = $productData;

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($invoiceCollection)),
            new Statement(new Result($productCollection)),
        );

    $this->app->swap(Connections::default(), $connection);

    /** @var Collection<Invoice> $invoices */
    $invoices = Invoice::query()
        ->with(['products'])
        ->get();

    expect($invoices)->toBeInstanceOf(Collection::class);
    expect($invoices->count())->toBe(1);

    expect($invoices->first()->id)->toBe($invoiceData['id']);
    expect($invoices->first()->reference)->toBe($invoiceData['reference']);
    expect($invoices->first()->value)->toBe($invoiceData['value']);

    expect($invoices->first()->products)->toBeInstanceOf(Collection::class);
    expect($invoices->first()->products->count())->toBe(1);

    /** @var Product $product */
    $product = $invoices->first()->products->first();

    expect($product->id)->toBe($productData['id']);
    expect($product->description)->toBe($productData['description']);
    expect($product->price)->toBe($productData['price']);
    expect($product->createdAt)->toBeInstanceOf(Date::class);
    expect($product->pivot)->toBeInstanceOf(stdClass::class);
    expect($product->pivot->product_id)->toBe(122);
    expect($product->pivot->invoice_id)->toBe(20);
});

it('loads relationship when the model belongs to many models with pivot columns', function () {
    $invoiceData = [
        'id' => 20,
        'reference' => '1234',
        'value' => 100.0,
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $invoiceCollection[] = $invoiceData;

    $productData = [
        'id' => 122,
        'description' => 'PHP Plush',
        'price' => 50.0,
        'created_at' => Date::now()->toDateTimeString(),
        'pivot_product_id' => 122,
        'pivot_invoice_id' => 20,
        'pivot_quantity' => 2,
        'pivot_value' => 100.0,
    ];

    $productCollection[] = $productData;

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($invoiceCollection)),
            new Statement(new Result($productCollection)),
        );

    $this->app->swap(Connections::default(), $connection);

    /** @var Collection<Invoice> $invoices */
    $invoices = Invoice::query()
        ->with([
            'products' => function (BelongsToMany $relation) {
                $relation->withPivot(['quantity', 'value']);
            },
        ])
        ->get();

    expect($invoices)->toBeInstanceOf(Collection::class);
    expect($invoices->count())->toBe(1);

    expect($invoices->first()->id)->toBe($invoiceData['id']);
    expect($invoices->first()->reference)->toBe($invoiceData['reference']);
    expect($invoices->first()->value)->toBe($invoiceData['value']);

    expect($invoices->first()->products)->toBeInstanceOf(Collection::class);
    expect($invoices->first()->products->count())->toBe(1);

    /** @var Product $product */
    $product = $invoices->first()->products->first();

    expect($product->id)->toBe($productData['id']);
    expect($product->description)->toBe($productData['description']);
    expect($product->price)->toBe($productData['price']);
    expect($product->createdAt)->toBeInstanceOf(Date::class);
    expect($product->pivot)->toBeInstanceOf(stdClass::class);
    expect($product->pivot->product_id)->toBe(122);
    expect($product->pivot->invoice_id)->toBe(20);
    expect($product->pivot->quantity)->toBe(2);
    expect($product->pivot->value)->toBe(100.0);
});

it('loads relationship when the model belongs to many models with column selection', function () {
    $invoiceData = [
        'id' => 20,
        'reference' => '1234',
        'value' => 100.0,
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $invoiceCollection[] = $invoiceData;

    $productData = [
        'id' => 122,
        'description' => 'PHP Plush',
        'pivot_product_id' => 122,
        'pivot_invoice_id' => 20,
        'pivot_quantity' => 2,
        'pivot_value' => 100.0,
    ];

    $productCollection[] = $productData;

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($invoiceCollection)),
            new Statement(new Result($productCollection)),
        );

    $this->app->swap(Connections::default(), $connection);

    /** @var Collection<Invoice> $invoices */
    $invoices = Invoice::query()
        ->with([
            'products' => function (BelongsToMany $relation) {
                $relation->withPivot(['quantity', 'value'])
                    ->query()
                    ->select(['id', 'description']);
            },
        ])
        ->get();

    expect($invoices)->toBeInstanceOf(Collection::class);
    expect($invoices->count())->toBe(1);

    expect($invoices->first()->id)->toBe($invoiceData['id']);
    expect($invoices->first()->reference)->toBe($invoiceData['reference']);
    expect($invoices->first()->value)->toBe($invoiceData['value']);

    expect($invoices->first()->products)->toBeInstanceOf(Collection::class);
    expect($invoices->first()->products->count())->toBe(1);

    /** @var Product $product */
    $product = $invoices->first()->products->first();

    expect($product->id)->toBe($productData['id']);
    expect($product->description)->toBe($productData['description']);
    expect(isset($product->price))->toBeFalse();
    expect(isset($product->createdAt))->toBeFalse();
    expect($product->pivot)->toBeInstanceOf(stdClass::class);
    expect($product->pivot->product_id)->toBe(122);
    expect($product->pivot->invoice_id)->toBe(20);
    expect($product->pivot->quantity)->toBe(2);
    expect($product->pivot->value)->toBe(100.0);
});

it('loads nested relationship using dot notation', function () {
    $userData = [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john.doe@email.com',
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $productData = [
        'id' => 1,
        'description' => 'Phenix shirt',
        'price' => 100,
        'stock' => 6,
        'user_id' => $userData['id'],
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $commentData = [
        'id' => 1,
        'content' => 'PHP is awesome',
        'product_id' => $productData['id'],
        'created_at' => Date::now()->toDateTimeString(),
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([$commentData])),
            new Statement(new Result([$productData])),
            new Statement(new Result([$userData])),
        );

    $this->app->swap(Connections::default(), $connection);

    /** @var Comment $comment */
    $comment = Comment::query()
        ->with([
            'product:id,description,price,stock,user_id,created_at',
            'product.user:id,name,email,created_at',
        ])
        ->first();

    expect($comment)->toBeInstanceOf(DatabaseModel::class);

    expect($comment->id)->toBe($commentData['id']);
    expect($comment->content)->toBe($commentData['content']);
    expect($comment->createdAt)->toBeInstanceOf(Date::class);
    expect($comment->productId)->toBe($productData['id']);

    expect($comment->product)->toBeInstanceOf(DatabaseModel::class);
    expect($comment->product->id)->toBe($productData['id']);
    expect($comment->product->description)->toBe($productData['description']);

    expect($comment->product->user)->toBeInstanceOf(DatabaseModel::class);
    expect($comment->product->user->id)->toBe($userData['id']);
    expect($comment->product->user->name)->toBe($userData['name']);
    expect($comment->product->user->email)->toBe($userData['email']);
});

it('dispatches error on unknown column', function () {
    expect(function () {
        $data = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john.doe@email.com',
                'created_at' => Date::now()->toDateTimeString(),
                'unknown_column' => 'unknown',
            ],
        ];

        $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

        $connection->expects($this->exactly(1))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                new Statement(new Result($data)),
            );

        $this->app->swap(Connections::default(), $connection);

        User::query()->selectAllColumns()->get();
    })->toThrow(
        ModelException::class,
        "Unknown column 'unknown_column' for model " . User::class,
    );
});

it('dispatches error on unknown relationship', function () {
    expect(function () {
        Post::query()->selectAllColumns()
            ->with('company')
            ->first();
    })->toThrow(
        ModelException::class,
        "Undefined relationship company for " . Post::class,
    );
});
