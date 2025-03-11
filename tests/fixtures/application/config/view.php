<?php

return [
    'path' => env('VIEW_PATH', fn () => base_path('resources/views')),

    'compiled_path' => env('VIEW_COMPILED_PATH', fn () => base_path('storage/framework/views')),
];
