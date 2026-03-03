<?php

namespace App\Http\Controllers;

use App\Models\SpecialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpecialCategoryController extends Controller
{
    public function index()
    {
        $specialCategories = SpecialCategory::all();

        return response()->json([
            'status' => 'success',
            'data' => $specialCategories
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $specialCategory = SpecialCategory::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $specialCategory
        ], 201);
    }

    public function show(SpecialCategory $specialCategory)
    {
        return response()->json([
            'status' => 'success',
            'data' => $specialCategory
        ]);
    }

    public function update(Request $request, SpecialCategory $specialCategory)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $specialCategory->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $specialCategory
        ]);
    }

    public function destroy(SpecialCategory $specialCategory)
    {
        $specialCategory->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Special category deleted successfully'
        ]);
    }
}
