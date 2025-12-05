<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Decoration;
use App\Models\Order;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews for a decoration.
     */
    public function index(Request $request)
    {
        $query = Review::with([
            'user:id,first_name,last_name,email',
            'decoration:id,name,slug'
        ]);

        // Filter by decoration
        if ($request->has('decoration_id')) {
            $query->where('decoration_id', $request->decoration_id);
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Search by comment
        if ($request->has('search')) {
            $query->where('comment', 'like', '%' . $request->search . '%');
        }

        $reviews = $query->orderBy('posted_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Get reviews for specific decoration (Public).
     */
    public function decorationReviews($decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);
        
        $reviews = Review::with('user:id,first_name,last_name')
            ->where('decoration_id', $decorationId)
            ->orderBy('posted_at', 'desc')
            ->get()
            ->map(function($review) {
                return [
                    'id' => $review->id,
                    'customer_name' => $review->customer_name ?? $review->user?->name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'posted_at' => $review->posted_at,
                    'created_at' => $review->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'decoration' => [
                    'id' => $decoration->id,
                    'name' => $decoration->name,
                    'rating' => $decoration->rating,
                    'review_count' => $decoration->review_count,
                ],
                'reviews' => $reviews
            ]
        ]);
    }

    /**
     * Store a newly created review (Customer - must have purchased).
     */
    public function storeCustomer(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'decoration_id' => 'required|exists:decorations,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        // Check if user has purchased this decoration
        $hasPurchased = Order::where('user_id', $user->id)
            ->where('status', 'completed') // Only completed orders
            ->whereHas('items', function($query) use ($request) {
                $query->where('decoration_id', $request->decoration_id);
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'success' => false,
                'message' => 'You can only review decorations you have purchased'
            ], 403);
        }

        // Check if user already reviewed this decoration
        $existingReview = Review::where('user_id', $user->id)
            ->where('decoration_id', $request->decoration_id)
            ->exists();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this decoration'
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'decoration_id' => $request->decoration_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'posted_at' => now(),
        ]);

        // Update decoration rating
        $this->updateDecorationRating($request->decoration_id);

        $review->load([
            'user:id,first_name,last_name,email',
            'decoration:id,name,slug'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review posted successfully',
            'data' => $review
        ], 201);
    }

    /**
     * Store a newly created review (Admin - can create fake reviews).
     */
    public function storeAdmin(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'decoration_id' => 'required|exists:decorations,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'posted_at' => 'nullable|date',
        ]);

        $review = Review::create([
            'user_id' => null, // Fake review, no real user
            'customer_name' => $request->customer_name,
            'decoration_id' => $request->decoration_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'posted_at' => $request->posted_at ?? now(),
        ]);

        // Update decoration rating
        $this->updateDecorationRating($request->decoration_id);

        $review->load('decoration:id,name,slug');

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => $review
        ], 201);
    }

    /**
     * Display the specified review.
     */
    public function show($id)
    {
        $review = Review::with([
            'user:id,first_name,last_name,email',
            'decoration:id,name,slug'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $review
        ]);
    }

    /**
     * Update the specified review (Admin only).
     */
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $request->validate([
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'comment' => 'sometimes|required|string|max:1000',
            'posted_at' => 'sometimes|date',
        ]);

        $review->update($request->only(['rating', 'comment', 'posted_at']));

        // Update decoration rating
        $this->updateDecorationRating($review->decoration_id);

        $review->load([
            'user:id,first_name,last_name,email',
            'decoration:id,name,slug'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => $review
        ]);
    }

    /**
     * Remove the specified review (Admin only).
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $decorationId = $review->decoration_id;
        
        $review->delete();

        // Update decoration rating after deletion
        $this->updateDecorationRating($decorationId);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Update customer's own review.
     */
    public function updateOwn(Request $request, $id)
    {
        $user = auth()->user();
        $review = Review::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'comment' => 'sometimes|required|string|max:1000',
        ]);

        $review->update($request->only(['rating', 'comment']));

        // Update decoration rating
        $this->updateDecorationRating($review->decoration_id);

        $review->load([
            'user:id,first_name,last_name,email',
            'decoration:id,name,slug'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => $review
        ]);
    }

    /**
     * Delete customer's own review.
     */
    public function destroyOwn($id)
    {
        $user = auth()->user();
        $review = Review::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        
        $decorationId = $review->decoration_id;
        $review->delete();

        // Update decoration rating after deletion
        $this->updateDecorationRating($decorationId);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Check if customer can review a decoration.
     */
    public function canReview($decorationId)
    {
        $user = auth()->user();
        $decoration = Decoration::findOrFail($decorationId);

        // Check if already reviewed
        $hasReviewed = Review::where('user_id', $user->id)
            ->where('decoration_id', $decorationId)
            ->exists();

        if ($hasReviewed) {
            return response()->json([
                'success' => true,
                'can_review' => false,
                'reason' => 'already_reviewed',
                'message' => 'You have already reviewed this decoration'
            ]);
        }

        // Check if user has purchased this decoration
        $hasPurchased = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('items', function($query) use ($decorationId) {
                $query->where('decoration_id', $decorationId);
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'success' => true,
                'can_review' => false,
                'reason' => 'not_purchased',
                'message' => 'You need to purchase this decoration before reviewing'
            ]);
        }

        return response()->json([
            'success' => true,
            'can_review' => true,
            'message' => 'You can review this decoration'
        ]);
    }

    /**
     * Update decoration's average rating and review count.
     */
    private function updateDecorationRating($decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);
        
        $stats = Review::where('decoration_id', $decorationId)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->first();

        $decoration->update([
            'rating' => $stats->avg_rating ? round($stats->avg_rating, 1) : 0,
            'review_count' => $stats->review_count ?? 0,
        ]);
    }
}
