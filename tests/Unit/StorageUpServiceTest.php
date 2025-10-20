<?php

namespace Univpancasila\StorageUp\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Facades\StorageUp;
use Univpancasila\StorageUp\Models\StorageFile;
use Univpancasila\StorageUp\StorageUpService;
use Univpancasila\StorageUp\Tests\Models\User;
use Univpancasila\StorageUp\Tests\TestCase;

/**
 * Unit tests for StorageUpService
 *
 * @author @abdansyakuro.id
 */
class StorageUpServiceTest extends TestCase
{
    /** @test */
    public function it_can_set_api_key()
    {
        $service = new StorageUpService();
        $result = $service->apiKey('test-key-123');

        $this->assertInstanceOf(StorageUpService::class, $result);
        $this->assertEquals($service, $result);
    }

    /** @test */
    public function it_can_set_api_url()
    {
        $service = new StorageUpService();
        $result = $service->apiUrl('https://custom.api.url');

        $this->assertInstanceOf(StorageUpService::class, $result);
        $this->assertEquals($service, $result);
    }

    /** @test */
    public function it_can_set_collection_name()
    {
        $service = new StorageUpService();
        $result = $service->collection('documents');

        $this->assertInstanceOf(StorageUpService::class, $result);
        $this->assertEquals($service, $result);
    }

    /** @test */
    public function it_can_set_model_instance()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $service = new StorageUpService();
        $result = $service->for($user);

        $this->assertInstanceOf(StorageUpService::class, $result);
        $this->assertEquals($service, $result);
    }

    /** @test */
    public function it_supports_fluent_interface()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $service = new StorageUpService();

        $result = $service
            ->apiKey('test-key')
            ->apiUrl('https://custom.url')
            ->collection('documents')
            ->for($user);

        $this->assertInstanceOf(StorageUpService::class, $result);
    }

    /** @test */
    public function it_throws_exception_when_uploading_without_api_key()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API key not set');

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $service = new StorageUpService();
        $service->for($user)->upload($file);
    }

    /** @test */
    public function it_throws_exception_when_uploading_without_model()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Model not set');

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $service = new StorageUpService();
        $service->apiKey('test-key')->upload($file);
    }

    /** @test */
    public function it_can_upload_file_successfully()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'uploaded-file.pdf',
                    'fileId' => 'file-123',
                    'link' => 'https://storage.example.com/files/uploaded-file.pdf',
                    'thumbnail' => 'https://storage.example.com/thumbnails/uploaded-file.jpg',
                ],
            ], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $service = new StorageUpService();
        $result = $service
            ->apiKey('test-key')
            ->for($user)
            ->collection('documents')
            ->upload($file);

        $this->assertInstanceOf(StorageFile::class, $result);
        $this->assertEquals('documents', $result->collection_name);
        $this->assertEquals('document.pdf', $result->original_name);
        $this->assertEquals('uploaded-file.pdf', $result->filename);
        $this->assertEquals('file-123', $result->file_id);
        $this->assertEquals('https://storage.example.com/files/uploaded-file.pdf', $result->url);
    }

    /** @test */
    public function it_resets_properties_after_upload()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'uploaded-file.pdf',
                    'fileId' => 'file-123',
                    'link' => 'https://storage.example.com/files/uploaded-file.pdf',
                ],
            ], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $service = new StorageUpService();
        $service
            ->apiKey('test-key')
            ->for($user)
            ->collection('documents')
            ->upload($file);

        // After upload, should throw exception if trying to upload again without setting model
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Model not set');

        $file2 = UploadedFile::fake()->create('document2.pdf', 1024);
        $service->upload($file2);
    }

    /** @test */
    public function it_handles_failed_api_response()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to upload file to storage service');

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $service = new StorageUpService();
        $service
            ->apiKey('test-key')
            ->for($user)
            ->upload($file);
    }

    /** @test */
    public function it_handles_api_error_status()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'failed',
                'messages' => 'Invalid file type',
            ], 200),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid file type');

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $service = new StorageUpService();
        $service
            ->apiKey('test-key')
            ->for($user)
            ->upload($file);
    }

    /** @test */
    public function it_can_get_all_files_from_collection()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Create test files
        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file1.pdf',
            'filename' => 'stored-file1.pdf',
        ]);

        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file2.pdf',
            'filename' => 'stored-file2.pdf',
        ]);

        $service = new StorageUpService();
        $files = $service->getFile($user, 'documents');

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_can_get_latest_file_from_collection()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Create test files
        $file1 = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file1.pdf',
            'filename' => 'stored-file1.pdf',
        ]);

        // Ensure second file has a later timestamp
        sleep(1);

        $latestFile = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file2.pdf',
            'filename' => 'stored-file2.pdf',
        ]);

        $service = new StorageUpService();
        $file = $service->getFile($user, 'documents', true);

        $this->assertInstanceOf(StorageFile::class, $file);
        $this->assertEquals($latestFile->id, $file->id);
    }

    /** @test */
    public function it_returns_null_when_no_files_found()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $service = new StorageUpService();
        $file = $service->getFile($user, 'documents', true);

        $this->assertNull($file);
    }

    /** @test */
    public function it_can_delete_file()
    {
        Http::fake([
            '*/api/v1/storage/*' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file1.pdf',
            'filename' => 'stored-file1.pdf',
            'file_id' => 'remote-file-123',
        ]);

        $service = new StorageUpService();
        $result = $service->deleteFile($file);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('storage_files', ['id' => $file->id]);
    }

    /** @test */
    public function it_can_delete_all_files_from_collection()
    {
        Http::fake([
            '*/api/v1/storage/*' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Create files in different collections
        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file1.pdf',
            'filename' => 'stored-file1.pdf',
            'file_id' => 'remote-file-1',
        ]);

        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file2.pdf',
            'filename' => 'stored-file2.pdf',
            'file_id' => 'remote-file-2',
        ]);

        $profileFile = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'profile',
            'original_name' => 'avatar.jpg',
            'filename' => 'stored-avatar.jpg',
            'file_id' => 'remote-file-3',
        ]);

        $service = new StorageUpService();
        $service->deleteAllFiles($user, 'documents');

        $this->assertDatabaseMissing('storage_files', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
        ]);

        $this->assertDatabaseHas('storage_files', [
            'id' => $profileFile->id,
        ]);
    }
}
