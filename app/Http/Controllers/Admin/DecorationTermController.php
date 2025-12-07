<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DecorationTerm;
use App\Models\Decoration;
use Illuminate\Http\Request;

class DecorationTermController extends Controller
{
    /**
     * Display a listing of terms for a decoration.
     */
    public function index($decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);
        $terms = $decoration->terms;

        return response()->json([
            'success' => true,
            'data' => $terms
        ]);
    }

    /**
     * Store a newly created term.
     */
    public function store(Request $request, $decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);

        $request->validate([
            'term' => 'required|string',
            'order' => 'nullable|integer|min:0',
        ]);

        $term = $decoration->terms()->create([
            'term' => $request->term,
            'order' => $request->order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Term created successfully',
            'data' => $term
        ], 201);
    }

    /**
     * Display the specified term.
     */
    public function show($decorationId, $id)
    {
        $term = DecorationTerm::where('decoration_id', $decorationId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $term
        ]);
    }

    /**
     * Update the specified term.
     */
    public function update(Request $request, $decorationId, $id)
    {
        $term = DecorationTerm::where('decoration_id', $decorationId)
            ->findOrFail($id);

        $request->validate([
            'term' => 'required|string',
            'order' => 'nullable|integer|min:0',
        ]);

        $term->update([
            'term' => $request->term,
            'order' => $request->order ?? $term->order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Term updated successfully',
            'data' => $term
        ]);
    }

    /**
     * Remove the specified term.
     */
    public function destroy($decorationId, $id)
    {
        $term = DecorationTerm::where('decoration_id', $decorationId)
            ->findOrFail($id);
            
        $term->delete();

        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully'
        ]);
    }
}
