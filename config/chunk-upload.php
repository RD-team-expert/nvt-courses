<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chunk Upload Configuration
    |--------------------------------------------------------------------------
    */

    'chunk' => [
        'name' => [
            'use' => [
                'session' => true, // Use session ID in chunk names
                'browser' => false, // Don't use browser info in chunk names
            ],
        ],
    ],

    'storage' => [
        'disk' => 'local', // Use local disk (storage/app)
        'chunks' => 'chunks', // Chunks folder: storage/app/chunks
        'max_size' => 524288000, // 500MB max file size (500 * 1024 * 1024)
    ],

    'clear' => [
        'timestamp' => '-3 HOURS', // Clear chunks older than 3 hours
        'schedule' => [
            'enabled' => true,
            'cron' => '25 * * * *', // Run cleanup every hour at minute 25
        ],
    ],

    'chunk_size' => 2097152, // 2MB chunks (2 * 1024 * 1024 bytes)
];
