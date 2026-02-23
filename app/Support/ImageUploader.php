<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageUploader
{
    /**
     * Store an uploaded image in the public products directory and return the relative path.
     *
     * @param UploadedFile $file
     * @return string
     */
    public static function storeProductImage(UploadedFile $file): string
    {
        $filename = Str::uuid()->toString() . '.' . $file->extension();
        $path = $file->storeAs('products', $filename, 'public');

        // storeAs returns relative path
        return $path;
    }

    /**
     * Store an uploaded image in the public categories directory and return the relative path.
     *
     * @param UploadedFile $file
     * @return string
     */
    public static function storeCategoryImage(UploadedFile $file): string
    {
        $filename = Str::uuid()->toString() . '.' . $file->extension();
        $path = $file->storeAs('categories', $filename, 'public');

        return $path;
    }
}
