<?php

declare(strict_types=1);

return [
    'users' => [
        'model' => Phenix\Auth\User::class,
    ],
    'tokens' => [
        'model' => Phenix\Auth\PersonalAccessToken::class,
        'prefix' => '',
        'expiration' => 60 * 12, // in minutes
    ],
    'otp' => [
        'expiration' => 10, // in minutes
    ],
];
