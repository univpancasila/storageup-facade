<?php

namespace Univpancasila\StorageUp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Models\StorageFile;

/**
 * @author @abdansyakuro.id
 */
interface StorageUp
{
    /**
     * Set the API key for storage service
     *
     * @param  string  $apiKey  The API key for the storage service
     */
    public function apiKey(string $apiKey): self;
    /**
     * Set the API URL for storage service
     *
     * @param  string  $url  The API URL for the storage service
     */
    public function apiUrl(string $url): self;

    /**
     * Set the collection name for the file
     *
     * @param  string  $name  Collection name
     */
    public function collection(string $name): self;

    /**
     * Set the model instance
     *
     * @param  Model  $model
     */
    public function for(Model $model): self;

    /**
     * Upload file to storage service
     *
     * @param  string|null  $type  File type/category
     * @return StorageFile
     *
     * @throws \Exception
     */
    public function upload(UploadedFile $file, ?string $type = null);

    /**
     * Get files from a specific collection for a model
     *
     * @param  Model  $model  The model instance
     * @param  string  $collectionName  The name of the collection to retrieve files from
     * @param  bool  $latest  Get only the latest file from the collection
     * @return \Illuminate\Database\Eloquent\Collection|StorageFile|null
     */
    public function getFile(Model $model, string $collectionName, bool $latest = false);

    /**
     * Delete a specific file from storage
     *
     * @param  StorageFile  $file  The file to delete
     *
     * @throws \Exception
     */
    public function deleteFile(StorageFile $file): ?bool;

    /**
     * Delete all files from a specific collection or all collections for a model
     *
     * @param  Model  $model  The model instance
     * @param  string|null  $collectionName  Optional collection name to delete files from
     */
    public function deleteAllFiles(Model $model, ?string $collectionName = null): void;
}
