<?php

declare(strict_types=1);

namespace Phenix\Auth\Concerns;

use Phenix\Auth\AuthenticationToken;
use Phenix\Auth\Events\TokenCreated;
use Phenix\Auth\PersonalAccessToken;
use Phenix\Auth\PersonalAccessTokenQuery;
use Phenix\Facades\Event;
use Phenix\Util\Date;

use function sprintf;

trait HasApiTokens
{
    protected PersonalAccessToken|null $accessToken = null;

    public function token(): PersonalAccessToken
    {
        $model = new (config('auth.tokens.model'));
        $model->tokenableType = static::class;
        $model->tokenableId = $this->getKey();

        return $model;
    }

    public function tokens(): PersonalAccessTokenQuery
    {
        $model = new (config('auth.tokens.model'));

        return $model::query()
            ->whereEqual('tokenable_type', static::class)
            ->whereEqual('tokenable_id', $this->getKey());
    }

    public function createToken(string $name, array $abilities = ['*'], Date|null $expiresAt = null): AuthenticationToken
    {
        $plainTextToken = $this->generateTokenValue();
        $expiresAt ??= Date::now()->addMinutes(config('auth.tokens.expiration', 60 * 12));

        $token = $this->token();
        $token->name = $name;
        $token->token = hash('sha256', $plainTextToken);
        $token->abilities = json_encode($abilities);
        $token->expiresAt = $expiresAt;
        $token->save();

        Event::emitAsync(new TokenCreated($token));

        return new AuthenticationToken(
            token: $plainTextToken,
            expiresAt: $expiresAt
        );
    }

    public function generateTokenValue(): string
    {
        $entropy = bin2hex(random_bytes(32));
        $checksum = substr(hash('sha256', $entropy), 0, 8);

        return sprintf(
            '%s%s_%s',
            config('auth.tokens.prefix', ''),
            $entropy,
            $checksum
        );
    }

    public function currentAccessToken(): PersonalAccessToken|null
    {
        return $this->accessToken;
    }

    public function withAccessToken(PersonalAccessToken $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
