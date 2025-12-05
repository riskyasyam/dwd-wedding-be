<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Event::with('images');

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('start_date', '<=', $request->end_date);
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'required|string|max:255',
            'short_description' => 'required|string',
            'full_description' => 'required|string',
            'organizer' => 'nullable|string|max:255',
            'banner_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $bannerPath = null;
        if ($request->hasFile('banner_image')) {
            $filename = time() . '_banner.' . $request->file('banner_image')->getClientOriginalExtension();
            $bannerPath = $request->file('banner_image')->storeAs('events', $filename, 'public');
        }

        $event = Event::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'banner_image' => '/storage/' . $bannerPath,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'short_description' => $request->short_description,
            'full_description' => $request->full_description,
            'organizer' => $request->organizer,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($identifier)
    {
        // Try to find by ID first, if not numeric then find by slug
        if (is_numeric($identifier)) {
            $event = Event::with('images')->findOrFail($identifier);
        } else {
            $event = Event::with('images')->where('slug', $identifier)->firstOrFail();
        }

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'required|string|max:255',
            'short_description' => 'required|string',
            'full_description' => 'required|string',
            'organizer' => 'nullable|string|max:255',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $updateData = [
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
            'short_description' => $request->short_description,
            'full_description' => $request->full_description,
            'organizer' => $request->organizer,
        ];

        if ($request->hasFile('banner_image')) {
            // Delete old banner
            $oldBannerPath = str_replace('/storage/', '', $event->banner_image);
            \Storage::disk('public')->delete($oldBannerPath);
            
            // Upload new banner
            $filename = time() . '_banner.' . $request->file('banner_image')->getClientOriginalExtension();
            $bannerPath = $request->file('banner_image')->storeAs('events', $filename, 'public');
            $updateData['banner_image'] = '/storage/' . $bannerPath;
        }

        $event->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ]);
    }

    /**
     * Upload gallery images for event
     */
    public function uploadImages(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('events', $filename, 'public');

            $eventImage = $event->images()->create([
                'image' => '/storage/' . $path
            ]);

            $uploadedImages[] = $eventImage;
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $uploadedImages
        ]);
    }

    /**
     * Delete single event gallery image
     */
    public function deleteImage($imageId)
    {
        $image = \App\Models\EventImage::findOrFail($imageId);
        
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
