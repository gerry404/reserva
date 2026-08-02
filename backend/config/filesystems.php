<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
     * Where merchant uploads (logos, cover images, service photos) live.
     *
     * Named separately from `default` because it is the one disk whose contents
     * must outlive a container. On Render the filesystem is ephemeral, so a
     * local disk quietly loses every image on the next deploy — set
     * UPLOAD_DISK=s3 (S3, Cloudflare R2, any S3-compatible bucket) in
     * production. Code should reach for it through App\Support\Uploads.
     */
    'uploads' => env('UPLOAD_DISK', 'public'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'auto'),
            'bucket' => env('AWS_BUCKET'),
            // R2 and most non-AWS providers need an explicit endpoint and
            // path-style addressing.
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            // Public bucket or CDN hostname: what ends up in an <img src>.
            'url' => env('AWS_URL'),
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
