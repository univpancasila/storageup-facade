<?php

namespace Univpancasila\StorageUp\Tests\Integration;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Facades\StorageUp;
use Univpancasila\StorageUp\Models\StorageFile;
use Univpancasila\StorageUp\StorageUpService;
use Univpancasila\StorageUp\Tests\Models\User;
use Univpancasila\StorageUp\Tests\TestCase;

/**
 * Integration tests for StorageUp Facade
 *
 * @author @abdansyakuro.id
 */
class FacadeIntegrationTest extends TestCase
{
    /** @test */
    public function facade_resolves_to_correct_service()
    {
        $service = StorageUp::getFacadeRoot();

        $this->assertInstanceOf(StorageUpService::class, $service);
    }

    /** @test */
    public function facade_maintains_singleton_behavior()
    {
        $service1 = StorageUp::getFacadeRoot();
        $service2 = StorageUp::getFacadeRoot();

        $this->assertSame($service1, $service2);
    }

    /** @test */
    public function complete_upload_workflow_via_facade()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'workflow-test.pdf',
                    'fileId' => 'workflow-123',
                    'link' => 'https://storage.example.com/workflow-test.pdf',
                    'thumbnail' => 'https://storage.example.com/thumb-workflow-test.jpg',
                ],
            ], 200),
        ]);

        $user = User::create(['name' => 'Integration Test', 'email' => 'integration@test.com']);
        $file = UploadedFile::fake()->create('test-document.pdf', 2048);

        // Upload
        $uploadedFile = StorageUp::apiKey('integration-key')
            ->apiUrl('https://integration.storage.com')
            ->for($user)
            ->collection('integration-tests')
            ->upload($file);

        // Verify upload
        $this->assertInstanceOf(StorageFile::class, $uploadedFile);
        $this->assertEquals('integration-tests', $uploadedFile->collection_name);
        $this->assertEquals('test-document.pdf', $uploadedFile->original_name);
        $this->assertEquals('workflow-test.pdf', $uploadedFile->filename);
        $this->assertEquals('workflow-123', $uploadedFile->file_id);

        // Verify HTTP request
        Http::assertSent(function ($request) {
            return $request->hasHeader('Api-key', 'integration-key') &&
                   str_contains($request->url(), 'integration.storage.com');
        });

        // Retrieve
        $retrievedFiles = StorageUp::getFile($user, 'integration-tests');
        $this->assertCount(1, $retrievedFiles);
        $this->assertEquals($uploadedFile->id, $retrievedFiles->first()->id);

        // Retrieve latest
        $latestFile = StorageUp::getFile($user, 'integration-tests', true);
        $this->assertEquals($uploadedFile->id, $latestFile->id);
    }

    /** @test */
    public function complete_delete_workflow_via_facade()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'delete-test.pdf',
                    'fileId' => 'delete-123',
                    'link' => 'https://storage.example.com/delete-test.pdf',
                ],
            ], 200),
            '*/api/v1/storage/delete' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Delete Test', 'email' => 'delete@test.com']);
        $file = UploadedFile::fake()->create('to-delete.pdf', 1024);

        // Upload
        $uploadedFile = StorageUp::apiKey('delete-key')
            ->for($user)
            ->collection('deletable')
            ->upload($file);

        $fileId = $uploadedFile->id;

        // Delete
        $deleted = StorageUp::deleteFile($uploadedFile);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('storage_files', ['id' => $fileId]);
    }

    /** @test */
    public function bulk_delete_workflow_via_facade()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::sequence()
                ->push([
                    'status' => 'success',
                    'data' => ['fileName' => 'bulk1.pdf', 'fileId' => 'bulk-1', 'link' => 'https://example.com/1.pdf'],
                ], 200)
                ->push([
                    'status' => 'success',
                    'data' => ['fileName' => 'bulk2.pdf', 'fileId' => 'bulk-2', 'link' => 'https://example.com/2.pdf'],
                ], 200)
                ->push([
                    'status' => 'success',
                    'data' => ['fileName' => 'keep.pdf', 'fileId' => 'keep-1', 'link' => 'https://example.com/keep.pdf'],
                ], 200),
            '*/api/v1/storage/*' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Bulk Test', 'email' => 'bulk@test.com']);

        // Upload multiple files
        $file1 = UploadedFile::fake()->create('file1.pdf', 1024);
        $file2 = UploadedFile::fake()->create('file2.pdf', 1024);
        $file3 = UploadedFile::fake()->create('file3.pdf', 1024);

        StorageUp::apiKey('bulk-key')->for($user)->collection('bulk-delete')->upload($file1);
        StorageUp::apiKey('bulk-key')->for($user)->collection('bulk-delete')->upload($file2);
        StorageUp::apiKey('bulk-key')->for($user)->collection('keep')->upload($file3);

        // Verify files exist
        $this->assertCount(2, StorageUp::getFile($user, 'bulk-delete'));
        $this->assertCount(1, StorageUp::getFile($user, 'keep'));

        // Bulk delete
        StorageUp::deleteAllFiles($user, 'bulk-delete');

        // Verify deletion
        $this->assertCount(0, StorageUp::getFile($user, 'bulk-delete'));
        $this->assertCount(1, StorageUp::getFile($user, 'keep'));
    }

    /** @test */
    public function error_handling_integration()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'failed',
                'messages' => 'File size exceeds limit',
            ], 200),
        ]);

        $user = User::create(['name' => 'Error Test', 'email' => 'error@test.com']);
        $file = UploadedFile::fake()->create('large-file.pdf', 1024);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File size exceeds limit');

        StorageUp::apiKey('error-key')
            ->for($user)
            ->collection('errors')
            ->upload($file);
    }

    /** @test */
    public function retry_mechanism_works()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::sequence()
                ->push([], 500)
                ->push([], 500)
                ->push([
                    'status' => 'success',
                    'data' => [
                        'fileName' => 'retry-success.pdf',
                        'fileId' => 'retry-123',
                        'link' => 'https://storage.example.com/retry-success.pdf',
                    ],
                ], 200),
        ]);

        $user = User::create(['name' => 'Retry Test', 'email' => 'retry@test.com']);
        $file = UploadedFile::fake()->create('retry.pdf', 1024);

        $uploadedFile = StorageUp::apiKey('retry-key')
            ->for($user)
            ->collection('retry')
            ->upload($file);

        $this->assertInstanceOf(StorageFile::class, $uploadedFile);
        $this->assertEquals('retry-success.pdf', $uploadedFile->filename);

        // Should have made 3 attempts (2 failures + 1 success)
        Http::assertSentCount(3);
    }

    /** @test */
    public function multiple_users_with_same_collection_name()
    {
        Http::fake([
            '*/api/v1/storage/upload' => Http::sequence()
                ->push([
                    'status' => 'success',
                    'data' => ['fileName' => 'user1.pdf', 'fileId' => 'u1-1', 'link' => 'https://example.com/u1.pdf'],
                ], 200)
                ->push([
                    'status' => 'success',
                    'data' => ['fileName' => 'user2.pdf', 'fileId' => 'u2-1', 'link' => 'https://example.com/u2.pdf'],
                ], 200),
        ]);

        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@test.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@test.com']);

        $file1 = UploadedFile::fake()->create('file1.pdf', 1024);
        $file2 = UploadedFile::fake()->create('file2.pdf', 1024);

        StorageUp::apiKey('test-key')->for($user1)->collection('documents')->upload($file1);
        StorageUp::apiKey('test-key')->for($user2)->collection('documents')->upload($file2);

        // Each user should only see their own files
        $user1Files = StorageUp::getFile($user1, 'documents');
        $user2Files = StorageUp::getFile($user2, 'documents');

        $this->assertCount(1, $user1Files);
        $this->assertCount(1, $user2Files);
        $this->assertEquals('user1.pdf', $user1Files->first()->filename);
        $this->assertEquals('user2.pdf', $user2Files->first()->filename);
    }
}
