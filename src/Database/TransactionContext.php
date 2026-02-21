<?php

declare(strict_types=1);

namespace Phenix\Database;

use Amp\Sql\SqlTransaction;
use Fiber;
use Phenix\Database\Exceptions\TransactionException;
use WeakMap;

class TransactionContext
{
    /** @var WeakMap<Fiber, SqlTransaction>|null */
    private static WeakMap|null $contexts = null;

    private static function contexts(): WeakMap
    {
        return self::$contexts ??= new WeakMap();
    }

    public static function set(SqlTransaction $transaction): void
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null) {
            throw new TransactionException(
                'TransactionContext can only be used within a Fiber'
            );
        }

        self::contexts()[$fiber] = $transaction;
    }

    public static function get(): SqlTransaction|null
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null) {
            return null;
        }

        return self::contexts()[$fiber] ?? null;
    }

    public static function clear(): void
    {
        $fiber = Fiber::getCurrent();

        if ($fiber !== null) {
            unset(self::contexts()[$fiber]);
        }
    }

    public static function has(): bool
    {
        return self::get() !== null;
    }
}
