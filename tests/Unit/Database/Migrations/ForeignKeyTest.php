<?php

declare(strict_types=1);

use Phenix\Database\Constants\ColumnAction;
use Phenix\Database\Migrations\ForeignKey;
use Phenix\Database\Migrations\Table;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;

beforeEach(function (): void {
    $this->mockAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

    $this->mockAdapter->expects($this->any())
        ->method('hasTable')
        ->willReturn(false);

    $this->mockAdapter->expects($this->any())
        ->method('isValidColumnType')
        ->willReturn(true);

    $this->mockAdapter->expects($this->any())
        ->method('execute')
        ->willReturnCallback(function ($sql) {
            return true;
        });
});

it('can create a simple foreign key', function (): void {
    $foreignKey = new ForeignKey('user_id', 'users', 'id');

    expect($foreignKey->getColumns())->toEqual('user_id');
    expect($foreignKey->getReferencedTable())->toEqual('users');
    expect($foreignKey->getReferencedColumns())->toEqual('id');
    expect($foreignKey->getOptions())->toEqual([]);
});

it('can create a foreign key with multiple columns', function (): void {
    $foreignKey = new ForeignKey(['user_id', 'role_id'], 'user_roles', ['user_id', 'role_id']);

    expect($foreignKey->getColumns())->toEqual(['user_id', 'role_id']);
    expect($foreignKey->getReferencedTable())->toEqual('user_roles');
    expect($foreignKey->getReferencedColumns())->toEqual(['user_id', 'role_id']);
});

it('can set delete and update actions with strings', function (): void {
    $foreignKey = new ForeignKey('user_id', 'users', 'id');
    $foreignKey->onDelete('CASCADE')->onUpdate('SET_NULL');

    $options = $foreignKey->getOptions();
    expect($options['delete'])->toEqual('CASCADE');
    expect($options['update'])->toEqual('SET_NULL');
});

it('can set constraint name', function (): void {
    $foreignKey = new ForeignKey('user_id', 'users', 'id');
    $foreignKey->constraint('fk_posts_user_id');

    expect($foreignKey->getOptions()['constraint'])->toEqual('fk_posts_user_id');
});

it('can use fluent interface with references and on', function (): void {
    $foreignKey = new ForeignKey('user_id');
    $foreignKey->references('id')->on('users');

    expect($foreignKey->getColumns())->toEqual('user_id');
    expect($foreignKey->getReferencedTable())->toEqual('users');
    expect($foreignKey->getReferencedColumns())->toEqual('id');
});

it('can set deferrable option for PostgreSQL', function (): void {
    $foreignKey = new ForeignKey('user_id', 'users', 'id');

    $postgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $foreignKey->setAdapter($postgresAdapter);
    $foreignKey->deferrable('IMMEDIATE');

    expect($foreignKey->getOptions()['deferrable'])->toEqual('IMMEDIATE');
    expect($foreignKey->isPostgres())->toBeTrue();
});

it('ignores deferrable option for non-PostgreSQL adapters', function (): void {
    $foreignKey = new ForeignKey('user_id', 'users', 'id');

    $mysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $foreignKey->setAdapter($mysqlAdapter);
    $foreignKey->deferrable('IMMEDIATE');

    expect($foreignKey->getOptions())->not->toHaveKey('deferrable');
    expect($foreignKey->isMysql())->toBeTrue();
});

it('can add foreign key to table using foreignKey method', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $foreignKey = $table->foreignKey('user_id', 'users', 'id', ['delete' => 'CASCADE']);

    expect($foreignKey)->toBeInstanceOf(ForeignKey::class);
    expect($foreignKey->getColumns())->toEqual('user_id');
    expect($foreignKey->getReferencedTable())->toEqual('users');
    expect($foreignKey->getReferencedColumns())->toEqual('id');
    expect($foreignKey->getOptions()['delete'])->toEqual('CASCADE');

    $foreignKeys = $table->getForeignKeyBuilders();
    expect(count($foreignKeys))->toEqual(1);
    expect($foreignKeys[0])->toEqual($foreignKey);
});

it('can add foreign key to table using foreign method with fluent interface', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $foreignKey = $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');

    expect($foreignKey->getColumns())->toEqual('user_id');
    expect($foreignKey->getReferencedTable())->toEqual('users');
    expect($foreignKey->getReferencedColumns())->toEqual('id');
    expect($foreignKey->getOptions()['delete'])->toEqual('CASCADE');

    $foreignKeys = $table->getForeignKeyBuilders();
    expect(count($foreignKeys))->toEqual(1);
    expect($foreignKeys[0])->toEqual($foreignKey);
});

it('can create foreign key with multiple columns using fluent interface', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $foreignKey = $table->foreign(['user_id', 'role_id'])
        ->references(['user_id', 'role_id'])
        ->on('user_roles')
        ->onDelete(ColumnAction::NO_ACTION)
        ->onUpdate(ColumnAction::NO_ACTION)
        ->constraint('fk_posts_user_role');

    expect($foreignKey->getColumns())->toEqual(['user_id', 'role_id']);
    expect($foreignKey->getReferencedTable())->toEqual('user_roles');
    expect($foreignKey->getReferencedColumns())->toEqual(['user_id', 'role_id']);
    expect($foreignKey->getOptions())->toEqual([
        'delete' => 'NO_ACTION',
        'update' => 'NO_ACTION',
        'constraint' => 'fk_posts_user_role',
    ]);
});

it('sets adapter correctly when added to table', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $foreignKey = $table->foreignKey('user_id', 'users');

    expect($foreignKey->getAdapter())->not->toBeNull();
});

it('can use ColumnAction enum constants for onDelete and onUpdate', function (): void {
    $foreignKey = new ForeignKey('user_id', 'users', 'id');
    $foreignKey->onDelete(ColumnAction::CASCADE)->onUpdate(ColumnAction::SET_NULL);

    $options = $foreignKey->getOptions();
    expect($options['delete'])->toEqual('CASCADE');
    expect($options['update'])->toEqual('SET_NULL');
});

it('can use mixed string and ColumnAction enum parameters', function (): void {
    $foreignKey = new ForeignKey('user_id', 'users', 'id');
    $foreignKey->onDelete('RESTRICT')->onUpdate(ColumnAction::NO_ACTION);

    $options = $foreignKey->getOptions();
    expect($options['delete'])->toEqual('RESTRICT');
    expect($options['update'])->toEqual('NO_ACTION');
});

it('can use ColumnAction enum in fluent interface', function (): void {
    $table = new Table('posts', adapter: $this->mockAdapter);

    $foreignKey = $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete(ColumnAction::CASCADE)
        ->onUpdate(ColumnAction::RESTRICT);

    expect($foreignKey->getOptions()['delete'])->toEqual('CASCADE');
    expect($foreignKey->getOptions()['update'])->toEqual('RESTRICT');
});
