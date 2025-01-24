<?php

namespace Univpancasila\StorageUp\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    protected function setUp(): void
    {
        parent::setUp();

        // Create test database table
        $this->artisan('migrate', [
            '--database' => 'testing',
            '--path' => __DIR__.'/../../database/migrations',
        ]);
    }

    /** @test */
    public function it_can_handle_user_profile_picture_upload()
    {
        // Arrange
        Storage::fake('local');
        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->save();

        $profilePic = UploadedFile::fake()->image('profile.jpg', 400, 400);

        // Act
        $file = StorageUp::apiKey('test_key')
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
        Storage::fake('local');
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
        Storage::fake('local');
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
        Storage::fake('local');
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
