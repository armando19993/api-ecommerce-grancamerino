<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Cloudinary\Cloudinary;

class ImageController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        // Configuración de Cloudinary
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key' => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
        ]);
    }

    /**
     * Get Guzzle client with SSL verification disabled for local development
     */
    protected function getGuzzleOptions()
    {
        $options = [];
        
        // En desarrollo local, deshabilitar verificación SSL
        if (config('app.env') === 'local' || config('app.debug')) {
            $options['verify'] = false;
        }
        
        return $options;
    }

    /**
     * Upload image
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'folder' => 'nullable|string|max:100',
            'type' => 'nullable|string|in:category,league,country,team,product',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al subir la imagen',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('image');
            
            // Verificar que el archivo se haya subido correctamente
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Archivo inválido',
                    'error' => 'El archivo no es válido o no se recibió correctamente'
                ], 400);
            }

            $type = $request->input('type', 'product');
            $folder = $request->input('folder', "{$type}s");

            // Configurar opciones según el tipo
            $options = [
                'folder' => $folder,
                'crop' => 'limit',
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'resource_type' => 'image',
            ];
            
            switch ($type) {
                case 'category':
                case 'league':
                    $options['width'] = 400;
                    $options['height'] = 400;
                    break;
                case 'country':
                    $options['width'] = 300;
                    $options['height'] = 200;
                    break;
                case 'team':
                    $options['width'] = 300;
                    $options['height'] = 300;
                    break;
                case 'product':
                default:
                    $options['width'] = 1200;
                    $options['height'] = 1200;
                    break;
            }

            // Configurar cliente HTTP para desarrollo local
            if (config('app.env') === 'local' || config('app.debug')) {
                // Crear cliente Guzzle sin verificación SSL
                $httpClient = new \GuzzleHttp\Client([
                    'verify' => false,
                    'timeout' => 60,
                ]);
                
                // Configurar Cloudinary para usar este cliente
                \Cloudinary\Configuration\Configuration::instance()->cloud->apiKey = config('cloudinary.api_key');
                \Cloudinary\Configuration\Configuration::instance()->cloud->apiSecret = config('cloudinary.api_secret');
                \Cloudinary\Configuration\Configuration::instance()->cloud->cloudName = config('cloudinary.cloud_name');
            }

            // Subir imagen a Cloudinary
            $uploadResult = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                $options
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $uploadResult['secure_url'],
                    'public_id' => $uploadResult['public_id'],
                    'width' => $uploadResult['width'] ?? null,
                    'height' => $uploadResult['height'] ?? null,
                ]
            ]);

        } catch (\Cloudinary\Api\Exception\ApiError $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de API de Cloudinary',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al subir la imagen',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Delete image
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'public_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la imagen',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->cloudinary->uploadApi()->destroy($request->public_id);

            if (isset($result['result']) && $result['result'] === 'ok') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Imagen eliminada correctamente'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se pudo eliminar la imagen',
                    'error' => $result['result'] ?? 'Unknown error'
                ], 500);
            }
        } catch (\Cloudinary\Api\Exception\ApiError $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de API de Cloudinary',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get optimized image URL
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function optimize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'public_id' => 'required|string',
            'width' => 'nullable|integer|min:1|max:2000',
            'height' => 'nullable|integer|min:1|max:2000',
            'crop' => 'nullable|string|in:fill,scale,thumb,limit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transformations = [
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ];
            
            if ($request->width) $transformations['width'] = $request->width;
            if ($request->height) $transformations['height'] = $request->height;
            if ($request->crop) $transformations['crop'] = $request->crop;

            $url = $this->cloudinary->image($request->public_id)
                ->resize(
                    \Cloudinary\Transformation\Resize::scale()
                        ->width($transformations['width'] ?? null)
                        ->height($transformations['height'] ?? null)
                )
                ->toUrl();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'optimized_url' => $url,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar URL optimizada',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
