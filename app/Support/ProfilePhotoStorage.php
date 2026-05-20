<?php

namespace App\Support;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotoStorage
{
    private const DIRECTORY = 'fotos_perfil';
    private const LEGACY_DISK = 'public';
    private const CLOUDINARY_DISK = 'cloudinary';

    public static function store(UploadedFile $file): string
    {
        $disk = self::configuredDisk();
        $path = $file->storePublicly(self::DIRECTORY, $disk);

        return self::formatReference($disk, $path);
    }

    public static function delete(?string $reference): void
    {
        if (blank($reference) || self::isExternalReference($reference)) {
            return;
        }

        [$disk, $path] = self::extractDiskAndPath($reference);

        if ($disk !== null) {
            self::deleteFromDisk($disk, $path);

            return;
        }

        $legacyPath = self::normalizeLegacyPath($path);

        if ($legacyPath !== null) {
            self::deleteFromDisk(self::LEGACY_DISK, $legacyPath);
        }
    }

    public static function url(?string $reference): ?string
    {
        if (blank($reference)) {
            return null;
        }

        if (self::isExternalReference($reference)) {
            return $reference;
        }

        [$disk, $path] = self::extractDiskAndPath($reference);

        if ($disk !== null) {
            return self::urlForDisk($disk, $path);
        }

        $legacyPath = self::normalizeLegacyPath($path);

        if ($legacyPath !== null && Storage::disk(self::LEGACY_DISK)->exists($legacyPath)) {
            return self::urlForDisk(self::LEGACY_DISK, $legacyPath);
        }

        $publicAsset = public_path(ltrim($path, '/'));

        if (is_file($publicAsset)) {
            return asset(ltrim($path, '/'));
        }

        return null;
    }

    public static function dataUri(?string $reference): ?string
    {
        if (blank($reference)) {
            return null;
        }

        if (Str::startsWith($reference, 'data:image/')) {
            return $reference;
        }

        if (filter_var($reference, FILTER_VALIDATE_URL)) {
            $contents = @file_get_contents($reference);

            if ($contents === false) {
                return null;
            }

            return self::toDataUri($contents, self::guessMimeType($reference));
        }

        [$disk, $path] = self::extractDiskAndPath($reference);

        if ($disk !== null) {
            return self::dataUriFromDisk($disk, $path);
        }

        $legacyPath = self::normalizeLegacyPath($path);

        if ($legacyPath !== null) {
            $dataUri = self::dataUriFromDisk(self::LEGACY_DISK, $legacyPath);

            if ($dataUri !== null) {
                return $dataUri;
            }
        }

        foreach (self::publicPathCandidates($path) as $candidate) {
            if (! is_file($candidate)) {
                continue;
            }

            $contents = @file_get_contents($candidate);

            if ($contents === false) {
                continue;
            }

            return self::toDataUri($contents, mime_content_type($candidate) ?: self::guessMimeType($candidate));
        }

        return null;
    }

    public static function configuredDisk(): string
    {
        return config('filesystems.profile_photos_disk', self::LEGACY_DISK);
    }

    private static function formatReference(string $disk, string $path): string
    {
        return "{$disk}:{$path}";
    }

    private static function extractDiskAndPath(string $reference): array
    {
        $knownDisks = [self::LEGACY_DISK, self::CLOUDINARY_DISK];

        foreach ($knownDisks as $disk) {
            $prefix = $disk . ':';

            if (str_starts_with($reference, $prefix)) {
                return [$disk, ltrim(substr($reference, strlen($prefix)), '/')];
            }
        }

        return [null, $reference];
    }

    private static function deleteFromDisk(string $disk, string $path): void
    {
        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable) {
            // If the file is already gone we keep the profile update flow going.
        }
    }

    private static function urlForDisk(string $disk, string $path): ?string
    {
        if ($disk === self::CLOUDINARY_DISK) {
            return self::cloudinaryUrl($path);
        }

        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver === 'local') {
            return '/storage/' . ltrim($path, '/');
        }

        return Storage::disk($disk)->url($path);
    }

    private static function cloudinaryUrl(string $path): ?string
    {
        try {
            return (string) app(Cloudinary::class)
                ->image(self::cloudinaryPublicId($path))
                ->toUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function cloudinaryPublicId(string $path): string
    {
        $pathInfo = pathinfo(str_replace('\\', '/', $path));
        $directory = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? $pathInfo['basename'] ?? $path;

        if ($directory === '' || $directory === '.') {
            return $filename;
        }

        return trim($directory, '/') . '/' . $filename;
    }

    private static function dataUriFromDisk(string $disk, string $path): ?string
    {
        try {
            $contents = Storage::disk($disk)->get($path);
            $mime = Storage::disk($disk)->mimeType($path) ?: self::guessMimeType($path);

            return self::toDataUri($contents, $mime);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function toDataUri(string $contents, string $mime): string
    {
        return "data:{$mime};base64," . base64_encode($contents);
    }

    private static function normalizeLegacyPath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = ltrim(substr($path, strlen('storage/')), '/');
        }

        return $path;
    }

    private static function publicPathCandidates(string $path): array
    {
        $path = ltrim($path, '/');

        return array_values(array_unique([
            public_path('storage/' . $path),
            public_path($path),
        ]));
    }

    private static function isExternalReference(string $reference): bool
    {
        return Str::startsWith($reference, ['http://', 'https://', 'data:image/']);
    }

    private static function guessMimeType(string $path): string
    {
        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?? $path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}
