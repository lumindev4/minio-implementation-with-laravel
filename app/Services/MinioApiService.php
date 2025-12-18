<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinioApiService
{
    private $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('minio');
    }

    public function upload($file): array
    {
        try{
            $folder = match (true) {
                str_starts_with($file->getMimeType(), 'image/') => 'images',
                str_starts_with($file->getMimeType(), 'video/') => 'videos',
                default => 'files',
            };

            $path = sprintf(
                '%s/%s/%s.%s',
                $folder,
                now()->format('Y/m'),
                Str::uuid(),
                $file->getClientOriginalExtension()
            );

            $this->disk->writeStream(
                $path,
                fopen($file->getRealPath(), 'r'),
                ['visibility' => 'private']
            );

            // Store metadata in DB after successful upload
            $record = File::create([
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'size'          => $file->getSize(),
                'mime'          => $file->getMimeType(),
                'disk'          => 'minio',
            ]);

            return [
                'id'   => $record->id,
                'name' => $record->original_name,
                'path' => $record->path,
                'size' => $record->size,
                'mime' => $record->mime,
            ];
        }catch(\Exception $e){
            return [
                'message' => 'Failed to upload file',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function list(string $prefix = '', int $expiresMinutes = 15): array
    {
        // Use a recursive listing so files in nested folders (e.g. images/2025/...) are returned
        try {
            $files = $this->disk->allFiles($prefix);

            $result = [];

            foreach ($files as $path) {
                // Determine visibility if available
                try {
                    if (method_exists($this->disk, 'getVisibility')) {
                        $visibility = $this->disk->getVisibility($path);
                    } elseif (method_exists($this->disk, 'visibility')) {
                        $visibility = $this->disk->visibility($path);
                    } else {
                        $visibility = null;
                    }
                } catch (\Exception $e) {
                    $visibility = null;
                }

                // Prefer a direct URL for public files, otherwise generate a temporary URL
                $url = null;

                if ($visibility === 'public' && method_exists($this->disk, 'url')) {
                    try {
                        $url = $this->disk->url($path);
                    } catch (\Exception $e) {
                        $url = null;
                    }
                } elseif (method_exists($this->disk, 'temporaryUrl')) {
                    try {
                        $url = $this->disk->temporaryUrl($path, now()->addMinutes($expiresMinutes));
                    } catch (\Exception $e) {
                        $url = null;
                    }
                }

                $result[] = [
                    'visibility' => $visibility,
                    'path' => $path,
                    'name' => basename($path),
                    'url' => $url,
                    'size' => $this->disk->size($path),
                    'mime' => $this->disk->mimeType($path),
                    'last_modified' => $this->disk->lastModified($path),
                ];
            }

            return $result;
        } catch (\Exception $e) {
            // On error return empty array (you may want to log $e->getMessage())
            return [];
        }
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    public function delete(string $path): bool
    {
        try {
            $deleted = $this->disk->delete($path);
        } catch (\Exception $e) {
            $deleted = false;
        }

        // If the file was deleted from storage, remove DB record as well
        if ($deleted) {
            try {
                File::where('path', $path)->delete();
            } catch (\Exception $e) {
                // ignore DB delete failures for now
            }
        }

        return $deleted;
    }

    public function replace(string $path, $file): array
    {
        try {
            $record = File::where('path', $path)->first();

            // Write the new content to the same path
            $this->disk->writeStream(
                $path,
                fopen($file->getRealPath(), 'r'),
                ['visibility' => 'private']
            );

            // Update DB record if exists, otherwise create one with the same path
            if ($record) {
                $record->update([
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ]);
            } else {
                $record = File::create([
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'disk' => 'minio',
                ]);
            }

            return [
                'id' => $record->id,
                'name' => $record->original_name,
                'path' => $record->path,
                'size' => $record->size,
                'mime' => $record->mime,
            ];
        } catch (\Exception $e) {
            return [
                'message' => 'Failed to replace file',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function temporaryUrl(string $path, int $minutes = 15): string
    {
        return $this->disk->temporaryUrl(
            $path,
            now()->addMinutes($minutes)
        );
    }
}
