<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $addresses
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'street' => 'required|string|max:255',
            'street_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'is_primary' => 'boolean'
        ]);

        $address = $request->user()->addresses()->create($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Address created successfully',
            'data' => $address
        ], 201);
    }

    public function show(Request $request, Address $address): JsonResponse
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $address
        ]);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found'
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'gender' => 'sometimes|in:male,female,other',
            'street' => 'sometimes|string|max:255',
            'street_line_2' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'postal_code' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:255',
            'is_primary' => 'boolean'
        ]);

        $address->update($validated);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Address updated successfully',
            'data' => $address
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found'
            ], 404);
        }

        $address->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Address deleted successfully'
        ]);
    }

    public function setPrimary(Request $request, Address $address): JsonResponse
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Address not found'
            ], 404);
        }

        // Set this address as primary (will automatically set others to false)
        $address->update(['is_primary' => true]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Address set as primary successfully',
            'data' => $address
        ]);
    }
}
