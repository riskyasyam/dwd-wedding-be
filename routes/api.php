<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// Public routes (no authentication required) - for landing page
Route::prefix('public')->group(function () {
    // Decorations - public browsing
    Route::get('/decorations', [\App\Http\Controllers\Admin\DecorationController::class, 'index']);
    Route::get('/decorations/{id}', [\App\Http\Controllers\Admin\DecorationController::class, 'show']);
    
    // Events - public browsing
    Route::get('/events', [\App\Http\Controllers\Admin\EventController::class, 'index']);
    Route::get('/events/{id}', [\App\Http\Controllers\Admin\EventController::class, 'show']);
    
    // Advertisements - public browsing
    Route::get('/advertisements', [\App\Http\Controllers\Admin\AdvertisementController::class, 'activeAds']);
    
    // Testimonials - public browsing
    Route::get('/testimonials', [\App\Http\Controllers\Admin\TestimonialController::class, 'index']);
    
    // Inspirations - public browsing
    Route::get('/inspirations', [\App\Http\Controllers\Admin\InspirationController::class, 'index']);
    Route::get('/inspirations/{id}', [\App\Http\Controllers\Admin\InspirationController::class, 'show']);
    
    // Vendors - public browsing
    Route::get('/vendors', [\App\Http\Controllers\Admin\VendorController::class, 'index']);
    Route::get('/vendors/{id}', [\App\Http\Controllers\Admin\VendorController::class, 'show']);
    
    // Reviews - public browsing
    Route::get('/decorations/{decorationId}/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'decorationReviews']);
});

// Auth routes (for API authentication)
Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);
    Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Customer routes
    Route::prefix('customer')->group(function () {
        // Decorations - public browsing
        Route::get('/decorations', [\App\Http\Controllers\Admin\DecorationController::class, 'index']);
        Route::get('/decorations/{id}', [\App\Http\Controllers\Admin\DecorationController::class, 'show']);
        
        // Events - public browsing
        Route::get('/events', [\App\Http\Controllers\Admin\EventController::class, 'index']);
        Route::get('/events/{id}', [\App\Http\Controllers\Admin\EventController::class, 'show']);
        
        // Advertisements - public browsing
        Route::get('/advertisements', [\App\Http\Controllers\Admin\AdvertisementController::class, 'activeAds']);
        
        // Testimonials - public browsing
        Route::get('/testimonials', [\App\Http\Controllers\Admin\TestimonialController::class, 'index']);
        
        // Inspirations - public browsing
        Route::get('/inspirations', [\App\Http\Controllers\Admin\InspirationController::class, 'index']);
        Route::get('/inspirations/{id}', [\App\Http\Controllers\Admin\InspirationController::class, 'show']);
        Route::post('/inspirations/{id}/like', [\App\Http\Controllers\Admin\InspirationController::class, 'toggleLike']);
        Route::get('/my-saved-inspirations', [\App\Http\Controllers\Admin\InspirationController::class, 'mySaved']);
        
        // Vouchers - validate voucher code
        Route::post('/vouchers/validate', [\App\Http\Controllers\Admin\VoucherController::class, 'validate']);
        
        // Reviews - customer can create/edit own reviews
        Route::post('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'storeCustomer']);
        Route::get('/reviews/can-review/{decorationId}', [\App\Http\Controllers\Admin\ReviewController::class, 'canReview']);
        Route::put('/reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'updateOwn']);
        Route::delete('/reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroyOwn']);
    });
    
    // Admin routes - CRUD operations
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Decorations
        Route::get('decorations/dropdown', [\App\Http\Controllers\Admin\DecorationController::class, 'dropdown']);
        Route::apiResource('decorations', \App\Http\Controllers\Admin\DecorationController::class);
        Route::post('decorations/{id}/images', [\App\Http\Controllers\Admin\DecorationController::class, 'uploadImages']);
        Route::delete('decorations/images/{imageId}', [\App\Http\Controllers\Admin\DecorationController::class, 'deleteImage']);
        
        // Decoration Free Items
        Route::get('decorations/{decorationId}/free-items', [\App\Http\Controllers\Admin\DecorationFreeItemController::class, 'index']);
        Route::post('decorations/{decorationId}/free-items', [\App\Http\Controllers\Admin\DecorationFreeItemController::class, 'store']);
        Route::get('decorations/{decorationId}/free-items/{id}', [\App\Http\Controllers\Admin\DecorationFreeItemController::class, 'show']);
        Route::put('decorations/{decorationId}/free-items/{id}', [\App\Http\Controllers\Admin\DecorationFreeItemController::class, 'update']);
        Route::delete('decorations/{decorationId}/free-items/{id}', [\App\Http\Controllers\Admin\DecorationFreeItemController::class, 'destroy']);
        
        // Events
        Route::apiResource('events', \App\Http\Controllers\Admin\EventController::class);
        Route::post('events/{id}/images', [\App\Http\Controllers\Admin\EventController::class, 'uploadImages']);
        Route::delete('events/images/{imageId}', [\App\Http\Controllers\Admin\EventController::class, 'deleteImage']);
        
        // Advertisements
        Route::apiResource('advertisements', \App\Http\Controllers\Admin\AdvertisementController::class);
        Route::post('advertisements/update-order', [\App\Http\Controllers\Admin\AdvertisementController::class, 'updateOrder']);
        
        // Testimonials
        Route::apiResource('testimonials', \App\Http\Controllers\Admin\TestimonialController::class);
        
        // Vendors
        Route::apiResource('vendors', \App\Http\Controllers\Admin\VendorController::class);
        Route::post('vendors/{id}/images', [\App\Http\Controllers\Admin\VendorController::class, 'uploadImages']);
        Route::delete('vendors/images/{imageId}', [\App\Http\Controllers\Admin\VendorController::class, 'deleteImage']);
        
        // Vouchers
        Route::apiResource('vouchers', \App\Http\Controllers\Admin\VoucherController::class);
        
        // Inspirations
        Route::apiResource('inspirations', \App\Http\Controllers\Admin\InspirationController::class);
        
        // Customers (User Management)
        Route::get('customers/dropdown', [\App\Http\Controllers\Admin\UserController::class, 'dropdown']);
        Route::get('customers', [\App\Http\Controllers\Admin\UserController::class, 'index']);
        Route::get('customers/statistics', [\App\Http\Controllers\Admin\UserController::class, 'statistics']);
        Route::get('customers/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show']);
        Route::put('customers/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update']);
        Route::delete('customers/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy']);
        
        // Reviews (Admin - CRUD fake reviews)
        Route::get('reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index']);
        Route::post('reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'storeAdmin']);
        Route::get('reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'show']);
        Route::put('reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'update']);
        Route::delete('reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy']);
    });
});
