<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        $coupons = Coupon::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->withCount('orders')
            ->with(['orders' => function ($query) {
                $query->select('id', 'coupon_id', 'order_number', 'total_amount', 'discount_amount', 'created_at')
                    ->orderBy('created_at', 'desc');
            }])
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $coupons
        ]);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $coupon
        ]);
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0'
        ]);

        $coupon = Coupon::where('code', strtoupper($validated['code']))->first();

        if (!$coupon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid coupon code'
            ], 404);
        }

        if (!$coupon->isValid()) {
            $message = 'Coupon is not valid';
            if (!$coupon->is_active) $message = 'Coupon is inactive';
            elseif ($coupon->starts_at && now()->lt($coupon->starts_at)) $message = 'Coupon not yet active';
            elseif ($coupon->expires_at && now()->gt($coupon->expires_at)) $message = 'Coupon has expired';
            elseif ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) $message = 'Coupon usage limit reached';

            return response()->json([
                'status' => 'error',
                'message' => $message
            ], 400);
        }

        $discount = $coupon->calculateDiscount((float) $validated['amount']);

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon is valid',
            'data' => [
                'coupon' => $coupon,
                'discount_amount' => $discount,
                'final_amount' => max(0, (float) $validated['amount'] - $discount)
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean'
        ]);

        $coupon = Coupon::create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Coupon created successfully',
            'data' => $coupon
        ], 201);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean'
        ]);

        $coupon->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Coupon updated successfully',
            'data' => $coupon
        ]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Coupon deleted successfully'
        ]);
    }
}
