<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Runtime\Facade;

/**
 * @method static \Phenix\Database\QueryBuilder connection(string $connection)
 * @method static \Phenix\Database\QueryBuilder table(string $table)
 * @method static \Phenix\Database\QueryBuilder from(\Closure|string $table)
 * @method static \Phenix\Database\QueryBuilder select(array $columns)
 * @method static \Phenix\Database\QueryBuilder selectAllColumns()
 * @method static \Amp\Sql\SqlResult unprepared(string $sql)
 * @method static mixed transaction(\Closure(\Phenix\Database\TransactionManager):mixed $callback)
 * @method static \Phenix\Database\TransactionManager beginTransaction()
 * @method static void commit()
 * @method static void rollBack()
 * @method static \Amp\Sql\SqlTransaction|null getTransaction()
 * @method static \Phenix\Database\QueryBuilder setTransaction(\Amp\Sql\SqlTransaction $transaction)
 *
 * @see \Phenix\Database\QueryBuilder
 */
class DB extends Facade
{
    public static function getKeyName(): string
    {
        return \Phenix\Database\QueryBuilder::class;
    }
}
