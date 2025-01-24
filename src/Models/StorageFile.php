<?php

namespace Univpancasila\StorageUp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Facades\StorageUp;

/**
 * @author @abdansyakuro.id
 */
class StorageFile extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'collection_name',
        'original_name',
        'filename',
        'file_id',
        'url',
        'url_thumbnail',
    ];

    /**
     * Get the parent model
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Delete the file from storage service
     *
     * @param  string|null  $apiKey  Optional API key. If not provided, will use the one from StorageUp facade
     * @param  string|null  $apiUrl  Optional API URL. If not provided, will use the default one
     *
     * @throws \Exception
     */
    public function deleteFile(?string $apiKey = null, ?string $apiUrl = null): ?bool
    {
        if (! $this->file_id) {
            return $this->delete();
        }

        try {
            $response = Http::withHeaders([
                'Api-key' => $apiKey ?? app('storage-up')->apiKey,
            ])
                ->delete(($apiUrl ?? 'https://storage.univpancasila.ac.id').'/api/v1/storage/'.$this->file_id);

            if ($response->failed()) {
                throw new \Exception('Failed to delete file from storage service.');
            }

            return $this->delete();
        } catch (\Exception $e) {
            report($e);
            throw new \Exception('Failed to delete file from storage service. '.$e->getMessage());
        }
    }

    /**
     * Delete all files from a specific collection or all collections
     *
     * @param  mixed  $modelId
     */
    public static function deleteAllFiles(string $modelType, $modelId, ?string $collectionName = null): void
    {
        $query = static::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId);

        if ($collectionName) {
            $query->where('collection_name', $collectionName);
        }

        $query->get()->each(function ($file) {
            $file->deleteFile();
        });
    }
}
