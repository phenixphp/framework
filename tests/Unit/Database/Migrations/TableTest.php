<?php

declare(strict_types=1);

use Phenix\Database\Migration;
use Phenix\Database\Migrations\Columns\BigInteger;
use Phenix\Database\Migrations\Columns\Binary;
use Phenix\Database\Migrations\Columns\Bit;
use Phenix\Database\Migrations\Columns\Blob;
use Phenix\Database\Migrations\Columns\Boolean;
use Phenix\Database\Migrations\Columns\Char;
use Phenix\Database\Migrations\Columns\Cidr;
use Phenix\Database\Migrations\Columns\Date;
use Phenix\Database\Migrations\Columns\DateTime;
use Phenix\Database\Migrations\Columns\Decimal;
use Phenix\Database\Migrations\Columns\Double;
use Phenix\Database\Migrations\Columns\Enum;
use Phenix\Database\Migrations\Columns\Floating;
use Phenix\Database\Migrations\Columns\Inet;
use Phenix\Database\Migrations\Columns\Integer;
use Phenix\Database\Migrations\Columns\Interval;
use Phenix\Database\Migrations\Columns\Json;
use Phenix\Database\Migrations\Columns\JsonB;
use Phenix\Database\Migrations\Columns\MacAddr;
use Phenix\Database\Migrations\Columns\Set;
use Phenix\Database\Migrations\Columns\SmallInteger;
use Phenix\Database\Migrations\Columns\Str;
use Phenix\Database\Migrations\Columns\Text;
use Phenix\Database\Migrations\Columns\Time;
use Phenix\Database\Migrations\Columns\Timestamp;
use Phenix\Database\Migrations\Columns\Ulid;
use Phenix\Database\Migrations\Columns\UnsignedBigInteger;
use Phenix\Database\Migrations\Columns\UnsignedDecimal;
use Phenix\Database\Migrations\Columns\UnsignedFloat;
use Phenix\Database\Migrations\Columns\UnsignedInteger;
use Phenix\Database\Migrations\Columns\UnsignedSmallInteger;
use Phenix\Database\Migrations\Columns\Uuid;
use Phenix\Database\Migrations\Table;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;
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

    $table->string('username', 50)->comment('User name');

    $columns = $table->getColumnBuilders();

    expect(count($columns))->toBe(1);

    $column = $columns[0];

    expect($column)->toBeInstanceOf(Str::class);
    expect($column->getName())->toBe('username');
    expect($column->getType())->toBe('string');
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 50,
        'comment' => 'User name',
    ]);
});

it('can add integer column with options', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->integer('age', 10, false)->default(0)->comment('User age');

    expect($column)->toBeInstanceOf(Integer::class);
    expect($column->getName())->toBe('age');
    expect($column->getType())->toBe('integer');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => true,
        'limit' => 10,
        'default' => 0,
        'comment' => 'User age',
    ]);
});

it('can add big integer column with identity', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->bigInteger('id', true)->comment('Primary key');

    expect($column)->toBeInstanceOf(BigInteger::class);
    expect($column->getName())->toBe('id');
    expect($column->getType())->toBe('biginteger');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => true,
        'identity' => true,
        'comment' => 'Primary key',
    ]);
});

it('can add unsigned integer column with options', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->unsignedInteger('count', 10, false)->default(0)->comment('Item count');

    expect($column)->toBeInstanceOf(UnsignedInteger::class);
    expect($column->getName())->toBe('count');
    expect($column->getType())->toBe('integer');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
        'limit' => 10,
        'default' => 0,
        'comment' => 'Item count',
    ]);
});

it('can add unsigned big integer column with identity', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->unsignedBigInteger('id', true)->comment('Primary key');

    expect($column)->toBeInstanceOf(UnsignedBigInteger::class);
    expect($column->getName())->toBe('id');
    expect($column->getType())->toBe('biginteger');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
        'identity' => true,
        'comment' => 'Primary key',
    ]);
});

it('can add small integer column', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->smallInteger('status', false)->default(1);

    expect($column)->toBeInstanceOf(SmallInteger::class);
    expect($column->getName())->toBe('status');
    expect($column->getType())->toBe('smallinteger');
    expect($column->getOptions())->toBe([
        'null' => false,
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
        'null' => true,
        'limit' => 1000,
        'comment' => 'Post content',
    ]);
});

it('can add boolean column', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->boolean('is_active')->default(true)->comment('User status');

    expect($column)->toBeInstanceOf(Boolean::class);
    expect($column->getName())->toBe('is_active');
    expect($column->getType())->toBe('boolean');
    expect($column->getOptions())->toBe([
        'null' => false,
        'default' => true,
        'comment' => 'User status',
    ]);
});

it('can add decimal column with precision and scale', function (): void {
    $table = new Table('products', adapter: $this->mockAdapter);

    $column = $table->decimal('price', 8, 2)->default(0.00)->comment('Product price');

    expect($column)->toBeInstanceOf(Decimal::class);
    expect($column->getName())->toBe('price');
    expect($column->getType())->toBe('decimal');
    expect($column->getOptions())->toBe([
        'null' => false,
        'precision' => 8,
        'scale' => 2,
        'signed' => true,
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

    $column = $table->timestamp('created_at', true)->currentTimestamp();

    expect($column)->toBeInstanceOf(Timestamp::class);
    expect($column->getName())->toBe('created_at');
    expect($column->getType())->toBe('timestamp');
    expect($column->getOptions())->toBe([
        'null' => false,
        'timezone' => true,
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

    $column = $table->uuid('uuid')->comment('Unique identifier');

    expect($column)->toBeInstanceOf(Uuid::class);
    expect($column->getName())->toBe('uuid');
    expect($column->getType())->toBe('uuid');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'Unique identifier',
    ]);
});

it('can add ulid column', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->ulid('ulid')->comment('ULID identifier');

    expect($column)->toBeInstanceOf(Ulid::class);
    expect($column->getName())->toBe('ulid');
    expect($column->getType())->toBe('string');
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 26,
        'comment' => 'ULID identifier',
    ]);
});

it('can add enum column with values', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->enum('role', ['admin', 'user', 'guest'])->default('user')->comment('User role');

    expect($column)->toBeInstanceOf(Enum::class);
    expect($column->getName())->toBe('role');
    expect($column->getType())->toBe('enum');
    expect($column->getOptions())->toBe([
        'null' => false,
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
        'null' => false,
        'default' => 0.0,
        'comment' => 'Temperature value',
    ]);
});

it('can add date column', function (): void {
    $table = new Table('events', adapter: $this->mockAdapter);

    $column = $table->date('event_date')->comment('Event date');

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
        'null' => true,
        'limit' => 1024,
        'comment' => 'Binary file data',
    ]);
});

it('can add id column with auto increment', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->id('user_id');

    expect($column)->toBeInstanceOf(UnsignedInteger::class);
    expect($column->getName())->toBe('user_id');
    expect($column->getType())->toBe('integer');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
        'identity' => true,
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
        'null' => true,
        'timezone' => true,
        'default' => 'CURRENT_TIMESTAMP',
    ]);

    $updatedAt = $columns[1];
    expect($updatedAt)->toBeInstanceOf(Timestamp::class);
    expect($updatedAt->getName())->toBe('updated_at');
    expect($updatedAt->getType())->toBe('timestamp');
    expect($updatedAt->getOptions())->toBe([
        'null' => true,
        'timezone' => true,
        'update' => 'CURRENT_TIMESTAMP',
    ]);
});

it('can add unsigned decimal column with precision and scale', function (): void {
    $table = new Table('products', adapter: $this->mockAdapter);

    $column = $table->unsignedDecimal('price', 8, 2)->default(0.00)->comment('Product price');

    expect($column)->toBeInstanceOf(UnsignedDecimal::class);
    expect($column->getName())->toBe('price');
    expect($column->getType())->toBe('decimal');
    expect($column->getOptions())->toBe([
        'null' => false,
        'precision' => 8,
        'scale' => 2,
        'signed' => false,
        'default' => 0.00,
        'comment' => 'Product price',
    ]);
});

it('can add unsigned small integer column', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->unsignedSmallInteger('status', false)->default(1)->comment('User status');

    expect($column)->toBeInstanceOf(UnsignedSmallInteger::class);
    expect($column->getName())->toBe('status');
    expect($column->getType())->toBe('smallinteger');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
        'default' => 1,
        'comment' => 'User status',
    ]);
});

it('can add unsigned small integer column with identity', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->unsignedSmallInteger('id', true)->comment('Primary key');

    expect($column)->toBeInstanceOf(UnsignedSmallInteger::class);
    expect($column->getName())->toBe('id');
    expect($column->getType())->toBe('smallinteger');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
        'identity' => true,
        'comment' => 'Primary key',
    ]);
});

it('can add unsigned float column', function (): void {
    $table = new Table('measurements', adapter: $this->mockAdapter);

    $column = $table->unsignedFloat('temperature')->default(0.0)->comment('Temperature value');

    expect($column)->toBeInstanceOf(UnsignedFloat::class);
    expect($column->getName())->toBe('temperature');
    expect($column->getType())->toBe('float');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
        'default' => 0.0,
        'comment' => 'Temperature value',
    ]);
});

it('can change adapter for columns', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->string('name', 100);

    expect($column->getAdapter())->toBe($this->mockAdapter);

    $mysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $column->setAdapter($mysqlAdapter);
    expect($column->isMysql())->toBeTrue();
    expect($column->isPostgres())->toBeFalse();
    expect($column->isSQLite())->toBeFalse();
    expect($column->isSqlServer())->toBeFalse();

    $postgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $column->setAdapter($postgresAdapter);

    expect($column->isPostgres())->toBeTrue();
    expect($column->isMysql())->toBeFalse();
});

it('can add char column with limit', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->char('code', 10)->comment('Product code');

    expect($column)->toBeInstanceOf(Char::class);
    expect($column->getName())->toBe('code');
    expect($column->getType())->toBe('char');
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 10,
        'comment' => 'Product code',
    ]);
});

it('can add time column', function (): void {
    $table = new Table('events', adapter: $this->mockAdapter);

    $column = $table->time('start_time')->comment('Event start time');

    expect($column)->toBeInstanceOf(Time::class);
    expect($column->getName())->toBe('start_time');
    expect($column->getType())->toBe('time');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'Event start time',
    ]);
});

it('can add double column', function (): void {
    $table = new Table('measurements', adapter: $this->mockAdapter);

    $column = $table->double('value')->default(0.0)->comment('Measurement value');

    expect($column)->toBeInstanceOf(Double::class);
    expect($column->getName())->toBe('value');
    expect($column->getType())->toBe('double');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => true,
        'default' => 0.0,
        'comment' => 'Measurement value',
    ]);
});

it('can add blob column', function (): void {
    $table = new Table('files', adapter: $this->mockAdapter);

    $column = $table->blob('data')->comment('File data');

    expect($column)->toBeInstanceOf(Blob::class);
    expect($column->getName())->toBe('data');
    expect($column->getType())->toBe('blob');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'File data',
    ]);
});

it('can add set column with values', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->set('permissions', ['read', 'write', 'execute'])->comment('User permissions');

    expect($column)->toBeInstanceOf(Set::class);
    expect($column->getName())->toBe('permissions');
    expect($column->getType())->toBe('set');
    expect($column->getOptions())->toBe([
        'null' => false,
        'values' => ['read', 'write', 'execute'],
        'comment' => 'User permissions',
    ]);
});

it('can add bit column', function (): void {
    $table = new Table('flags', adapter: $this->mockAdapter);

    $column = $table->bit('flags', 8)->comment('Status flags');

    expect($column)->toBeInstanceOf(Bit::class);
    expect($column->getName())->toBe('flags');
    expect($column->getType())->toBe('bit');
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 8,
        'comment' => 'Status flags',
    ]);
});

it('can add jsonb column (PostgreSQL)', function (): void {
    $table = new Table('data', adapter: $this->mockAdapter);

    $column = $table->jsonb('metadata')->nullable()->comment('JSON metadata');

    expect($column)->toBeInstanceOf(JsonB::class);
    expect($column->getName())->toBe('metadata');
    expect($column->getType())->toBe('jsonb');
    expect($column->getOptions())->toBe([
        'null' => true,
        'comment' => 'JSON metadata',
    ]);
});

it('can add inet column (PostgreSQL)', function (): void {
    $table = new Table('connections', adapter: $this->mockAdapter);

    $column = $table->inet('ip_address')->comment('IP address');

    expect($column)->toBeInstanceOf(Inet::class);
    expect($column->getName())->toBe('ip_address');
    expect($column->getType())->toBe('inet');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'IP address',
    ]);
});

it('can add cidr column (PostgreSQL)', function (): void {
    $table = new Table('networks', adapter: $this->mockAdapter);

    $column = $table->cidr('network')->comment('Network CIDR');

    expect($column)->toBeInstanceOf(Cidr::class);
    expect($column->getName())->toBe('network');
    expect($column->getType())->toBe('cidr');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'Network CIDR',
    ]);
});

it('can add macaddr column (PostgreSQL)', function (): void {
    $table = new Table('devices', adapter: $this->mockAdapter);

    $column = $table->macaddr('mac_address')->comment('MAC address');

    expect($column)->toBeInstanceOf(MacAddr::class);
    expect($column->getName())->toBe('mac_address');
    expect($column->getType())->toBe('macaddr');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'MAC address',
    ]);
});

it('can add interval column (PostgreSQL)', function (): void {
    $table = new Table('events', adapter: $this->mockAdapter);

    $column = $table->interval('duration')->comment('Event duration');

    expect($column)->toBeInstanceOf(Interval::class);
    expect($column->getName())->toBe('duration');
    expect($column->getType())->toBe('interval');
    expect($column->getOptions())->toBe([
        'null' => false,
        'comment' => 'Event duration',
    ]);
});

it('can use after method to position column', function (): void {
    $table = new Table('users', adapter: $this->mockAdapter);

    $column = $table->string('email')->after('username');

    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 255,
        'after' => 'username',
    ]);
});

it('can use first method to position column at beginning', function (): void {
    $mysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $mysqlAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('users', adapter: $mysqlAdapter);

    $column = $table->string('id')->setAdapter($mysqlAdapter)->first();

    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 255,
        'after' => MysqlAdapter::FIRST,
    ]);
});

it('can set collation for MySQL columns', function (): void {
    $mysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $mysqlAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('users', adapter: $mysqlAdapter);

    $column = $table->string('name')->setAdapter($mysqlAdapter)->collation('utf8mb4_unicode_ci');

    expect($column->isMysql())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 255,
        'collation' => 'utf8mb4_unicode_ci',
    ]);
});

it('sets collation for non-MySQL adapters (Str class behavior)', function (): void {
    $postgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $postgresAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $postgresAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $postgresAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('users', adapter: $postgresAdapter);

    $column = $table->string('name');
    $column->setAdapter($postgresAdapter);
    $column->collation('utf8mb4_unicode_ci');

    expect($column->isPostgres())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 255,
        'collation' => 'utf8mb4_unicode_ci',
    ]);
});

it('can set encoding for MySQL columns', function (): void {
    $mysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $mysqlAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('users', adapter: $mysqlAdapter);

    $column = $table->string('name');
    $column->setAdapter($mysqlAdapter);
    $column->encoding('utf8mb4');

    expect($column->isMysql())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 255,
        'encoding' => 'utf8mb4',
    ]);
});

it('sets encoding for non-MySQL adapters (Str class behavior)', function (): void {
    $postgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $postgresAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $postgresAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $postgresAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('users', adapter: $postgresAdapter);

    $column = $table->string('name');
    $column->setAdapter($postgresAdapter);
    $column->encoding('utf8mb4');

    expect($column->isPostgres())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 255,
        'encoding' => 'utf8mb4',
    ]);
});

it('can set timezone for PostgreSQL columns', function (): void {
    $postgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $postgresAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $postgresAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $postgresAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('events', adapter: $postgresAdapter);

    $column = $table->timestamp('created_at');
    $column->setAdapter($postgresAdapter);
    $column->timezone(true);

    expect($column->isPostgres())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'timezone' => true,
    ]);
});

it('can set timezone to false for PostgreSQL columns', function (): void {
    $postgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $postgresAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $postgresAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $postgresAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('events', adapter: $postgresAdapter);

    $column = $table->timestamp('created_at');
    $column->setAdapter($postgresAdapter);
    $column->timezone(false);

    expect($column->isPostgres())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'timezone' => false,
    ]);
});

it('sets timezone for non-PostgreSQL adapters (Timestamp class behavior)', function (): void {
    $mysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $mysqlAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('events', adapter: $mysqlAdapter);

    $column = $table->timestamp('created_at');
    $column->setAdapter($mysqlAdapter);
    $column->timezone(true);

    expect($column->isMysql())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'timezone' => true,
    ]);
});

it('can set update trigger for MySQL columns', function (): void {
    $mysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $mysqlAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $mysqlAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('users', adapter: $mysqlAdapter);

    $column = $table->timestamp('updated_at');
    $column->setAdapter($mysqlAdapter);
    $column->update('CURRENT_TIMESTAMP');

    expect($column->isMysql())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'update' => 'CURRENT_TIMESTAMP',
    ]);
});

it('sets update trigger for non-MySQL adapters (Timestamp class behavior)', function (): void {
    $postgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $postgresAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $postgresAdapter->expects($this->any())
        ->method('getColumnTypes')
        ->willReturn(['string', 'integer', 'boolean', 'text', 'datetime', 'timestamp']);

    $postgresAdapter->expects($this->any())
        ->method('getColumnForType')
        ->willReturnCallback(function (string $columnName, string $type, array $options): Column {
            $column = new Column();
            $column->setName($columnName);
            $column->setType($type);
            $column->setOptions($options);

            return $column;
        });

    $table = new Table('users', adapter: $postgresAdapter);

    $column = $table->timestamp('updated_at');
    $column->setAdapter($postgresAdapter);
    $column->update('CURRENT_TIMESTAMP');

    expect($column->isPostgres())->toBeTrue();
    expect($column->getOptions())->toBe([
        'null' => false,
        'update' => 'CURRENT_TIMESTAMP',
    ]);
});

it('returns new table for migrations', function (): void {
    $migration = new class ('local', 1) extends Migration {};
    $migration->setAdapter($this->mockAdapter);

    expect($migration->table('users'))->toBeInstanceOf(Table::class);
});

it('can add foreign key using table methods', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $table->string('title');
    $table->foreignKey('user_id', 'users', 'id', ['delete' => 'CASCADE']);

    $columns = $table->getColumnBuilders();
    $foreignKeys = $table->getForeignKeyBuilders();

    expect(count($columns))->toBe(1);
    expect(count($foreignKeys))->toBe(1);

    $foreignKey = $foreignKeys[0];
    expect($foreignKey->getColumns())->toBe('user_id');
    expect($foreignKey->getReferencedTable())->toBe('users');
    expect($foreignKey->getReferencedColumns())->toBe('id');
    expect($foreignKey->getOptions()['delete'])->toBe('CASCADE');
});

it('can add foreign key using fluent interface', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $table->string('title');
    $table->foreign('author_id')->references('id')->on('authors')->onDelete('SET_NULL')->constraint('fk_post_author');

    $foreignKeys = $table->getForeignKeyBuilders();

    expect(count($foreignKeys))->toBe(1);

    $foreignKey = $foreignKeys[0];
    expect($foreignKey->getColumns())->toBe('author_id');
    expect($foreignKey->getReferencedTable())->toBe('authors');
    expect($foreignKey->getReferencedColumns())->toBe('id');
    expect($foreignKey->getOptions()['delete'])->toBe('SET_NULL');
    expect($foreignKey->getOptions()['constraint'])->toBe('fk_post_author');
});
