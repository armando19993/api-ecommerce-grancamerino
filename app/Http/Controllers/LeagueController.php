<?php

namespace App\Http\Controllers;

use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeagueController extends Controller
{
    public function index()
    {
        $leagues = League::with('country')->get();

        return response()->json([
            'status' => 'success',
            'data' => $leagues
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $league = League::create($request->all());
        $league->load('country');

        return response()->json([
            'status' => 'success',
            'data' => $league
        ], 201);
    }

    public function show(League $league)
    {
        $league->load('country');

        return response()->json([
            'status' => 'success',
            'data' => $league
        ]);
    }

    public function update(Request $request, League $league)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $league->update($request->all());
        $league->load('country');

        return response()->json([
            'status' => 'success',
            'data' => $league
        ]);
    }

    public function destroy(League $league)
    {
        $league->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'League deleted successfully'
        ]);
    }
}
