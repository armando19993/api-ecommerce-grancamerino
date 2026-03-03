<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CloudinaryService
{
    /**
     * Upload image to Cloudinary
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     */
    public static function uploadImage($file, $folder = 'products', $options = [])
    {
        try {
            // Verificar que Cloudinary esté configurado
            if (empty(config('cloudinary.cloud_name')) || 
                empty(config('cloudinary.api_key')) || 
                empty(config('cloudinary.api_secret'))) {
                return [
                    'success' => false,
                    'error' => 'Cloudinary is not configured. Please check your .env file.',
                ];
            }

            $defaultOptions = [
                'folder' => $folder,
                'resource_type' => 'image',
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ];

            // Merge options, allowing custom options to override defaults
            $uploadOptions = array_merge($defaultOptions, $options);

            $result = Cloudinary::upload($file->getRealPath(), $uploadOptions);

            // Verificar que el resultado no sea null
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Upload failed. Cloudinary returned null.',
                ];
            }

            return [
                'success' => true,
                'url' => $result->getSecurePath(),
                'public_id' => $result->getPublicId(),
                'size' => $result->getSize(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete image from Cloudinary
     *
     * @param string $publicId
     * @return array
     */
    public static function deleteImage($publicId)
    {
        try {
            // Verificar que Cloudinary esté configurado
            if (empty(config('cloudinary.cloud_name')) || 
                empty(config('cloudinary.api_key')) || 
                empty(config('cloudinary.api_secret'))) {
                return [
                    'success' => false,
                    'error' => 'Cloudinary is not configured. Please check your .env file.',
                ];
            }

            $result = Cloudinary::destroy($publicId);

            // Verificar que el resultado no sea null
            if (!$result || !isset($result['result'])) {
                return [
                    'success' => false,
                    'error' => 'Delete failed. Cloudinary returned invalid response.',
                ];
            }

            return [
                'success' => $result['result'] === 'ok',
                'message' => $result['result'] === 'ok' ? 'Image deleted successfully' : 'Failed to delete image',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate optimized image URL
     *
     * @param string $publicId
     * @param array $transformations
     * @return string
     */
    public static function getOptimizedUrl($publicId, $transformations = [])
    {
        $defaultTransformations = [
            'quality' => 'auto',
            'fetch_format' => 'auto',
            'crop' => 'fill',
            'gravity' => 'auto',
        ];

        $transformations = array_merge($defaultTransformations, $transformations);

        return Cloudinary::getUrl($publicId, $transformations);
    }

    /**
     * Generate thumbnail URL
     *
     * @param string $publicId
     * @param int $width
     * @param int $height
     * @return string
     */
    public static function getThumbnailUrl($publicId, $width = 200, $height = 200)
    {
        return self::getOptimizedUrl($publicId, [
            'width' => $width,
            'height' => $height,
            'crop' => 'thumb',
            'gravity' => 'center',
        ]);
    }

    /**
     * Generate responsive image URLs
     *
     * @param string $publicId
     * @param array $breakpoints
     * @return array
     */
    public static function getResponsiveUrls($publicId, $breakpoints = [320, 640, 960, 1280])
    {
        $urls = [];
        
        foreach ($breakpoints as $width) {
            $urls[$width] = self::getOptimizedUrl($publicId, [
                'width' => $width,
                'crop' => 'scale',
            ]);
        }

        return $urls;
    }
}
