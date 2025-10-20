<?php

namespace Univpancasila\StorageUp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Models\StorageFile;

/**
 * @author @abdansyakuro.id
 */
class StorageUpService implements StorageUp
{
    protected $apiKey = null;

    protected $collectionName = null;

    protected $model = null;

    protected $apiUrl = 'https://storage.univpancasila.ac.id';

    /**
     * Set the API key for storage service
     *
     * @param  string  $apiKey  The API key for the storage service
     */
    public function apiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Set the API URL for storage service
     *
     * @param  string  $url  The API URL for the storage service
     */
    public function apiUrl(string $url): self
    {
        $this->apiUrl = $url;

        return $this;
    }

    /**
     * Set the collection name for the file
     *
     * @param  string  $name  Collection name
     */
    public function collection(string $name): self
    {
        $this->collectionName = $name;

        return $this;
    }

    /**
     * Set the model instance
     *
     * @param  Model  $model
     */
    public function for(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Upload file to storage service
     *
     * @param  string|null  $type  File type/category
     * @return StorageFile
     *
     * @throws \Exception
     */
    public function upload(UploadedFile $file, ?string $type = null)
    {
        if (! $this->apiKey) {
            throw new \Exception('API key not set. Use apiKey() method first.');
        }

        if (! $this->model) {
            throw new \Exception('Model not set. Use for() method first.');
        }

        $uploadEndpoint = config('storageup.endpoints.upload', '/api/v1/storage/upload');
        $retryCount = config('storageup.retry.upload', 3);

        try {
            $response = Http::withHeaders([
                'Api-key' => $this->apiKey,
            ])
                ->retry($retryCount)
                ->attach(
                    'attachment',
                    fopen($file->getRealPath(), 'r'),
                    $file->getClientOriginalName()
                )
                ->post($this->apiUrl.$uploadEndpoint);

            if ($response->failed()) {
                throw new \Exception('Failed to upload file to storage service.');
            }

            $result = $response->json();

            if ($result['status'] === 'failed') {
                throw new \Exception($result['messages'] ?? 'Failed to upload file.');
            }

            // Create storage file record
            $storageFile = StorageFile::create([
                'model_type' => get_class($this->model),
                'model_id' => $this->model->getKey(),
                'collection_name' => $this->collectionName,
                'original_name' => $file->getClientOriginalName(),
                'filename' => $result['data']['fileName'],
                'file_id' => $result['data']['fileId'] ?? null,
                'url' => $result['data']['link'] ?? null,
                'url_thumbnail' => $result['data']['thumbnail'] ?? null,
            ]);

            // Reset properties after upload
            $this->collectionName = null;
            $this->model = null;

            return $storageFile;
        } catch (\Exception $e) {
            report($e);
            throw new \Exception('Failed to upload file to storage service. ' . $e->getMessage());
        }
    }

    /**
     * Get files from a specific collection for a model
     *
     * @param  Model  $model  The model instance
     * @param  string  $collectionName  The name of the collection to retrieve files from
     * @param  bool  $latest  Get only the latest file from the collection
     * @return \Illuminate\Database\Eloquent\Collection|StorageFile|null
     */
    public function getFile(Model $model, string $collectionName, bool $latest = false)
    {
        $query = StorageFile::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collectionName);

        if ($latest) {
            return $query->latest()->first();
        }

        return $query->get();
    }

    /**
     * Delete a specific file from storage
     *
     * @param  StorageFile  $file  The file to delete
     *
     * @throws \Exception
     */
    public function deleteFile(StorageFile $file): ?bool
    {
        return $file->deleteFile();
    }

    /**
     * Delete all files from a specific collection or all collections for a model
     *
     * @param  Model  $model  The model instance
     * @param  string|null  $collectionName  Optional collection name to delete files from
     */
    public function deleteAllFiles(Model $model, ?string $collectionName = null): void
    {
        StorageFile::deleteAllFiles(
            modelType: get_class($model),
            modelId: $model->getKey(),
            collectionName: $collectionName
        );
    }
}
