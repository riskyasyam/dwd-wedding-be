<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DecorationAdvantage;
use App\Models\Decoration;
use Illuminate\Http\Request;

class DecorationAdvantageController extends Controller
{
    /**
     * Display a listing of advantages for a decoration.
     */
    public function index($decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);
        $advantages = $decoration->advantages;

        return response()->json([
            'success' => true,
            'data' => $advantages
        ]);
    }

    /**
     * Store a newly created advantage.
     */
    public function store(Request $request, $decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
        ]);

        $advantage = $decoration->advantages()->create([
            'title' => $request->title,
            'description' => $request->description,
            'order' => $request->order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advantage created successfully',
            'data' => $advantage
        ], 201);
    }

    /**
     * Display the specified advantage.
     */
    public function show($decorationId, $id)
    {
        $advantage = DecorationAdvantage::where('decoration_id', $decorationId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $advantage
        ]);
    }

    /**
     * Update the specified advantage.
     */
    public function update(Request $request, $decorationId, $id)
    {
        $advantage = DecorationAdvantage::where('decoration_id', $decorationId)
            ->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
        ]);

        $advantage->update([
            'title' => $request->title,
            'description' => $request->description,
            'order' => $request->order ?? $advantage->order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advantage updated successfully',
            'data' => $advantage
        ]);
    }

    /**
     * Remove the specified advantage.
     */
    public function destroy($decorationId, $id)
    {
        $advantage = DecorationAdvantage::where('decoration_id', $decorationId)
            ->findOrFail($id);
            
        $advantage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advantage deleted successfully'
        ]);
    }
}
