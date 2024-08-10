<?php

declare(strict_types=1);

namespace Phenix\Http\Requests\Concerns;

use Amp\Http\Cookie\RequestCookie;

trait HasCookies
{
    public function getCookies(): array
    {
        return $this->request->getCookies();
    }

    public function getCookie(string $name): RequestCookie|null
    {
        return $this->request->getCookie($name);
    }

    public function setCookie(RequestCookie $cookie): void
    {
        $this->request->setCookie($cookie);
    }

    public function removeCookie(string $name): void
    {
        $this->request->removeCookie($name);
    }
}
