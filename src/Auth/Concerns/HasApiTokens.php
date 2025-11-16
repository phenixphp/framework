<?php

declare(strict_types=1);

namespace Phenix\Auth\Concerns;

use Phenix\Auth\AuthenticationToken;
use Phenix\Auth\PersonalAccessToken;
use Phenix\Auth\PersonalAccessTokenQuery;
use Phenix\Util\Date;
use Phenix\Util\Str;

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
        $expiresAt ??= Date::now()->addMinutes(config('auth.tokens.expiration', 60 * 6));

        $token = $this->token();
        $token->name = $name;
        $token->token = hash('sha256', $plainTextToken);
        $token->abilities = json_encode($abilities);
        $token->expiresAt = $expiresAt;
        $token->save();

        return new AuthenticationToken(
            token: $plainTextToken,
            expiresAt: $expiresAt
        );
    }

    public function generateTokenValue(): string
    {
        $tokenEntropy = Str::random(64);

        return sprintf(
            '%s%s%s',
            config('auth.tokens.prefix', ''),
            $tokenEntropy,
            hash('crc32b', $tokenEntropy)
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
