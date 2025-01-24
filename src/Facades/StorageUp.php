<?php

namespace Univpancasila\StorageUp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Univpancasila\StorageUp\StorageUpService apiKey(string $apiKey)
 * @method static \Univpancasila\StorageUp\StorageUpService apiUrl(string $url)
 * @method static \Univpancasila\StorageUp\StorageUpService collection(string $name)
 * @method static \Univpancasila\StorageUp\StorageUpService for($model)
 * @method static \Univpancasila\StorageUp\Models\StorageFile upload(\Illuminate\Http\UploadedFile $file, ?string $type = null)
 * @method static \Illuminate\Database\Eloquent\Collection|\Univpancasila\StorageUp\Models\StorageFile|null getFile($model, string $collectionName, bool $latest = false)
 * @method static bool|null deleteFile(\Univpancasila\StorageUp\Models\StorageFile $file)
 * @method static void deleteAllFiles($model, ?string $collectionName = null)
 *
 * @see \Univpancasila\StorageUp\StorageUpService
 * @author @abdansyakuro.id
 */
class StorageUp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'storageup';
    }
}
