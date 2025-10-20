<?php

namespace Univpancasila\StorageUp\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Facades\StorageUp;
use Univpancasila\StorageUp\Models\StorageFile;
use Univpancasila\StorageUp\Tests\Models\User;
use Univpancasila\StorageUp\Tests\TestCase;

/**
 * StorageUp Feature Test
 *
 * @author @abdansyakuro.id
 */
class StorageUpTest extends TestCase
{
    /** @test */
    public function it_can_upload_file_via_facade()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'document-123.pdf',
                    'fileId' => 'remote-file-123',
                    'link' => 'https://storage.example.com/files/document-123.pdf',
                    'thumbnail' => 'https://storage.example.com/thumbnails/document-123.jpg',
                ],
            ], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $result = StorageUp::apiKey('test-key')
            ->for($user)
            ->collection('documents')
            ->upload($file);

        $this->assertInstanceOf(StorageFile::class, $result);
        $this->assertEquals('documents', $result->collection_name);
        $this->assertEquals('document.pdf', $result->original_name);
        $this->assertDatabaseHas('storage_files', [
            'original_name' => 'document.pdf',
            'collection_name' => 'documents',
        ]);
    }

    /** @test */
    public function it_can_retrieve_files_via_facade()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
        ]);

        $files = StorageUp::getFile($user, 'documents');

        $this->assertCount(1, $files);
        $this->assertEquals('test.pdf', $files->first()->original_name);
    }

    /** @test */
    public function it_can_delete_file_via_facade()
    {
        Http::fake([
            '*/api/v1/storage/*' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
            'file_id' => 'remote-file-123',
        ]);

        $result = StorageUp::deleteFile($file);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('storage_files', ['id' => $file->id]);
    }

    /** @test */
    public function it_handles_non_existent_files_gracefully()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageUp::getFile($user, 'non-existent-collection', true);

        $this->assertNull($file);
    }

    /** @test */
    public function it_can_chain_multiple_operations()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'file.pdf',
                    'fileId' => 'file-123',
                    'link' => 'https://storage.example.com/file.pdf',
                ],
            ], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $uploadedFile = StorageUp::apiKey('test-key')
            ->apiUrl('https://custom.storage.com')
            ->for($user)
            ->collection('documents')
            ->upload($file);

        $this->assertInstanceOf(StorageFile::class, $uploadedFile);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Api-key', 'test-key') &&
                   str_contains($request->url(), 'custom.storage.com');
        });
    }

    /** @test */
    public function it_validates_required_parameters_for_upload()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API key not set');

        StorageUp::upload($file);
    }

    /** @test */
    public function it_can_handle_uploads_with_null_collection()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'file.pdf',
                    'fileId' => 'file-123',
                    'link' => 'https://storage.example.com/file.pdf',
                ],
            ], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $result = StorageUp::apiKey('test-key')
            ->for($user)
            ->upload($file);

        $this->assertInstanceOf(StorageFile::class, $result);
        $this->assertNull($result->collection_name);
    }
}
