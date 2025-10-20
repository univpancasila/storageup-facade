# StorageUp - Laravel File Storage Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/univpancasila/storage-up.svg?style=flat-square)](https://packagist.org/packages/univpancasila/storage-up)
[![Total Downloads](https://img.shields.io/packagist/dt/univpancasila/storage-up.svg?style=flat-square)](https://packagist.org/packages/univpancasila/storage-up)
[![License](https://img.shields.io/packagist/l/univpancasila/storage-up.svg?style=flat-square)](https://packagist.org/packages/univpancasila/storage-up)

A Laravel package developed by the Internal Organization of the University of Pancasila, designed to simplify file storage management. This package provides an intuitive facade for uploading, retrieving, and managing files across organizational applications through a unified API.

## Features

✨ **Simple & Intuitive** - Clean facade interface for file operations
🔗 **Polymorphic Relations** - Attach files to any Eloquent model
📁 **Collection Management** - Organize files into named collections
🔄 **Automatic Retry** - Built-in HTTP retry mechanism (3-10 attempts, configurable)
🗄️ **Database Tracking** - Track all file metadata in your database
⚡ **Optimized Queries** - Composite indexes for fast lookups
🧪 **Fully Tested** - 54 tests with comprehensive coverage
🎯 **Type Safe** - Full type hints and PHPStan compliance
⚙️ **Configurable** - Endpoints and retry counts customizable via config

## Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- Laravel 9.x, 10.x, 11.x, or 12.x
- Guzzle HTTP Client 7.0+

## Installation

### 1. Install via Composer

```bash
composer require univpancasila/storageup-facade
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=storageup-config
```

This creates `config/storageup.php`:

```php
return [
    'api_url' => env('STORAGE_UP_API_URL', 'https://storage.univpancasila.ac.id'),

    'api_keys' => [
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
        'max_size' => env('STORAGE_UP_MAX_SIZE', 10240), // 10MB
        'allowed_mimes' => [
            'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx',
            'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar',
        ],
    ],
];
```

### 3. Publish & Run Migrations

```bash
php artisan vendor:publish --tag=storageup-migrations
php artisan migrate
```

This creates the `storage_files` table to track your uploads.

### 4. Configure Environment

Add to your `.env` file:

```env
# Required
STORAGE_UP_API_URL=https://storage.univpancasila.ac.id
STORAGE_UP_API_KEY=your-api-key-here

# Optional - Customize endpoints (defaults shown)
STORAGE_UP_UPLOAD_ENDPOINT=/api/v1/storage/upload
STORAGE_UP_DELETE_ENDPOINT=/api/v1/storage/delete

# Optional - Customize retry counts (defaults shown)
STORAGE_UP_UPLOAD_RETRY=3
STORAGE_UP_DELETE_RETRY=10

# Optional - Customize validation (defaults shown)
STORAGE_UP_MAX_SIZE=10240
```

## Basic Usage

### Upload a File

```php
use Univpancasila\StorageUp\Facades\StorageUp;
use App\Models\User;

$user = User::find(1);

$file = StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($user)
    ->collection('documents')
    ->upload($request->file('document'));

// Returns StorageFile model with:
// - id, original_name, filename, file_id
// - url, url_thumbnail, collection_name
// - model_type, model_id, timestamps
```

### Retrieve Files

```php
// Get all files from a collection
$documents = StorageUp::getFile($user, 'documents');

foreach ($documents as $doc) {
    echo $doc->original_name;
    echo $doc->url;
}

// Get only the latest file
$latestDocument = StorageUp::getFile($user, 'documents', $latest = true);
```

### Delete Files

```php
// Delete a specific file
$file = $user->storageFiles()->first();
StorageUp::deleteFile($file);

// Delete all files from a collection
StorageUp::deleteAllFiles($user, 'documents');

// Delete all files for a model
StorageUp::deleteAllFiles($user);
```

## Advanced Usage

### Fluent Interface

Chain methods for clean, readable code:

```php
$file = StorageUp::apiKey('custom-key')
    ->apiUrl('https://custom-storage.example.com')
    ->for($user)
    ->collection('profile-pictures')
    ->upload($request->file('avatar'));
```

### Custom API Configuration

```php
// Use different API keys per upload
$file = StorageUp::apiKey('admin-key')
    ->for($document)
    ->collection('confidential')
    ->upload($file);

// Use different storage endpoints
$file = StorageUp::apiUrl('https://backup.storage.com')
    ->apiKey('backup-key')
    ->for($user)
    ->upload($file);
```

### Working with Collections

```php
// Profile pictures
$avatar = StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($user)
    ->collection('avatars')
    ->upload($request->file('avatar'));

// Documents
$document = StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($user)
    ->collection('documents')
    ->upload($request->file('document'));

// Retrieve by collection
$avatars = StorageUp::getFile($user, 'avatars');
$documents = StorageUp::getFile($user, 'documents');
```

### Using with Any Model

StorageUp works with any Eloquent model:

```php
use App\Models\Project;
use App\Models\Invoice;

// Attach to Project
$project = Project::find(1);
$file = StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($project)
    ->collection('blueprints')
    ->upload($request->file('blueprint'));

// Attach to Invoice
$invoice = Invoice::find(1);
$receipt = StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($invoice)
    ->collection('receipts')
    ->upload($request->file('receipt'));
```

## Model Integration

### Add Relationship to Your Models

Add this to any model that needs file storage:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Univpancasila\StorageUp\Models\StorageFile;

class User extends Model
{
    /**
     * Get all storage files for this user
     */
    public function storageFiles()
    {
        return $this->morphMany(StorageFile::class, 'model');
    }

    /**
     * Get files from specific collection
     */
    public function getStorageFiles(string $collection)
    {
        return $this->storageFiles()
            ->where('collection_name', $collection)
            ->get();
    }

    /**
     * Get latest file from collection
     */
    public function getLatestFile(string $collection)
    {
        return $this->storageFiles()
            ->where('collection_name', $collection)
            ->latest()
            ->first();
    }
}
```

### Using the Relationship

```php
// Get all files
$allFiles = $user->storageFiles;

// Get files by collection
$documents = $user->storageFiles()
    ->where('collection_name', 'documents')
    ->get();

// Count files
$fileCount = $user->storageFiles()->count();

// Get files with specific attributes
$pdfs = $user->storageFiles()
    ->where('collection_name', 'documents')
    ->where('original_name', 'LIKE', '%.pdf')
    ->get();
```

## Controller Example

Here's a complete controller example:

```php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Univpancasila\StorageUp\Facades\StorageUp;
use Univpancasila\StorageUp\Models\StorageFile;

class FileController extends Controller
{
    /**
     * Upload a file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'collection' => 'required|string',
        ]);

        try {
            $user = auth()->user();

            $file = StorageUp::apiKey(config('storageup.api_keys.default'))
                ->for($user)
                ->collection($request->collection)
                ->upload($request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file' => [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'url' => $file->url,
                    'thumbnail' => $file->url_thumbnail,
                    'collection' => $file->collection_name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user files
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $collection = $request->get('collection');

        $files = $collection
            ? StorageUp::getFile($user, $collection)
            : $user->storageFiles;

        return response()->json([
            'success' => true,
            'files' => $files,
        ]);
    }

    /**
     * Delete a file
     */
    public function destroy($id)
    {
        try {
            $file = StorageFile::findOrFail($id);

            // Optional: Check ownership
            if ($file->model_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            StorageUp::deleteFile($file);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all files from collection
     */
    public function destroyCollection(Request $request)
    {
        $request->validate([
            'collection' => 'required|string',
        ]);

        try {
            $user = auth()->user();
            StorageUp::deleteAllFiles($user, $request->collection);

            return response()->json([
                'success' => true,
                'message' => 'All files deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
```

## API Reference

### StorageUp Facade Methods

#### `apiKey(string $apiKey): self`
Set the API key for the storage service.

```php
StorageUp::apiKey('your-api-key')
```

#### `apiUrl(string $url): self`
Set a custom API URL.

```php
StorageUp::apiUrl('https://custom.storage.com')
```

#### `collection(string $name): self`
Set the collection name for organizing files.

```php
StorageUp::collection('documents')
```

#### `for(Model $model): self`
Set the model instance to attach files to.

```php
StorageUp::for($user)
```

#### `upload(UploadedFile $file, ?string $type = null): StorageFile`
Upload a file to the storage service.

```php
$file = StorageUp::upload($request->file('document'));
```

**Throws:** `\Exception` if API key or model is not set.

#### `getFile(Model $model, string $collectionName, bool $latest = false)`
Retrieve files for a model and collection.

```php
// Get all files
$files = StorageUp::getFile($user, 'documents');

// Get latest file only
$file = StorageUp::getFile($user, 'documents', true);
```

**Returns:** `Collection|StorageFile|null`

#### `deleteFile(StorageFile $file): ?bool`
Delete a specific file from storage and database.

```php
StorageUp::deleteFile($file);
```

#### `deleteAllFiles(Model $model, ?string $collectionName = null): void`
Delete all files for a model, optionally filtered by collection.

```php
// Delete all files from a collection
StorageUp::deleteAllFiles($user, 'documents');

// Delete all files for a model
StorageUp::deleteAllFiles($user);
```

### StorageFile Model

The `StorageFile` model has the following attributes:

```php
$file->id                 // int
$file->model_type         // string (polymorphic type)
$file->model_id          // int (polymorphic id)
$file->collection_name   // string|null
$file->original_name     // string
$file->filename          // string (stored filename)
$file->file_id           // string|null (remote file ID)
$file->url               // string|null (file URL)
$file->url_thumbnail     // string|null (thumbnail URL)
$file->created_at        // Carbon
$file->updated_at        // Carbon
```

#### Model Methods

```php
// Get the parent model
$file->model(); // Returns the associated model (User, Project, etc.)

// Delete file from storage and database
$file->deleteFile(?string $apiKey = null, ?string $apiUrl = null);

// Static method to delete multiple files
StorageFile::deleteAllFiles(
    string $modelType,
    $modelId,
    ?string $collectionName = null
);
```

## Blade Examples

### Upload Form

```blade
<form action="{{ route('files.upload') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="mb-4">
        <label for="file" class="block text-sm font-medium text-gray-700">
            Upload File
        </label>
        <input type="file"
               name="file"
               id="file"
               required
               class="mt-1 block w-full">
        @error('file')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label for="collection" class="block text-sm font-medium text-gray-700">
            Collection
        </label>
        <select name="collection"
                id="collection"
                required
                class="mt-1 block w-full">
            <option value="documents">Documents</option>
            <option value="images">Images</option>
            <option value="videos">Videos</option>
        </select>
    </div>

    <button type="submit"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        Upload File
    </button>
</form>
```

### Display Files

```blade
<div class="space-y-4">
    @forelse($user->storageFiles as $file)
        <div class="flex items-center justify-between p-4 border rounded">
            <div class="flex items-center space-x-4">
                @if($file->url_thumbnail)
                    <img src="{{ $file->url_thumbnail }}"
                         alt="{{ $file->original_name }}"
                         class="w-16 h-16 object-cover rounded">
                @endif

                <div>
                    <h4 class="font-medium">{{ $file->original_name }}</h4>
                    <p class="text-sm text-gray-600">
                        Collection: {{ $file->collection_name ?? 'Default' }}
                    </p>
                    <p class="text-xs text-gray-500">
                        Uploaded {{ $file->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>

            <div class="flex space-x-2">
                <a href="{{ $file->url }}"
                   target="_blank"
                   class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                    View
                </a>

                <form action="{{ route('files.destroy', $file->id) }}"
                      method="POST"
                      onsubmit="return confirm('Are you sure?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-gray-500 text-center py-8">No files uploaded yet.</p>
    @endforelse
</div>
```

## Error Handling

The package throws exceptions that you should catch:

```php
try {
    $file = StorageUp::apiKey(config('storageup.api_keys.default'))
        ->for($user)
        ->collection('documents')
        ->upload($request->file('document'));

} catch (\Exception $e) {
    // Handle errors
    if (str_contains($e->getMessage(), 'API key not set')) {
        // API key configuration error
    } elseif (str_contains($e->getMessage(), 'Model not set')) {
        // Model not provided
    } elseif (str_contains($e->getMessage(), 'Failed to upload')) {
        // Upload failed (network, API error, etc.)
    } else {
        // Other errors
    }

    Log::error('File upload failed', [
        'error' => $e->getMessage(),
        'user_id' => $user->id ?? null,
    ]);

    throw $e;
}
```

## Testing

The package includes comprehensive tests:

```bash
# Run all tests
composer test

# Run specific test suites
./vendor/bin/pest tests/Unit
./vendor/bin/pest tests/Feature
./vendor/bin/pest tests/Integration

# Run with coverage
composer test-coverage

# Run static analysis
composer analyse

# Fix code style
composer format
```

### Writing Tests for Your Application

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Univpancasila\StorageUp\Facades\StorageUp;

test('user can upload document', function () {
    Http::fake([
        '*/api/v1/storage/upload' => Http::response([
            'status' => 'success',
            'data' => [
                'fileName' => 'test-document.pdf',
                'fileId' => 'file-123',
                'link' => 'https://storage.example.com/test-document.pdf',
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 1024);

    $response = $this->actingAs($user)
        ->post('/upload', [
            'file' => $file,
            'collection' => 'documents',
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('storage_files', [
        'model_type' => User::class,
        'model_id' => $user->id,
        'collection_name' => 'documents',
        'original_name' => 'document.pdf',
    ]);
});
```

## Configuration

### Multiple API Keys

You can configure multiple API keys in `config/storageup.php`:

```php
'api_keys' => [
    'default' => env('STORAGE_UP_API_KEY'),
    'admin' => env('STORAGE_UP_ADMIN_KEY'),
    'backup' => env('STORAGE_UP_BACKUP_KEY'),
],
```

Usage:

```php
// Use default key
StorageUp::apiKey(config('storageup.api_keys.default'))

// Use admin key
StorageUp::apiKey(config('storageup.api_keys.admin'))

// Use backup key
StorageUp::apiKey(config('storageup.api_keys.backup'))
```

### API Endpoints

Customize API endpoints in `config/storageup.php`:

```php
'endpoints' => [
    'upload' => env('STORAGE_UP_UPLOAD_ENDPOINT', '/api/v1/storage/upload'),
    'delete' => env('STORAGE_UP_DELETE_ENDPOINT', '/api/v1/storage/delete'),
],
```

You can override these in your `.env` file:

```env
STORAGE_UP_UPLOAD_ENDPOINT=/api/v1/storage/upload
STORAGE_UP_DELETE_ENDPOINT=/api/v1/storage/delete
```

This is useful when:
- Using a different API version
- Testing with a staging endpoint
- Implementing custom routing on the storage server

### HTTP Retry Configuration

Configure retry attempts for each operation in `config/storageup.php`:

```php
'retry' => [
    'upload' => env('STORAGE_UP_UPLOAD_RETRY', 3),
    'delete' => env('STORAGE_UP_DELETE_RETRY', 10),
],
```

**Why different retry counts?**
- **Upload (3 retries)**: Files are typically large, so fewer retries prevent long wait times
- **Delete (10 retries)**: Lightweight operation, more retries ensure reliability

Override in `.env`:

```env
STORAGE_UP_UPLOAD_RETRY=5
STORAGE_UP_DELETE_RETRY=15
```

### Validation Rules

Configure file validation in `config/storageup.php`:

```php
'validation' => [
    'max_size' => env('STORAGE_UP_MAX_SIZE', 10240), // kilobytes
    'allowed_mimes' => [
        'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx',
        'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar',
    ],
],
```

Apply in your validation:

```php
$request->validate([
    'file' => [
        'required',
        'file',
        'max:' . config('storageup.validation.max_size'),
        'mimes:' . implode(',', config('storageup.validation.allowed_mimes')),
    ],
]);
```

## Performance Tips

### Database Optimization

The package includes optimized indexes. To check:

```sql
-- Check indexes on storage_files table
SHOW INDEX FROM storage_files;
```

You should see:
- Composite index on `model_type`, `model_id`, `collection_name`
- Index on `file_id`

### Eager Loading

When retrieving multiple models with files:

```php
// ❌ Bad - N+1 query problem
$users = User::all();
foreach ($users as $user) {
    $files = $user->storageFiles; // Separate query per user
}

// ✅ Good - Single query with eager loading
$users = User::with('storageFiles')->get();
foreach ($users as $user) {
    $files = $user->storageFiles; // Already loaded
}

// ✅ Even better - Load specific collection
$users = User::with(['storageFiles' => function ($query) {
    $query->where('collection_name', 'documents');
}])->get();
```

### Caching File URLs

Cache frequently accessed file URLs:

```php
use Illuminate\Support\Facades\Cache;

$avatarUrl = Cache::remember(
    "user.{$user->id}.avatar",
    now()->addHours(24),
    fn() => StorageUp::getFile($user, 'avatars', true)?->url
);
```

## Troubleshooting

### Issue: "API key not set"

**Solution:** Make sure to call `apiKey()` before `upload()`:

```php
StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($user)
    ->upload($file);
```

### Issue: "Model not set"

**Solution:** Make sure to call `for()` before `upload()`:

```php
StorageUp::apiKey(config('storageup.api_keys.default'))
    ->for($user) // Required
    ->upload($file);
```

### Issue: "Failed to upload file to storage service"

**Solutions:**
1. Check API key is valid
2. Verify API URL is correct and accessible
3. Check network connectivity
4. Review file size limits
5. Check API server logs

### Issue: Files not appearing in database

**Solution:** Run migrations:

```bash
php artisan migrate

# Or republish and migrate
php artisan vendor:publish --tag=storageup-migrations --force
php artisan migrate:fresh
```

### Issue: Facade not found

**Solutions:**

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan clear-compiled

# Rebuild autoload
composer dump-autoload

# Ensure service provider is registered
php artisan package:discover
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
# Clone repository
git clone https://github.com/univpancasila/storageup-facade.git
cd storageup-facade

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer analyse

# Fix code style
composer format
```

## Security

If you discover any security-related issues, please email abdan@univpancasila.ac.id instead of using the issue tracker.

## Credits

- [Abdan Syakuro](https://github.com/abdansyakuro) - Developer
- [University of Pancasila](https://univpancasila.ac.id) - Organization
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

**Made with ❤️ by Internal Organization of University of Pancasila**
