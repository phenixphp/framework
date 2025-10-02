<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Runtime\Facade;
use Phenix\Translation\Translator as TranslationManager;

/**
 * @method static array|string get(string $key, array $replace = [], string|null $locale = null)
 * @method static string choice(string $key, int|array|Countable $count, array $replace = [], string|null $locale = null)
 * @method static bool has(string $key, string|null $locale = null)
 * @method static string getLocale()
 * @method static void setLocale(string $locale)
 *
 * @see \Phenix\Translation\Translator
 */
class Translator extends Facade
{
    protected static function getKeyName(): string
    {
        return TranslationManager::class;
    }
}
