<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;

class ImageService
{
    public function uploadAndConvertToWebp(UploadedFile $file, string $folder, int $quality = 80): string
    {
        $manager = ImageManager::usingDriver(Driver::class);

        $image = $manager->decode($file);

        $encodedImage = $image->encodeUsingFormat(Format::WEBP, $quality);

        $filename = $folder . '/' . Str::random(40) . '.webp';

        Storage::disk('public')->put($filename, (string) $encodedImage);

        return $filename;
    }
}
