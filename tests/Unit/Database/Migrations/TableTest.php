<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\BigInteger;
use Phenix\Database\Migrations\Columns\Binary;
use Phenix\Database\Migrations\Columns\Boolean;
use Phenix\Database\Migrations\Columns\Date;
use Phenix\Database\Migrations\Columns\DateTime;
use Phenix\Database\Migrations\Columns\Decimal;
use Phenix\Database\Migrations\Columns\Enum;
use Phenix\Database\Migrations\Columns\Floating;
use Phenix\Database\Migrations\Columns\Integer;
use Phenix\Database\Migrations\Columns\Json;
use Phenix\Database\Migrations\Columns\SmallInteger;
use Phenix\Database\Migrations\Columns\Str;
use Phenix\Database\Migrations\Columns\Text;
use Phenix\Database\Migrations\Columns\Timestamp;
use Phenix\Database\Migrations\Columns\Uuid;
use Phenix\Database\Migrations\Table;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table\Column;

beforeEach(function (): void {
    $this->mockAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

    $this->mockAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $this->mockAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $this->mockAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });
});

it('can add string column with options', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $table->string('username', 50)->notNull()->comment('User name');

    $columns = $table->getColumnBuilders();

    expect(count($columns))->toBe(1);

    $column = $columns[0];

    expect($column)->toBeInstanceOf(Str::class);
    expect($column->getName())->toBe('username');
    expect($column->getType())->toBe('string');
    expect($column->getOptions())->toBe([
        'limit' => 50,
        'null' => false,
        'comment' => 'User name',
    ]);
});

it('can add integer column with options', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->integer('age', 10, false, true)->default(0)->comment('User age');

    expect($column)->toBeInstanceOf(Integer::class);
    expect($column->getName())->toBe('age');
    expect($column->getType())->toBe('integer');
    expect($column->getOptions())->toBe([
        'limit' => 10,
        'default' => 0,
        'comment' => 'User age',
    ]);
});

it('can add big integer column with identity', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->bigInteger('id', true, false)->comment('Primary key');

    expect($column)->toBeInstanceOf(BigInteger::class);
    expect($column->getName())->toBe('id');
    expect($column->getType())->toBe('biginteger');
    expect($column->getOptions())->toBe([
        'identity' => true,
        'null' => false,
        'signed' => false,
        'comment' => 'Primary key',
    ]);
});

it('can add small integer column', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->smallInteger('status', false, true)->default(1);

    expect($column)->toBeInstanceOf(SmallInteger::class);
    expect($column->getName())->toBe('status');
    expect($column->getType())->toBe('smallinteger');
    expect($column->getOptions())->toBe([
        'default' => 1,
    ]);
});

it('can add text column with limit', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $column = $table->text('content', 1000)->nullable()->comment('Post content');

    expect($column)->toBeInstanceOf(Text::class);
    expect($column->getName())->toBe('content');
    expect($column->getType())->toBe('text');
    expect($column->getOptions())->toBe([
        'limit' => 1000,
        'null' => true,
        'comment' => 'Post content',
    ]);
});

it('can add boolean column', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->boolean('is_active', true)->default(true)->comment('User status');

    expect($column)->toBeInstanceOf(Boolean::class);
    expect($column->getName())->toBe('is_active');
    expect($column->getType())->toBe('boolean');
    expect($column->getOptions())->toBe([
        'default' => true,
        'comment' => 'User status',
    ]);
});

it('can add decimal column with precision and scale', function (): void {
    $table = new Table('products', adapter: $this->mockAdapter);

    $column = $table->decimal('price', 8, 2, true)->default(0.00)->comment('Product price');

    expect($column)->toBeInstanceOf(Decimal::class);
    expect($column->getName())->toBe('price');
    expect($column->getType())->toBe('decimal');
    expect($column->getOptions())->toBe([
        'precision' => 8,
        'scale' => 2,
        'default' => 0.00,
        'comment' => 'Product price',
    ]);
});

it('can add datetime column', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $column = $table->dateTime('published_at')->nullable()->comment('Publication date');

    expect($column)->toBeInstanceOf(DateTime::class);
    expect($column->getName())->toBe('published_at');
    expect($column->getType())->toBe('datetime');
    expect($column->getOptions())->toBe([
        'null' => true,
        'comment' => 'Publication date',
    ]);
});

it('can add timestamp column with timezone', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->timestamp('created_at', true)->notNull()->currentTimestamp();

    expect($column)->toBeInstanceOf(Timestamp::class);
    expect($column->getName())->toBe('created_at');
    expect($column->getType())->toBe('timestamp');
    expect($column->getOptions())->toBe([
        'timezone' => true,
        'null' => false,
        'default' => 'CURRENT_TIMESTAMP',
    ]);
});

it('can add json column', function (): void {
    $table = new Table('settings', adapter: $this->mockAdapter);

    $column = $table->json('data')->nullable()->comment('JSON data');

    expect($column)->toBeInstanceOf(Json::class);
    expect($column->getName())->toBe('data');
    expect($column->getType())->toBe('json');
    expect($column->getOptions())->toBe([
        'null' => true,
        'comment' => 'JSON data',
    ]);
});

it('can add uuid column', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->uuid('uuid')->notNull()->comment('Unique identifier');

    expect($column)->toBeInstanceOf(Uuid::class);
    expect($column->getName())->toBe('uuid');
    expect($column->getType())->toBe('uuid');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'Unique identifier',
    ]);
});

it('can add enum column with values', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->enum('role', ['admin', 'user', 'guest'])->default('user')->comment('User role');

    expect($column)->toBeInstanceOf(Enum::class);
    expect($column->getName())->toBe('role');
    expect($column->getType())->toBe('enum');
    expect($column->getOptions())->toBe([
        'values' => ['admin', 'user', 'guest'],
        'default' => 'user',
        'comment' => 'User role',
    ]);
});

it('can add float column', function (): void {
    $table = new Table('measurements', adapter: $this->mockAdapter);

    $column = $table->float('temperature')->default(0.0)->comment('Temperature value');

    expect($column)->toBeInstanceOf(Floating::class);
    expect($column->getName())->toBe('temperature');
    expect($column->getType())->toBe('float');
    expect($column->getOptions())->toBe([
        'default' => 0.0,
        'comment' => 'Temperature value',
    ]);
});

it('can add date column', function (): void {
    $table = new Table('events', adapter: $this->mockAdapter);

    $column = $table->date('event_date')->notNull()->comment('Event date');

    expect($column)->toBeInstanceOf(Date::class);
    expect($column->getName())->toBe('event_date');
    expect($column->getType())->toBe('date');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'Event date',
    ]);
});

it('can add binary column with limit', function (): void {
    $table = new Table('files', adapter: $this->mockAdapter);

    $column = $table->binary('file_data', 1024)->nullable()->comment('Binary file data');

    expect($column)->toBeInstanceOf(Binary::class);
    expect($column->getName())->toBe('file_data');
    expect($column->getType())->toBe('binary');
    expect($column->getOptions())->toBe([
        'limit' => 1024,
        'null' => true,
        'comment' => 'Binary file data',
    ]);
});

it('can add id column with auto increment', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->id('user_id');

    expect($column)->toBeInstanceOf(Integer::class);
    expect($column->getName())->toBe('user_id');
    expect($column->getType())->toBe('integer');
    expect($column->getOptions())->toBe([
        'identity' => true,
        'null' => false,
        'signed' => false,
    ]);
});

it('can add timestamps columns', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $table->timestamps(true);

    $columns = $table->getColumnBuilders();

    expect(count($columns))->toBe(2);

    $createdAt = $columns[0];
    expect($createdAt)->toBeInstanceOf(Timestamp::class);
    expect($createdAt->getName())->toBe('created_at');
    expect($createdAt->getType())->toBe('timestamp');
    expect($createdAt->getOptions())->toBe([
        'timezone' => true,
        'null' => false,
        'default' => 'CURRENT_TIMESTAMP',
    ]);

    $updatedAt = $columns[1];
    expect($updatedAt)->toBeInstanceOf(Timestamp::class);
    expect($updatedAt->getName())->toBe('updated_at');
    expect($updatedAt->getType())->toBe('timestamp');
    expect($updatedAt->getOptions())->toBe([
        'timezone' => true,
        'null' => true,
        'update' => 'CURRENT_TIMESTAMP',
    ]);
});
