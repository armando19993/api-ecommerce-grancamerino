<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favorites()
            ->with('product')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $favorites
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id'
        ]);

        // Check if already favorited
        $exists = $request->user()
            ->favorites()
            ->where('product_id', $validated['product_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product already in favorites'
            ], 409);
        }

        $favorite = $request->user()->favorites()->create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product added to favorites',
            'data' => $favorite->load('product')
        ], 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $favorite = $request->user()
            ->favorites()
            ->where('product_id', $product->id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found in favorites'
            ], 404);
        }

        $favorite->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product removed from favorites'
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $favorite = $request->user()
            ->favorites()
            ->where('product_id', $product->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $message = 'Product removed from favorites';
            $isFavorited = false;
        } else {
            $favorite = $request->user()->favorites()->create([
                'product_id' => $product->id
            ]);
            $message = 'Product added to favorites';
            $isFavorited = true;
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'is_favorited' => $isFavorited,
                'favorite' => $isFavorited ? $favorite->load('product') : null
            ]
        ]);
    }
}
