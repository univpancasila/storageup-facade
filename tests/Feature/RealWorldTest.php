<?php

namespace Univpancasila\StorageUp\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Facades\StorageUp;
use Univpancasila\StorageUp\Tests\Models\User;
use Univpancasila\StorageUp\Tests\TestCase;

/**
 * Real-world scenario tests for StorageUp package
 *
 * @author @abdansyakuro.id
 */
class RealWorldTest extends TestCase
{
    /** @test */
    public function it_can_handle_user_profile_picture_upload()
    {
        // Arrange
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'profile-123.jpg',
                    'fileId' => 'profile-file-123',
                    'link' => 'https://storage.example.com/profile-123.jpg',
                    'thumbnail' => 'https://storage.example.com/thumb-profile-123.jpg',
                ],
            ], 200),
        ]);

        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->save();

        $profilePic = UploadedFile::fake()->image('profile.jpg', 400, 400);

        // Act
        $file = StorageUp::apiKey('test-api-key')
            ->for($user)
            ->collection('profile_pictures')
            ->upload($profilePic);

        // Assert
        $this->assertNotNull($file);
        $this->assertEquals('profile_pictures', $file->collection_name);
        $this->assertEquals(User::class, $file->model_type);
        $this->assertEquals($user->id, $file->model_id);
    }

    /** @test */
    public function it_can_handle_multiple_document_uploads()
    {
        // Arrange
        Http::fake([
            '*/api/v1/storage/upload' => Http::sequence()
                ->push([
                    'status' => 'success',
                    'data' => [
                        'fileName' => 'document1-stored.pdf',
                        'fileId' => 'doc-1',
                        'link' => 'https://storage.example.com/document1.pdf',
                    ],
                ], 200)
                ->push([
                    'status' => 'success',
                    'data' => [
                        'fileName' => 'document2-stored.pdf',
                        'fileId' => 'doc-2',
                        'link' => 'https://storage.example.com/document2.pdf',
                    ],
                ], 200),
        ]);

        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->save();

        $doc1 = UploadedFile::fake()->create('document1.pdf', 1024);
        $doc2 = UploadedFile::fake()->create('document2.pdf', 1024);

        // Act
        $file1 = StorageUp::apiKey('test_key')
            ->for($user)
            ->collection('documents')
            ->upload($doc1);

        $file2 = StorageUp::apiKey('test_key')
            ->for($user)
            ->collection('documents')
            ->upload($doc2);

        // Get all documents
        $documents = StorageUp::getFile($user, 'documents');

        // Assert
        $this->assertCount(2, $documents);
        $this->assertEquals('documents', $file1->collection_name);
        $this->assertEquals('documents', $file2->collection_name);
    }

    /** @test */
    public function it_can_handle_file_deletion()
    {
        // Arrange
        Http::fake([
            '*/api/v1/storage/upload' => Http::response([
                'status' => 'success',
                'data' => [
                    'fileName' => 'document-stored.pdf',
                    'fileId' => 'doc-123',
                    'link' => 'https://storage.example.com/document.pdf',
                ],
            ], 200),
            '*/api/v1/storage/delete' => Http::response(['status' => 'success'], 200),
        ]);

        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->save();

        $doc = UploadedFile::fake()->create('document.pdf', 1024);
        $file = StorageUp::apiKey('test_key')
            ->for($user)
            ->collection('documents')
            ->upload($doc);

        // Act
        $deleted = StorageUp::deleteFile($file);

        // Assert
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('storage_files', ['id' => $file->id]);
    }

    /** @test */
    public function it_can_handle_bulk_file_operations()
    {
        // Arrange
        Http::fake([
            '*/api/v1/storage/upload' => Http::sequence()
                ->push([
                    'status' => 'success',
                    'data' => [
                        'fileName' => 'profile-stored.jpg',
                        'fileId' => 'profile-1',
                        'link' => 'https://storage.example.com/profile.jpg',
                    ],
                ], 200)
                ->push([
                    'status' => 'success',
                    'data' => [
                        'fileName' => 'doc1-stored.pdf',
                        'fileId' => 'doc-1',
                        'link' => 'https://storage.example.com/doc1.pdf',
                    ],
                ], 200)
                ->push([
                    'status' => 'success',
                    'data' => [
                        'fileName' => 'doc2-stored.pdf',
                        'fileId' => 'doc-2',
                        'link' => 'https://storage.example.com/doc2.pdf',
                    ],
                ], 200),
            '*/api/v1/storage/*' => Http::response(['status' => 'success'], 200),
        ]);

        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->save();

        // Upload multiple files to different collections
        $profilePic = UploadedFile::fake()->image('profile.jpg');
        $doc1 = UploadedFile::fake()->create('document1.pdf', 1024);
        $doc2 = UploadedFile::fake()->create('document2.pdf', 1024);

        StorageUp::apiKey('test_key')->for($user)->collection('profile_pictures')->upload($profilePic);
        StorageUp::apiKey('test_key')->for($user)->collection('documents')->upload($doc1);
        StorageUp::apiKey('test_key')->for($user)->collection('documents')->upload($doc2);

        // Act
        StorageUp::deleteAllFiles($user, 'documents');

        // Assert
        $remainingFiles = StorageUp::getFile($user, 'documents');
        $profilePicture = StorageUp::getFile($user, 'profile_pictures');

        $this->assertCount(0, $remainingFiles);
        $this->assertCount(1, $profilePicture);
    }
}
