<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Source
     |--------------------------------------------------------------------------
     |
     | Supported: "cache" or db connection name (e.g. "mysql")
     |
     */
    'source' => env('MEANIFY_LARAVEL_PERMISSIONS_CACHE_TTL', 'cache'),

    /*
     |--------------------------------------------------------------------------
     | Cache configs
     |--------------------------------------------------------------------------
     |
     | Define driver and TTL to store data in cache
     |
     */
    'cache' => [
        'store' => env('MEANIFY_LARAVEL_PERMISSIONS_CACHE_DRIVER', env('CACHE_DRIVER', 'file')),
        'ttl'   => env('MEANIFY_LARAVEL_PERMISSIONS_CACHE_TTL', 720), //In minutes
    ],

];