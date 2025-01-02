<?php

declare(strict_types=1);

namespace Phenix\Session;

use Amp\Http\Cookie\CookieAttributes;
use Phenix\Util\Date;

class Cookie
{
    public function __construct(
        private Config $config,
        private string $host
    ) {
    }

    public function build(): CookieAttributes
    {
        $cookieAttributes = CookieAttributes::default()
            ->withDomain($this->config->domain() ?? $this->host)
            ->withExpiry(Date::now()->addMinutes($this->config->lifetime())->toDateTime())
            ->withSameSite($this->config->sameSite()->value)
            ->withPath($this->config->path());

        if ($this->config->httpOnly()) {
            $cookieAttributes->withHttpOnly();
        }

        if ($this->config->secure()) {
            $cookieAttributes->withSecure();
        }

        return $cookieAttributes;
    }
}
