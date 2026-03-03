<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SizeController extends Controller
{
    public function index()
    {
        $sizes = Size::orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $sizes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sizes',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $size = Size::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $size
        ], 201);
    }

    public function show(Size $size)
    {
        return response()->json([
            'status' => 'success',
            'data' => $size
        ]);
    }

    public function update(Request $request, Size $size)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sizes,name,' . $size->id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $size->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $size
        ]);
    }

    public function destroy(Size $size)
    {
        $size->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Size deleted successfully'
        ]);
    }
}
