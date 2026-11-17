<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final class MediaStorage
{
    public static function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public static function disk(): FilesystemAdapter
    {
        return Storage::disk(self::diskName());
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (self::diskName() === 'cloudinary') {
            $baseUrl = (string) config('filesystems.disks.cloudinary.url', '');

            return $baseUrl !== ''
                ? rtrim($baseUrl, '/').'/'.ltrim($path, '/')
                : null;
        }

        return self::disk()->url($path);
    }
}
