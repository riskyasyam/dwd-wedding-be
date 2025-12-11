<?php

namespace App\Helpers;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ImageOptimizer
{
    /**
     * Optimize and compress uploaded image
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path Storage path (e.g., 'decorations', 'inspirations')
     * @param int $maxWidth Maximum width for image
     * @param int $quality Image quality (1-100)
     * @return array ['original' => path, 'thumbnail' => path]
     */
    public static function optimizeAndSave($file, string $path, int $maxWidth = 1920, int $quality = 80): array
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Create optimized version
        $image = Image::make($file);
        
        // Resize if image is too large
        if ($image->width() > $maxWidth) {
            $image->resize($maxWidth, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        // Optimize and save
        $optimizedPath = storage_path('app/public/' . $path . '/' . $filename);
        $image->save($optimizedPath, $quality);
        
        // Create thumbnail (300px width)
        $thumbnailFilename = 'thumb_' . $filename;
        $thumbnail = Image::make($file);
        $thumbnail->resize(300, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $thumbnailPath = storage_path('app/public/' . $path . '/thumbnails/' . $thumbnailFilename);
        
        // Ensure thumbnails directory exists
        if (!file_exists(storage_path('app/public/' . $path . '/thumbnails'))) {
            mkdir(storage_path('app/public/' . $path . '/thumbnails'), 0755, true);
        }
        
        $thumbnail->save($thumbnailPath, 70);
        
        return [
            'original' => '/storage/' . $path . '/' . $filename,
            'thumbnail' => '/storage/' . $path . '/thumbnails/' . $thumbnailFilename,
        ];
    }

    /**
     * Delete image and its thumbnail
     * 
     * @param string $imagePath
     * @return bool
     */
    public static function delete(string $imagePath): bool
    {
        if (!$imagePath) {
            return false;
        }

        // Delete original
        $path = str_replace('/storage/', '', $imagePath);
        Storage::disk('public')->delete($path);

        // Delete thumbnail
        $pathParts = pathinfo($path);
        $thumbnailPath = $pathParts['dirname'] . '/thumbnails/thumb_' . $pathParts['basename'];
        Storage::disk('public')->delete($thumbnailPath);

        return true;
    }

    /**
     * Get thumbnail URL from original image path
     * 
     * @param string $imagePath
     * @return string
     */
    public static function getThumbnailUrl(string $imagePath): string
    {
        if (!$imagePath) {
            return '';
        }

        $pathParts = pathinfo($imagePath);
        $thumbnailPath = $pathParts['dirname'] . '/thumbnails/thumb_' . $pathParts['basename'];
        
        return $thumbnailPath;
    }
}
