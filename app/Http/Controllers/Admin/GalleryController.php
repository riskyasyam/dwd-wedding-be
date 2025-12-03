<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Gallery::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $galleries = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $galleries
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'image_url' => 'required|url',
            'description' => 'nullable|string',
        ]);

        $gallery = Gallery::create([
            'title' => $request->title,
            'category' => $request->category,
            'image_url' => $request->image_url,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gallery item created successfully',
            'data' => $gallery
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $gallery = Gallery::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $gallery
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'image_url' => 'required|url',
            'description' => 'nullable|string',
        ]);

        $gallery->update([
            'title' => $request->title,
            'category' => $request->category,
            'image_url' => $request->image_url,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gallery item updated successfully',
            'data' => $gallery
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $gallery = Gallery::findOrFail($id);
        $gallery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gallery item deleted successfully'
        ]);
    }
}
