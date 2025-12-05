<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Advertisement::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active == 'true' || $request->is_active == '1');
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $advertisements = $query->orderBy('order')->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $advertisements
        ]);
    }

    /**
     * Get active advertisements for customer view.
     */
    public function activeAds()
    {
        $advertisements = Advertisement::active()
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $advertisements
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'description' => 'nullable|string',
            'link_url' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Upload image
        $image = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('advertisements', $filename, 'public');

        $advertisement = Advertisement::create([
            'title' => $request->title,
            'image' => '/storage/' . $path,
            'description' => $request->description,
            'link_url' => $request->link_url,
            'order' => $request->order ?? 0,
            'is_active' => $request->is_active ?? true,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created successfully',
            'data' => $advertisement
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $advertisement = Advertisement::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $advertisement
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $advertisement = Advertisement::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'description' => 'nullable|string',
            'link_url' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $data = $request->only(['title', 'description', 'link_url', 'order', 'is_active', 'start_date', 'end_date']);

        // Update image if new one uploaded
        if ($request->hasFile('image')) {
            // Delete old image
            $oldImagePath = str_replace('/storage/', '', $advertisement->image);
            Storage::disk('public')->delete($oldImagePath);
            
            $image = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('advertisements', $filename, 'public');
            $data['image'] = '/storage/' . $path;
        }

        $advertisement->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated successfully',
            'data' => $advertisement
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $advertisement = Advertisement::findOrFail($id);
        
        // Delete image from storage
        $imagePath = str_replace('/storage/', '', $advertisement->image);
        Storage::disk('public')->delete($imagePath);
        
        $advertisement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully'
        ]);
    }

    /**
     * Update order of advertisements.
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:advertisements,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            Advertisement::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Advertisement order updated successfully'
        ]);
    }
}
