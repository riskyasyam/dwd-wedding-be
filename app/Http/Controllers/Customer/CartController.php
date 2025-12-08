<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Decoration;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Get current user's cart with items
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get or create cart for user
        $cart = $user->cart()->with(['items.decoration.images'])->first();
        
        if (!$cart) {
            $cart = $user->cart()->create();
        }
        
        // Calculate totals
        $subtotal = $cart->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $cart,
                'subtotal' => $subtotal,
                'item_count' => $cart->items->count(),
            ]
        ]);
    }
    
    /**
     * Add item to cart
     */
    public function addItem(Request $request)
    {
        $request->validate([
            'decoration_id' => 'required|exists:decorations,id',
            'type' => 'required|in:custom,random',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $user = auth()->user();
        $decoration = Decoration::findOrFail($request->decoration_id);
        
        // Get or create cart
        $cart = $user->cart ?: $user->cart()->create();
        
        // Check if item already exists in cart
        $existingItem = $cart->items()
            ->where('decoration_id', $request->decoration_id)
            ->where('type', $request->type)
            ->first();
        
        if ($existingItem) {
            // Update quantity
            $existingItem->quantity += $request->quantity;
            $existingItem->save();
            
            $item = $existingItem;
            $message = 'Item quantity updated in cart';
        } else {
            // Add new item
            $item = $cart->items()->create([
                'decoration_id' => $request->decoration_id,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'price' => $decoration->final_price, // Snapshot current price
            ]);
            
            $message = 'Item added to cart successfully';
        }
        
        // Load decoration relationship
        $item->load('decoration.images');
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $item
        ]);
    }
    
    /**
     * Update cart item quantity
     */
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
        
        $user = auth()->user();
        $cart = $user->cart;
        
        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found'
            ], 404);
        }
        
        $item = $cart->items()->findOrFail($itemId);
        $item->quantity = $request->quantity;
        $item->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'data' => $item
        ]);
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($itemId)
    {
        $user = auth()->user();
        $cart = $user->cart;
        
        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found'
            ], 404);
        }
        
        $item = $cart->items()->findOrFail($itemId);
        $item->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart successfully'
        ]);
    }
    
    /**
     * Clear all items from cart
     */
    public function clear()
    {
        $user = auth()->user();
        $cart = $user->cart;
        
        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found'
            ], 404);
        }
        
        $cart->items()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
    }
}
