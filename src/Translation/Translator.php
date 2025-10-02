<?php

declare(strict_types=1);

namespace Phenix\Translation;

use Adbar\Dot;
use Countable;
use Phenix\Facades\Config;
use Phenix\Facades\File;
use Resend\Contracts\Stringable;

class Translator
{
    private Dot $catalogues;

    public function __construct(
        private string $locale,
        private string $fallbackLocale,
        array $catalogues = []
    ) {
        $this->catalogues = new Dot($catalogues);
    }

    public static function build(): self
    {
        $locale = Config::get('app.locale', 'en');
        $fallback = Config::get('app.fallback_locale', 'en');
        $catalogues = self::loadCatalogues();

        return new self($locale, $fallback, $catalogues);
    }

    /**
     * @param array<string, scalar|Stringable> $replace
     */
    public function get(string $key, array $replace = [], string|null $locale = null): array|string
    {
        $locale ??= $this->locale;
        $value = $this->catalogues->get("{$locale}.{$key}") ?? $this->catalogues->get("{$this->fallbackLocale}.{$key}");

        if ($value === null) {
            return $key;
        }

        if (is_string($value) && ! empty($replace)) {
            return $this->makeReplacements($value, $replace);
        }

        return $value;
    }

    /**
     * @param array<string, scalar|Stringable> $replace
     */
    public function choice(string $key, Countable|array|int $count, array $replace = [], string|null $locale = null): string
    {
        $line = $this->get($key, [], $locale);

        if (is_countable($count)) {
            $count = count($count);
        }

        if ($line === $key) {
            return $key; // not found
        }

        $segments = explode('|', $line);

        $index = $this->resolvePluralIndex($count, count($segments));
        $chosen = $segments[$index] ?? end($segments) ?: $key;

        if (! isset($replace['count'])) {
            $replace['count'] = $count;
        }

        return $this->makeReplacements($chosen, $replace);
    }

    public function has(string $key, string|null $locale = null): bool
    {
        $locale ??= $this->locale;

        return $this->catalogues->get("{$locale}.{$key}") !== null
            || $this->catalogues->get("{$this->fallbackLocale}.{$key}") !== null;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    private function resolvePluralIndex(int $count, int $available): int
    {
        $index = 0;

        if ($available > 1) {
            if ($count === 1) {
                $index = min(1, $available - 1);
            } elseif (! ($count === 0 && $available >= 3)) {
                $index = $available - 1;
            }
        }

        return $index;
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function loadCatalogues(): array
    {
        $path = base_path('lang');

        if (! File::exists($path)) {
            return [];
        }

        $catalogues = [];

        foreach (File::listFiles($path, false) as $localeDir) {
            $locale = basename($localeDir);
            $catalogues[$locale] = [];

            foreach (File::listFiles($localeDir) as $file) {
                $group = basename($file, '.php');

                $data = require $file;

                if (is_array($data)) {
                    $catalogues[$locale][$group] = $data;
                }
            }
        }

        return $catalogues;
    }

    /**
     * @param array<string, scalar|Stringable> $replace
     */
    private function makeReplacements(string $line, array $replace): string
    {
        if ($replace === []) {
            return $line;
        }

        $search = [];
        $replaceWith = [];

        foreach ($replace as $key => $value) {
            if ($value === null) {
                continue;
            }

            $value = (string) $value;
            $lowerKey = strtolower($key);

            // canonical form
            $search[] = ":{$lowerKey}";
            $replaceWith[] = $value;

            // Upper first
            $search[] = ':' . ucfirst($lowerKey);
            $replaceWith[] = ucfirst($value);

            // Upper case
            $search[] = ':' . strtoupper($lowerKey);
            $replaceWith[] = strtoupper($value);
        }

        return str_replace($search, $replaceWith, $line);
    }
}
