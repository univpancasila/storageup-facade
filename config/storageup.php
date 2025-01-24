<?php

/**
 * StorageUp Configuration File
 *
 * @author @abdansyakuro.id
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage disk that will be used by the
    | StorageUp facade. You may change this to any of the disks defined in
    | your filesystem configuration file.
    |
    */
    'default_disk' => env('STORAGE_UP_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | File Validation
    |--------------------------------------------------------------------------
    |
    | Here you may configure the default file validation rules that will be
    | applied when uploading files through StorageUp.
    |
    */
    'validation' => [
        'max_size' => env('STORAGE_UP_MAX_SIZE', 10240), // 10MB in kilobytes
        'allowed_mimes' => [
            'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx',
            'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Generation
    |--------------------------------------------------------------------------
    |
    | Configure how file paths should be generated when storing files.
    |
    */
    'path' => [
        'prefix' => env('STORAGE_UP_PATH_PREFIX', ''),
        'use_original_name' => env('STORAGE_UP_USE_ORIGINAL_NAME', false),
        'hash_length' => env('STORAGE_UP_HASH_LENGTH', 40),
    ],
];
