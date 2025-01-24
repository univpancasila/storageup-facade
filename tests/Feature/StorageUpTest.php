<?php

namespace Univpancasila\StorageUp\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Univpancasila\StorageUp\Tests\TestCase;

/**
 * StorageUp Feature Test
 *
 * @author @abdansyakuro.id
 */

test('can upload a file', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('document.pdf', 1024);

    $result = \Univpancasila\StorageUp\Facades\StorageUp::apiKey(config('storageup.api_keys.default'))
                            ->collection('documents')
                            ->upload($file);

    expect($result)->toBeString()
        ->and(Storage::disk('local')->exists($result))->toBeTrue();
});

test('can retrieve a file', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('document.pdf', 1024);
    $path = \Univpancasila\StorageUp\Facades\StorageUp::upload($file);

    $retrievedFile = \Univpancasila\StorageUp\Facades\StorageUp::get($path);

    expect($retrievedFile)->not->toBeNull()
        ->and($retrievedFile)->toBeString();
});

test('can delete a file', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('document.pdf', 1024);
    $path = \Univpancasila\StorageUp\Facades\StorageUp::upload($file);

    $deleted = \Univpancasila\StorageUp\Facades\StorageUp::delete($path);

    expect($deleted)->toBeTrue()
        ->and(Storage::disk('local')->exists($path))->toBeFalse();
});

test('handles non-existent files gracefully', function () {
    Storage::fake('local');

    $result = \Univpancasila\StorageUp\Facades\StorageUp::get('non-existent-file.pdf');

    expect($result)->toBeNull();
});
