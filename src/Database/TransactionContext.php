<?php

declare(strict_types=1);

namespace Phenix\Database;

use Amp\Sql\SqlTransaction;
use Fiber;
use Phenix\Database\Exceptions\TransactionException;
use WeakMap;

class TransactionContext
{
    /** @var WeakMap<Fiber, TransactionChain>|null */
    private static WeakMap|null $contexts = null;

    public static function push(SqlTransaction $transaction): void
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null) {
            throw new TransactionException(
                'TransactionContext can only be used within a Fiber'
            );
        }

        if (! self::contexts()->offsetExists($fiber)) {
            self::contexts()->offsetSet($fiber, new TransactionChain());
        }

        self::contexts()->offsetGet($fiber)->push($transaction);
    }

    public static function pop(): void
    {
        $fiber = Fiber::getCurrent();

        if ($fiber !== null && self::contexts()->offsetExists($fiber)) {
            self::contexts()->offsetGet($fiber)->pop();

            if (self::contexts()->offsetGet($fiber)->isEmpty()) {
                self::contexts()->offsetUnset($fiber);
            }
        }
    }

    public static function get(): SqlTransaction|null
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null) {
            return null;
        }

        if (! self::contexts()->offsetExists($fiber)) {
            return null;
        }

        return self::contexts()->offsetGet($fiber)->current()?->transaction;
    }

    public static function getCurrentNode(): TransactionNode|null
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null || ! self::contexts()->offsetExists($fiber)) {
            return null;
        }

        return self::contexts()->offsetGet($fiber)->current();
    }

    public static function getRoot(): TransactionNode|null
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null || ! self::contexts()->offsetExists($fiber)) {
            return null;
        }

        return self::contexts()->offsetGet($fiber)->root();
    }

    public static function depth(): int
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null || ! self::contexts()->offsetExists($fiber)) {
            return 0;
        }

        return self::contexts()->offsetGet($fiber)->depth();
    }

    public static function has(): bool
    {
        return self::get() !== null;
    }

    public static function getChain(): TransactionChain|null
    {
        $fiber = Fiber::getCurrent();

        if ($fiber === null) {
            return null;
        }

        return self::contexts()->offsetGet($fiber) ?? null;
    }

    private static function contexts(): WeakMap
    {
        return self::$contexts ??= new WeakMap();
    }
}
