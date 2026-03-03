<?php

namespace App\Http\Controllers;

use App\Models\Continent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContinentController extends Controller
{
    public function index()
    {
        $continentes = Continent::all();

        $response = [
            'status' => 'success',
            'data' => $continentes
        ];

        return response()->json($response);
    }

    public function store(Request $request)
    {
        $validador = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validador->fails()) {
            return response()->json($validador->errors(), 422);
        }

        $continente = Continent::create($request->all());

        $response = [
            'status' => 'success',
            'data' => $continente
        ];

        return response()->json($response);
    }

    public function show(Continent $continent)
    {
        $response = [
            'status' => 'success',
            'data' => $continent
        ];

        return response()->json($response);
    }

    public function update(Request $request, Continent $continent)
    {
        $continente = $continent->update($request->all());

        $response = [
            'status' => 'success',
            'data' => $continent
        ];

        return response()->json($response);
    }

    public function destroy(Continent $continent)
    {
        $continent->delete();

        $response = [
            'status' => 'success',
            'data' => $continent
        ];

        return response()->json($response);
    }
}
