<?php

/**
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
];
