<?php

declare(strict_types=1);

use Phenix\Http\Headers\CrossOriginOpenerPolicy;
use Phenix\Http\Headers\CrossOriginResourcePolicy;
use Phenix\Http\Headers\ReferrerPolicy;
use Phenix\Http\Headers\StrictTransportSecurity;
use Phenix\Http\Headers\XContentTypeOptions;
use Phenix\Http\Headers\XDnsPrefetchControl;
use Phenix\Http\Headers\XFrameOptions;

return [
    'security' => [
        'headers' => [
            XDnsPrefetchControl::class,
            XFrameOptions::class,
            StrictTransportSecurity::class,
            XContentTypeOptions::class,
            ReferrerPolicy::class,
            CrossOriginResourcePolicy::class,
            CrossOriginOpenerPolicy::class,
        ],
    ],
];
