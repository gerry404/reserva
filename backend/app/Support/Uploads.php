<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * One door to merchant-uploaded images.
 *
 * Every logo, cover and service photo goes through here so the storage backend
 * is a single configuration change (filesystems.uploads) rather than a dozen
 * hardcoded `Storage::disk('public')` calls, which is what made the switch to
 * durable object storage a rewrite instead of an env var.
 */
final class Uploads
{
    public static function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.uploads'));
    }

    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, config('filesystems.uploads'));
    }

    /** Absolute URL for a stored path, or null when there is nothing stored. */
    public static function url(?string $path): ?string
    {
        return $path ? self::disk()->url($path) : null;
    }

    /** @param iterable<string> $paths */
    public static function delete(iterable $paths): void
    {
        foreach ($paths as $path) {
            if ($path) {
                self::disk()->delete($path);
            }
        }
    }
}
