<?php

namespace Univpancasila\StorageUp\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Models\StorageFile;
use Univpancasila\StorageUp\Tests\Models\User;
use Univpancasila\StorageUp\Tests\TestCase;

/**
 * Unit tests for StorageFile Model
 *
 * @author @abdansyakuro.id
 */
class StorageFileModelTest extends TestCase
{
    /** @test */
    public function it_has_fillable_attributes()
    {
        $file = new StorageFile([
            'model_type' => User::class,
            'model_id' => 1,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
            'file_id' => 'file-123',
            'url' => 'https://example.com/file.pdf',
            'url_thumbnail' => 'https://example.com/thumb.jpg',
        ]);

        $this->assertEquals(User::class, $file->model_type);
        $this->assertEquals(1, $file->model_id);
        $this->assertEquals('documents', $file->collection_name);
        $this->assertEquals('test.pdf', $file->original_name);
        $this->assertEquals('stored-test.pdf', $file->filename);
        $this->assertEquals('file-123', $file->file_id);
        $this->assertEquals('https://example.com/file.pdf', $file->url);
        $this->assertEquals('https://example.com/thumb.jpg', $file->url_thumbnail);
    }

    /** @test */
    public function it_has_morphto_relationship()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
        ]);

        $this->assertInstanceOf(User::class, $file->model);
        $this->assertEquals($user->id, $file->model->id);
        $this->assertEquals($user->name, $file->model->name);
    }

    /** @test */
    public function it_can_delete_file_without_file_id()
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
            'file_id' => null,
        ]);

        $result = $file->deleteFile();

        $this->assertTrue($result);
        $this->assertDatabaseMissing('storage_files', ['id' => $file->id]);
    }

    /** @test */
    public function it_can_delete_file_with_file_id()
    {
        Http::fake([
            '*/api/v1/storage/delete' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
            'file_id' => 'file-123',
        ]);

        $result = $file->deleteFile();

        $this->assertTrue($result);
        $this->assertDatabaseMissing('storage_files', ['id' => $file->id]);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('Api-key', 'test-api-key') &&
                   $request->url() === 'https://storage.univpancasila.ac.id/api/v1/storage/delete' &&
                   $body['fileId'] === 'file-123' &&
                   $body['fileName'] === 'stored-test.pdf' &&
                   $body['safeDelete'] === 0;
        });
    }

    /** @test */
    public function it_can_delete_file_with_custom_api_key()
    {
        Http::fake([
            '*/api/v1/storage/delete' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
            'file_id' => 'file-123',
        ]);

        $result = $file->deleteFile('custom-api-key');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Api-key', 'custom-api-key');
        });
    }

    /** @test */
    public function it_can_delete_file_with_custom_api_url()
    {
        Http::fake([
            'https://custom.storage.com/api/v1/storage/delete' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
            'file_id' => 'file-123',
        ]);

        $result = $file->deleteFile('test-key', 'https://custom.storage.com');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://custom.storage.com/api/v1/storage/delete';
        });
    }

    /** @test */
    public function it_throws_exception_when_api_delete_fails()
    {
        Http::fake([
            '*/api/v1/storage/delete' => Http::response([], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to delete file from storage service');

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $file = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'test.pdf',
            'filename' => 'stored-test.pdf',
            'file_id' => 'file-123',
        ]);

        $file->deleteFile();
    }

    /** @test */
    public function it_can_delete_all_files_for_model_and_collection()
    {
        Http::fake([
            '*/api/v1/storage/*' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Create files
        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file1.pdf',
            'filename' => 'stored-file1.pdf',
            'file_id' => 'file-1',
        ]);

        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
            'original_name' => 'file2.pdf',
            'filename' => 'stored-file2.pdf',
            'file_id' => 'file-2',
        ]);

        StorageFile::deleteAllFiles(User::class, $user->id, 'documents');

        $this->assertDatabaseMissing('storage_files', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'documents',
        ]);
    }

    /** @test */
    public function it_can_delete_all_files_for_model_without_collection_filter()
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
            'file_id' => 'file-1',
        ]);

        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user->id,
            'collection_name' => 'profile',
            'original_name' => 'avatar.jpg',
            'filename' => 'stored-avatar.jpg',
            'file_id' => 'file-2',
        ]);

        StorageFile::deleteAllFiles(User::class, $user->id);

        $this->assertDatabaseMissing('storage_files', [
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_only_deletes_files_for_specific_model()
    {
        Http::fake([
            '*/api/v1/storage/*' => Http::response(['status' => 'success'], 200),
        ]);

        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        // Create files for both users
        StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user1->id,
            'collection_name' => 'documents',
            'original_name' => 'file1.pdf',
            'filename' => 'stored-file1.pdf',
            'file_id' => 'file-1',
        ]);

        $user2File = StorageFile::create([
            'model_type' => User::class,
            'model_id' => $user2->id,
            'collection_name' => 'documents',
            'original_name' => 'file2.pdf',
            'filename' => 'stored-file2.pdf',
            'file_id' => 'file-2',
        ]);

        StorageFile::deleteAllFiles(User::class, $user1->id, 'documents');

        $this->assertDatabaseMissing('storage_files', [
            'model_type' => User::class,
            'model_id' => $user1->id,
        ]);

        $this->assertDatabaseHas('storage_files', [
            'id' => $user2File->id,
        ]);
    }
}
