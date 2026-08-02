<?php

return [
    /*
     * CACHE_STORE is the Laravel 11 name; CACHE_DRIVER is kept as a fallback so
     * existing deployments keep working. Rate limiting lives in this store, so
     * picking the wrong one has teeth: on `file` the counters survive across
     * processes, which is right in production and wrong in a test run.
     */
    'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE', 'cache_locks'),
        ],
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'nuvo_cache_'),
];
