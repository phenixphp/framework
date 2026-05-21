<?php

declare(strict_types=1);

namespace Phenix\Http\Client\Concerns;

use SensitiveParameter;

trait HasAuthorization
{
    public function withBasicAuth(
        string $username,
        #[SensitiveParameter]
        string $password
    ): self {
        $this->headers['Authorization'] = 'Basic ' . base64_encode("{$username}:{$password}");

        return $this;
    }

    public function withDigestAuth(
        string $username,
        #[SensitiveParameter]
        string $password
    ): self {
        $this->headers['Authorization'] = 'Digest ' . base64_encode("{$username}:{$password}");

        return $this;
    }

    public function withToken(#[SensitiveParameter] string $token, string $type = 'Bearer'): self
    {
        $this->headers['Authorization'] = "{$type} {$token}";

        return $this;
    }
}
