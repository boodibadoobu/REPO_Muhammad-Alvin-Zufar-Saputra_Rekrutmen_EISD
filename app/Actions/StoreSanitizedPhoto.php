<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class StoreSanitizedPhoto
{
    public function handle(UploadedFile $photo, string $directory, string $field = 'photo'): string
    {
        try {
            // Bound decoded memory before handing the image to the decoder.
            $dimensions = @getimagesize($photo->getPathname());
            if (! $dimensions || $dimensions[0] * $dimensions[1] > 8000000) {
                throw new RuntimeException('Unsupported image dimensions.');
            }

            $extension = match ($dimensions['mime']) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => throw new RuntimeException('Unsupported image format.'),
            };

            $image = ImageManager::gd(autoOrientation: true, decodeAnimation: false, strip: true)
                ->read($photo->getPathname());
            $encoded = $image->encodeByExtension($extension);
        } catch (Throwable) {
            // Never expose decoder messages or store the original as a fallback.
            throw ValidationException::withMessages([
                $field => 'Foto tidak dapat diproses dengan aman. Gunakan JPG, PNG, atau WebP yang valid, maksimal 8 megapiksel.',
            ]);
        }

        $path = $directory.'/'.Str::uuid().'.'.$extension;
        if (! Storage::disk('public')->put($path, (string) $encoded)) {
            throw ValidationException::withMessages([
                $field => 'Foto gagal disimpan. Silakan coba lagi.',
            ]);
        }

        return $path;
    }
}
