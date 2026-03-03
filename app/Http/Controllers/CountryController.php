<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    public function index()
    {
        $paises = Country::with('continent')->get();

        $response = [
            'status' => 'success',
            'data' => $paises
        ];

        return response()->json($response);
    }

    
    public function store(Request $request)
    {
        $validador = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'continent_id' => 'required|exists:continents,id',
        ]);

        if ($validador->fails()) {
            return response()->json($validador->errors(), 422);
        }

        $pais = Country::create($request->all());

        $response = [
            'status' => 'success',
            'data' => $pais
        ];

        return response()->json($response);
    }

    public function show(Country $country)
    {
        $country->load('continent');
        
        $response = [
            'status' => 'success',
            'data' => $country
        ];

        return response()->json($response);
    }

    public function update(Request $request, Country $country)
    {
        $country->update($request->all());

        $response = [
            'status' => 'success',
            'data' => $country
        ];

        return response()->json($response);
    }

    public function destroy(Country $country)
    {
        $country->delete();

        $response = [
            'status' => 'success',
            'data' => $country
        ];

        return response()->json($response);
    }
}
