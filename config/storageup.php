<?php

/**
 * StorageUp Configuration File
 *
 * @author @abdansyakuro.id
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Storage Service API URL
    |--------------------------------------------------------------------------
    |
    | This is the base URL for the storage service API.
    |
    */
    'api_url' => env('STORAGE_UP_API_URL', 'https://storage.univpancasila.ac.id'),

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    |
    | Here you can configure multiple API keys for different purposes.
    | The 'default' key will be used when no specific key is specified.
    |
    */
    'api_keys' => [
        'default' => env('STORAGE_UP_API_KEY'),
        // Add more API keys as needed
        // 'custom' => env('STORAGE_UP_CUSTOM_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Configure validation rules for file uploads.
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
