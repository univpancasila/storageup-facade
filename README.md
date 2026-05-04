# StorageUp - Laravel File Storage Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/univpancasila/storage-up.svg?style=flat-square)](https://packagist.org/packages/univpancasila/storage-up)
[![Total Downloads](https://img.shields.io/packagist/dt/univpancasila/storage-up.svg?style=flat-square)](https://packagist.org/packages/univpancasila/storage-up)
[![License](https://img.shields.io/packagist/l/univpancasila/storage-up.svg?style=flat-square)](https://packagist.org/packages/univpancasila/storage-up)

A Laravel package by the Internal Organization of the University of Pancasila. Provides a clean facade for uploading, retrieving, and deleting files via a unified storage API.

## Features

- Simple facade interface with fluent method chaining
- Polymorphic relations — attach files to any Eloquent model
- Named collections for organizing files
- Automatic HTTP retry (configurable)
- Database tracking of all file metadata
- Composite indexes for fast lookups
- 54 tests with comprehensive coverage

## Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- Laravel 9.x, 10.x, 11.x, 12.x, or 13.x
- Guzzle HTTP Client 7.0+

## Installation

**1. Install via Composer**

```bash
composer require univpancasila/storage-up
```

**2. Publish configuration**

```bash
php artisan vendor:publish --tag=storageup-config
```

**3. Publish and run migrations**

```bash
php artisan vendor:publish --tag=storageup-migrations
php artisan migrate
```

**4. Configure environment**

```env
STORAGE_UP_API_URL=https://storage.univpancasila.ac.id
STORAGE_UP_API_KEY=your-api-key-here

# Optional
STORAGE_UP_UPLOAD_ENDPOINT=/api/v1/storage/upload
STORAGE_UP_DELETE_ENDPOINT=/api/v1/storage/delete
STORAGE_UP_UPLOAD_RETRY=3
STORAGE_UP_DELETE_RETRY=10
STORAGE_UP_MAX_SIZE=10240
```

## Basic Usage

### Upload a File

```php
use Univpancasila\StorageUp\Facades\StorageUp;

$file = StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($user)
    ->collection('documents')
    ->upload($request->file('document'));

// $file is a StorageFile model:
// id, original_name, filename, file_id, url, url_thumbnail,
// collection_name, model_type, model_id, timestamps
```

### Retrieve Files

```php
// All files in a collection
$documents = StorageUp::getFile($user, 'documents');

// Latest file only
$latest = StorageUp::getFile($user, 'documents', latest: true);
```

### Delete Files

```php
// Delete a specific file
StorageUp::deleteFile($file);

// Delete all files in a collection
StorageUp::deleteAllFiles($user, 'documents');

// Delete all files for a model
StorageUp::deleteAllFiles($user);
```

## Advanced Usage

### Custom API Configuration

```php
$file = StorageUp::apiKey('custom-key')
    ->apiUrl('https://custom-storage.example.com')
    ->for($user)
    ->collection('profile-pictures')
    ->upload($request->file('avatar'));
```

### Any Eloquent Model

```php
$project = Project::find(1);
StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($project)
    ->collection('blueprints')
    ->upload($request->file('blueprint'));
```

## Model Integration

Add a relationship to any model that needs file storage:

```php
use Univpancasila\StorageUp\Models\StorageFile;

class User extends Model
{
    public function storageFiles()
    {
        return $this->morphMany(StorageFile::class, 'model');
    }
}
```

Usage:

```php
// Eager load to avoid N+1
$users = User::with('storageFiles')->get();

// Filter by collection
$documents = $user->storageFiles()
    ->where('collection_name', 'documents')
    ->get();
```

## Controller Example

```php
use Univpancasila\StorageUp\Facades\StorageUp;
use Univpancasila\StorageUp\Models\StorageFile;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'collection' => 'required|string',
        ]);

        $file = StorageUp::apiKey(config('storageup.api_keys.default'))
            ->for(auth()->user())
            ->collection($request->collection)
            ->upload($request->file('file'));

        return response()->json([
            'id' => $file->id,
            'name' => $file->original_name,
            'url' => $file->url,
            'thumbnail' => $file->url_thumbnail,
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $collection = $request->get('collection');

        $files = $collection
            ? StorageUp::getFile($user, $collection)
            : $user->storageFiles;

        return response()->json($files);
    }

    public function destroy($id)
    {
        $file = StorageFile::findOrFail($id);

        abort_if($file->model_id !== auth()->id(), 403);

        StorageUp::deleteFile($file);

        return response()->json(['message' => 'File deleted']);
    }
}
```

## API Reference

### Facade Methods

| Method | Description |
|--------|-------------|
| `apiKey(string $apiKey): self` | Set API key |
| `apiUrl(string $url): self` | Set custom API URL |
| `collection(string $name): self` | Set collection name |
| `for(Model $model): self` | Bind to an Eloquent model |
| `upload(UploadedFile $file, ?string $type = null): StorageFile` | Upload file |
| `getFile(Model $model, string $collectionName, bool $latest = false)` | Retrieve files |
| `deleteFile(StorageFile $file): ?bool` | Delete a file |
| `deleteAllFiles(Model $model, ?string $collectionName = null): void` | Bulk delete |

### StorageFile Attributes

```php
$file->id
$file->model_type       // polymorphic type
$file->model_id         // polymorphic id
$file->collection_name
$file->original_name
$file->filename
$file->file_id          // remote storage ID
$file->url
$file->url_thumbnail
$file->created_at
$file->updated_at
```

### StorageFile Methods

```php
// Delete file from remote storage and database
$file->deleteFile(?string $apiKey = null, ?string $apiUrl = null): bool;

// Static bulk delete
StorageFile::deleteAllFiles(string $modelType, $modelId, ?string $collectionName = null): void;
```

## Configuration

`config/storageup.php`:

```php
return [
    'api_url'   => env('STORAGE_UP_API_URL', 'https://storage.univpancasila.ac.id'),
    'api_keys'  => [
        'default' => env('STORAGE_UP_API_KEY'),
    ],
    'endpoints' => [
        'upload' => env('STORAGE_UP_UPLOAD_ENDPOINT', '/api/v1/storage/upload'),
        'delete' => env('STORAGE_UP_DELETE_ENDPOINT', '/api/v1/storage/delete'),
    ],
    'retry' => [
        'upload' => env('STORAGE_UP_UPLOAD_RETRY', 3),
        'delete' => env('STORAGE_UP_DELETE_RETRY', 10),
    ],
    'validation' => [
        'max_size'      => env('STORAGE_UP_MAX_SIZE', 10240), // KB
        'allowed_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx',
                            'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar'],
    ],
];
```

Use multiple API keys:

```php
StorageUp::apiKey(config('storageup.api_keys.admin'))->...
```

Apply validation in controllers:

```php
$request->validate([
    'file' => [
        'required', 'file',
        'max:' . config('storageup.validation.max_size'),
        'mimes:' . implode(',', config('storageup.validation.allowed_mimes')),
    ],
]);
```

## Error Handling

```php
try {
    $file = StorageUp::apiKey(config('storageup.api_keys.default'))
        ->for($user)
        ->collection('documents')
        ->upload($request->file('document'));
} catch (\Exception $e) {
    Log::error('File upload failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

Possible exception messages:

- `"API key not set. Use apiKey() method first."` — call `apiKey()` before `upload()`
- `"Model not set. Use for() method first."` — call `for()` before `upload()`
- `"Failed to upload file to storage service."` — network or API error

## Testing

```bash
composer test           # Run all tests
composer test-coverage  # With coverage
composer analyse        # PHPStan static analysis
composer format         # Pint code style
```

Mocking in your application tests:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*/api/v1/storage/upload' => Http::response([
        'status' => 'success',
        'data' => [
            'fileName' => 'test.pdf',
            'fileId'   => 'file-123',
            'link'     => 'https://storage.example.com/test.pdf',
        ],
    ], 200),
]);
```

## Blade Example

```blade
@forelse($user->storageFiles as $file)
    <div>
        @if($file->url_thumbnail)
            <img src="{{ $file->url_thumbnail }}" alt="{{ $file->original_name }}">
        @endif

        <p>{{ $file->original_name }}</p>
        <p>{{ $file->collection_name ?? 'Default' }}</p>
        <a href="{{ $file->url }}" target="_blank">View</a>

        <form action="{{ route('files.destroy', $file->id) }}" method="POST">
            @csrf @method('DELETE')
            <button type="submit">Delete</button>
        </form>
    </div>
@empty
    <p>No files uploaded yet.</p>
@endforelse
```

## Performance Tips

Cache frequently accessed URLs:

```php
$url = Cache::remember("user.{$user->id}.avatar", now()->addHours(24),
    fn() => StorageUp::getFile($user, 'avatars', latest: true)?->url
);
```

Eager load relationships:

```php
$users = User::with('storageFiles')->get();

// Or scoped to a collection
$users = User::with(['storageFiles' => fn($q) =>
    $q->where('collection_name', 'documents')
])->get();
```

## Troubleshooting

**Facade not found**
```bash
php artisan config:clear && php artisan package:discover && composer dump-autoload
```

**Files not in database**
```bash
php artisan vendor:publish --tag=storageup-migrations && php artisan migrate
```

**Upload fails** — verify API key, URL reachability, and file size against `STORAGE_UP_MAX_SIZE`.

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Pull requests are welcome. For development setup:

```bash
git clone https://github.com/univpancasila/storageup-facade.git
cd storageup-facade
composer install
composer test
```

## Security

Report security issues to abdan@univpancasila.ac.id instead of the issue tracker.

## Credits

- [Abdan Syakuro](https://github.com/developerabdan)
- [University of Pancasila](https://univpancasila.ac.id)

## License

MIT — see [LICENSE](LICENSE.md).
