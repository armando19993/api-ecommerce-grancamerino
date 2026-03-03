<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Team::with(['country.continent', 'league']);

        // Filtrar por tipo (club o national_team)
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Filtrar por país
        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        // Filtrar por liga
        if ($request->has('league_id') && !empty($request->league_id)) {
            $query->where('league_id', $request->league_id);
        }

        // Búsqueda por nombre
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $teams = $query->orderBy('name')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $teams
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:club,national_team',
            'country_id' => 'required|exists:countries,id',
            'league_id' => 'nullable|exists:leagues,id',
            'image_url' => 'nullable|url',
        ]);

        $team = Team::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $team->load(['country', 'league'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        return response()->json([
            'status' => 'success',
            'data' => $team->load(['country', 'league'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:club,national_team',
            'country_id' => 'sometimes|exists:countries,id',
            'league_id' => 'sometimes|nullable|exists:leagues,id',
            'image_url' => 'sometimes|nullable|url',
        ]);

        $team->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $team->load(['country', 'league'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $team->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Team deleted successfully'
        ]);
    }
}
