<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Decoration;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DecorationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Decoration::with('images', 'freeItems');

        // Filter by region
        if ($request->has('region')) {
            $query->where('region', $request->region);
        }

        // Filter by deals
        if ($request->has('is_deals')) {
            $query->where('is_deals', $request->boolean('is_deals'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $decorations = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $decorations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'description' => 'required|string',
            'base_price' => 'required|integer|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'discount_start_date' => 'nullable|date',
            'discount_end_date' => 'nullable|date|after_or_equal:discount_start_date',
            'is_deals' => 'boolean',
        ]);

        $discountPercent = $request->discount_percent ?? 0;
        $finalPrice = $request->base_price - ($request->base_price * $discountPercent / 100);

        $decoration = Decoration::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'region' => $request->region,
            'description' => $request->description,
            'base_price' => $request->base_price,
            'discount_percent' => $discountPercent,
            'final_price' => $finalPrice,
            'discount_start_date' => $request->discount_start_date,
            'discount_end_date' => $request->discount_end_date,
            'is_deals' => $request->boolean('is_deals', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Decoration created successfully',
            'data' => $decoration
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $decoration = Decoration::with('images', 'freeItems')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $decoration
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $decoration = Decoration::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'description' => 'required|string',
            'base_price' => 'required|integer|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'discount_start_date' => 'nullable|date',
            'discount_end_date' => 'nullable|date|after_or_equal:discount_start_date',
            'is_deals' => 'boolean',
        ]);

        $discountPercent = $request->discount_percent ?? 0;
        $finalPrice = $request->base_price - ($request->base_price * $discountPercent / 100);

        $decoration->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'region' => $request->region,
            'description' => $request->description,
            'base_price' => $request->base_price,
            'discount_percent' => $discountPercent,
            'final_price' => $finalPrice,
            'discount_start_date' => $request->discount_start_date,
            'discount_end_date' => $request->discount_end_date,
            'is_deals' => $request->boolean('is_deals', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Decoration updated successfully',
            'data' => $decoration
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $decoration = Decoration::findOrFail($id);
        $decoration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Decoration deleted successfully'
        ]);
    }

    /**
     * Upload images for decoration
     */
    public function uploadImages(Request $request, $id)
    {
        $decoration = Decoration::findOrFail($id);

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('decorations', $filename, 'public');

            $decorationImage = $decoration->images()->create([
                'image' => '/storage/' . $path
            ]);

            $uploadedImages[] = $decorationImage;
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $uploadedImages
        ]);
    }

    /**
     * Delete single decoration image
     */
    public function deleteImage($imageId)
    {
        $image = \App\Models\DecorationImage::findOrFail($imageId);
        
        // Delete file from storage
        $imagePath = str_replace('/storage/', '', $image->image);
        \Storage::disk('public')->delete($imagePath);
        
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }
}
