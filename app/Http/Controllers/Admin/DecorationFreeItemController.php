<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DecorationFreeItem;
use App\Models\Decoration;
use Illuminate\Http\Request;

class DecorationFreeItemController extends Controller
{
    /**
     * Display a listing of free items for a specific decoration.
     */
    public function index($decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);
        $freeItems = $decoration->freeItems;
        
        return response()->json([
            'success' => true,
            'data' => $freeItems
        ]);
    }

    /**
     * Store a newly created free item.
     */
    public function store(Request $request, $decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);
        
        $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $freeItem = $decoration->freeItems()->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Free item created successfully',
            'data' => $freeItem
        ], 201);
    }

    /**
     * Display the specified free item.
     */
    public function show($decorationId, $id)
    {
        $freeItem = DecorationFreeItem::where('decoration_id', $decorationId)
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $freeItem
        ]);
    }

    /**
     * Update the specified free item.
     */
    public function update(Request $request, $decorationId, $id)
    {
        $freeItem = DecorationFreeItem::where('decoration_id', $decorationId)
            ->findOrFail($id);
        
        $request->validate([
            'item_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        $freeItem->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Free item updated successfully',
            'data' => $freeItem
        ]);
    }

    /**
     * Remove the specified free item.
     */
    public function destroy($decorationId, $id)
    {
        $freeItem = DecorationFreeItem::where('decoration_id', $decorationId)
            ->findOrFail($id);
        
        $freeItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Free item deleted successfully'
        ]);
    }
}
