<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inspiration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InspirationController extends Controller
{
    /**
     * Display a listing of inspirations.
     */
    public function index(Request $request)
    {
        $query = Inspiration::query();

        // Filter by color (search in JSON array)
        if ($request->has('color')) {
            $query->whereJsonContains('colors', $request->color);
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', $request->location);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Order by liked count (popular first) or created date
        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        
        $query->orderBy($orderBy, $orderDir);

        $inspirations = $query->paginate(15);

        // Add is_saved flag if user is authenticated (optimized to prevent N+1)
        if (auth()->check()) {
            $user = auth()->user();
            
            // Get all saved inspiration IDs for current user in one query
            $savedIds = $user->savedInspirations()->pluck('inspirations.id')->toArray();
            
            $inspirations->getCollection()->transform(function ($inspiration) use ($savedIds) {
                $inspiration->is_saved = in_array($inspiration->id, $savedIds);
                return $inspiration;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $inspirations
        ]);
    }

    /**
     * Store a newly created inspiration.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // Max 10MB
            'colors' => 'required|array|min:1', // Array of colors
            'colors.*' => 'string|max:255', // Each color is a string
            'location' => 'required|string|max:255',
        ]);

        // Upload image
        $image = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('inspirations', $filename, 'public');

        $inspiration = Inspiration::create([
            'title' => $request->title,
            'image' => '/storage/' . $path,
            'colors' => $request->colors, // Store as array (will be JSON)
            'location' => $request->location,
            'liked_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inspiration created successfully',
            'data' => $inspiration
        ], 201);
    }

    /**
     * Display the specified inspiration.
     */
    public function show($id)
    {
        $inspiration = Inspiration::findOrFail($id);
        
        // Add is_saved flag if user is authenticated
        if (auth()->check()) {
            $user = auth()->user();
            $inspiration->is_saved = $inspiration->savedByUsers()
                ->where('user_id', $user->id)
                ->exists();
        }
        
        return response()->json([
            'success' => true,
            'data' => $inspiration
        ]);
    }

    /**
     * Update the specified inspiration.
     */
    public function update(Request $request, $id)
    {
        $inspiration = Inspiration::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240', // Max 10MB
            'colors' => 'sometimes|required|array|min:1',
            'colors.*' => 'string|max:255',
            'location' => 'sometimes|required|string|max:255',
        ]);

        $data = $request->except('image');

        // Handle image upload if provided
        if ($request->hasFile('image')) {
            // Delete old image
            if ($inspiration->image) {
                $oldPath = str_replace('/storage/', '', $inspiration->image);
                Storage::disk('public')->delete($oldPath);
            }

            // Upload new image
            $image = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('inspirations', $filename, 'public');
            $data['image'] = '/storage/' . $path;
        }

        $inspiration->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Inspiration updated successfully',
            'data' => $inspiration
        ]);
    }

    /**
     * Remove the specified inspiration.
     */
    public function destroy($id)
    {
        $inspiration = Inspiration::findOrFail($id);
        
        // Delete image file
        if ($inspiration->image) {
            $path = str_replace('/storage/', '', $inspiration->image);
            Storage::disk('public')->delete($path);
        }

        $inspiration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inspiration deleted successfully'
        ]);
    }

    /**
     * Toggle like/save inspiration (for customer)
     */
    public function toggleLike(Request $request, $id)
    {
        $inspiration = Inspiration::findOrFail($id);
        $user = auth()->user();

        // Check if user already saved this
        $isSaved = $inspiration->savedByUsers()->where('user_id', $user->id)->exists();

        if ($isSaved) {
            // Unlike/Unsave
            $inspiration->savedByUsers()->detach($user->id);
            $inspiration->decrement('liked_count');
            $message = 'Inspiration removed from your saved list';
            $is_liked = false;
        } else {
            // Like/Save
            $inspiration->savedByUsers()->attach($user->id);
            $inspiration->increment('liked_count');
            $message = 'Inspiration saved to your list';
            $is_liked = true;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'is_liked' => $is_liked,
                'liked_count' => $inspiration->liked_count
            ]
        ]);
    }

    /**
     * Get user's saved inspirations (for customer)
     */
    public function mySaved(Request $request)
    {
        $user = auth()->user();
        $inspirations = $user->savedInspirations()->paginate(15);

        // Add is_saved flag (all are saved since this is saved list)
        $inspirations->getCollection()->transform(function ($inspiration) {
            $inspiration->is_saved = true;
            return $inspiration;
        });

        return response()->json([
            'success' => true,
            'data' => $inspirations
        ]);
    }

    /**
     * Remove inspiration from saved list (unfavorite)
     */
    public function removeSaved(Request $request, $id)
    {
        $inspiration = Inspiration::findOrFail($id);
        $user = auth()->user();

        // Check if user has saved this
        $isSaved = $inspiration->savedByUsers()->where('user_id', $user->id)->exists();

        if (!$isSaved) {
            return response()->json([
                'success' => false,
                'message' => 'Inspiration is not in your saved list'
            ], 400);
        }

        // Remove from saved
        $inspiration->savedByUsers()->detach($user->id);
        $inspiration->decrement('liked_count');

        return response()->json([
            'success' => true,
            'message' => 'Inspiration removed from your saved list',
            'data' => [
                'is_saved' => false,
                'liked_count' => $inspiration->liked_count
            ]
        ]);
    }
}
