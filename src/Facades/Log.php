<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\HigherOrderMessage;
use Phenix\App;
use Phenix\Runtime\Facade;
use Phenix\Testing\Mockery;

/**
 * @method static void info(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void emergency(string $message, array $context = [])
 *
 * @see \Phenix\Runtime\Log
 */
class Log extends Facade
{
    public static function getKeyName(): string
    {
        return \Phenix\Runtime\Log::class;
    }

    public static function shouldReceive(string $method): Expectation|ExpectationInterface|HigherOrderMessage
    {
        $mock = Mockery::mock(self::getKeyName())->shouldAllowMockingProtectedMethods()->makePartial();

        App::fake(self::getKeyName(), $mock);

        return $mock->shouldReceive($method);
    }
}
