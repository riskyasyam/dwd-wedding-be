<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class OrderController extends Controller
{
    public function __construct()
    {
        // Set Midtrans configuration
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Get all orders for authenticated user
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Order::where('user_id', $user->id)
            ->with(['items.decoration.images']);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Add has_reviewed flag for each order item
        $orders->getCollection()->transform(function ($order) use ($user) {
            $order->items->each(function ($item) use ($user) {
                $hasReviewed = \App\Models\Review::where('user_id', $user->id)
                    ->where('decoration_id', $item->decoration_id)
                    ->exists();
                $item->has_reviewed = $hasReviewed;
            });
            return $order;
        });
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get single order detail
     */
    public function show($id)
    {
        $user = auth()->user();
        
        $order = Order::where('user_id', $user->id)
            ->with(['items.decoration.images', 'user'])
            ->findOrFail($id);
        
        // Add has_reviewed flag for each order item
        $order->items->each(function ($item) use ($user) {
            $hasReviewed = \App\Models\Review::where('user_id', $user->id)
                ->where('decoration_id', $item->decoration_id)
                ->exists();
            $item->has_reviewed = $hasReviewed;
        });
        
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Create order from cart and generate Midtrans payment token
     */
    public function checkout(Request $request)
    {
        $request->validate([
            // Personal Details
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            
            // Shipping Address
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'sub_district' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            
            // Payment Type
            'payment_type' => 'required|in:full,dp',
            
            // Notes (optional)
            'notes' => 'nullable|string',
            
            // Voucher (optional)
            'voucher_code' => 'nullable|string|exists:vouchers,code',
        ]);

        $user = auth()->user();
        $cart = $user->cart()->with('items.decoration')->first();

        if (!$cart || $cart->items->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        // Calculate subtotal (cart items already use final_price after decoration discount)
        $subtotal = $cart->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Calculate decoration discounts for display purposes only
        // This is to show how much discount was given from base_price
        $decorationDiscount = $cart->items->sum(function ($item) {
            $decoration = $item->decoration;
            if ($decoration->discount_percent > 0) {
                $discountAmount = ($decoration->base_price * $decoration->discount_percent / 100) * $item->quantity;
                return $discountAmount;
            }
            return 0;
        });

        // Apply voucher if provided
        $voucherCode = null;
        $voucherDiscount = 0;
        
        if ($request->voucher_code) {
            $voucher = Voucher::where('code', strtoupper($request->voucher_code))->first();
            
            if ($voucher) {
                // Validate voucher (same logic as VoucherController::validate)
                $now = now();
                if ($voucher->valid_from && $now->lt($voucher->valid_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Voucher belum berlaku'
                    ], 400);
                }
                
                if ($voucher->valid_until && $now->gt($voucher->valid_until)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Voucher sudah expired'
                    ], 400);
                }
                
                if ($voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Voucher sudah mencapai batas penggunaan'
                    ], 400);
                }
                
                if ($subtotal < $voucher->min_purchase) {
                    return response()->json([
                        'success' => false,
                        'message' => "Minimum pembelian Rp " . number_format($voucher->min_purchase, 0, ',', '.')
                    ], 400);
                }
                
                // Check usage per user
                $userUsageCount = Order::where('user_id', $user->id)
                    ->where('voucher_code', $voucher->code)
                    ->whereIn('status', ['paid', 'completed'])
                    ->count();
                
                if ($voucher->usage_per_user && $userUsageCount >= $voucher->usage_per_user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda sudah mencapai batas penggunaan voucher ini'
                    ], 400);
                }
                
                // Calculate voucher discount
                if ($voucher->type === 'percentage') {
                    $voucherDiscount = ($subtotal * $voucher->discount_value / 100);
                    if ($voucher->max_discount && $voucherDiscount > $voucher->max_discount) {
                        $voucherDiscount = $voucher->max_discount;
                    }
                } else {
                    // Fixed amount discount
                    $voucherDiscount = $voucher->discount_value;
                }
                
                // Ensure voucher discount is numeric and properly calculated
                $voucherDiscount = (float) $voucherDiscount;
                
                $voucherCode = $voucher->code;
            }
        }

        $deliveryFee = 0; // Can be calculated based on user location
        // IMPORTANT: subtotal already includes decoration discount (cart uses final_price)
        // So we only subtract voucher discount, NOT decoration discount
        $total = $subtotal - $voucherDiscount + $deliveryFee;

        // Calculate DP if payment type is DP
        $paymentType = $request->payment_type;
        $dpAmount = 0;
        $remainingAmount = 0;
        
        if ($paymentType === 'dp') {
            // Get minimum DP percentage from all decorations in cart
            $minDpPercentage = $cart->items->max(function ($item) {
                return $item->decoration->minimum_dp_percentage ?? 30;
            });
            
            // Calculate DP amount (minimum percentage of total)
            $dpAmount = ceil($total * $minDpPercentage / 100);
            $remainingAmount = $total - $dpAmount;
        }

        // Generate unique order number
        $orderNumber = 'ORD-' . time() . '-' . strtoupper(substr(md5($user->id), 0, 6));

        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'district' => $request->district,
            'sub_district' => $request->sub_district,
            'postal_code' => $request->postal_code,
            'subtotal' => $subtotal,
            'voucher_code' => $voucherCode,
            'voucher_discount' => $voucherDiscount,
            'discount' => $decorationDiscount,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'payment_type' => $paymentType,
            'dp_amount' => $dpAmount,
            'remaining_amount' => $remainingAmount,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        // Create order items from cart items
        foreach ($cart->items as $cartItem) {
            $decoration = $cartItem->decoration;
            
            // Calculate base_price and discount per item
            $basePrice = $decoration->base_price;
            $discountAmount = 0;
            
            if ($decoration->discount_percent > 0) {
                $discountAmount = $basePrice * $decoration->discount_percent / 100;
            }
            
            $finalPrice = $basePrice - $discountAmount;
            
            $order->items()->create([
                'decoration_id' => $cartItem->decoration_id,
                'type' => $cartItem->type,
                'quantity' => $cartItem->quantity,
                'base_price' => $basePrice,
                'discount' => $discountAmount,
                'price' => $finalPrice,
            ]);
        }

        // Create Midtrans transaction
        try {
            // Use DP amount if payment type is DP, otherwise use full amount
            $paymentAmount = ($paymentType === 'dp') ? $dpAmount : $total;
            
            $params = [
                'transaction_details' => [
                    'order_id' => $orderNumber,
                    'gross_amount' => (int) $paymentAmount,
                ],
                'customer_details' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                ],
            ];

            // For DP payment, use single item with DP amount
            // For Full payment, use detailed items
            if ($paymentType === 'dp') {
                $params['item_details'] = [
                    [
                        'id' => $orderNumber,
                        'price' => (int) $dpAmount,
                        'quantity' => 1,
                        'name' => "Down Payment (DP) - Order #{$orderNumber}",
                    ]
                ];
            } else {
                // Full payment - show detailed items
                $params['item_details'] = $cart->items->map(function ($item) {
                    return [
                        'id' => $item->decoration_id,
                        'price' => (int) $item->price,
                        'quantity' => $item->quantity,
                        'name' => $item->decoration->name,
                    ];
                })->toArray();
                
                // Add discount items if any
                // DON'T add decoration discount here - it's already included in item prices!
                // Cart items already use final_price (base_price - decoration_discount)
                
                if ($voucherDiscount > 0) {
                    // Ensure the discount is a proper negative integer for Midtrans
                    $discountPrice = -1 * (int) round($voucherDiscount);
                    
                    \Log::info('Voucher Discount Debug', [
                        'voucherDiscount' => $voucherDiscount,
                        'discountPrice' => $discountPrice,
                        'voucherCode' => $voucherCode,
                        'subtotal' => $subtotal
                    ]);
                    
                    $params['item_details'][] = [
                        'id' => 'VOUCHER',
                        'price' => $discountPrice,
                        'quantity' => 1,
                        'name' => 'Voucher Discount (' . $voucherCode . ')',
                    ];
                }

                if ($deliveryFee > 0) {
                    $params['item_details'][] = [
                        'id' => 'DELIVERY',
                        'price' => (int) $deliveryFee,
                        'quantity' => 1,
                        'name' => 'Delivery Fee',
                    ];
                }
            }

            $snapToken = Snap::getSnapToken($params);

            // Save snap token based on payment type
            if ($paymentType === 'dp') {
                $order->update(['dp_snap_token' => $snapToken]);
            } else {
                $order->update(['snap_token' => $snapToken]);
            }

            // Update voucher usage if used
            if ($voucherCode) {
                $voucher = Voucher::where('code', $voucherCode)->first();
                if ($voucher) {
                    $voucher->increment('usage_count');
                }
            }

            // Clear cart after successful order creation
            $cart->items()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order->load('items.decoration'),
                    'snap_token' => $snapToken,
                    'client_key' => config('midtrans.client_key'),
                ]
            ]);

        } catch (\Exception $e) {
            // Delete order if payment token generation fails
            $order->items()->delete();
            $order->delete();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual polling: Check payment status from Midtrans
     * This endpoint should be called by frontend periodically after payment
     */
    public function checkPaymentStatus($orderNumber)
    {
        try {
            // Check if this is a remaining payment by checking order ID suffix
            $isRemainingPayment = str_contains($orderNumber, '-REMAINING');
            
            // Extract actual order number (remove -REMAINING and timestamp)
            if ($isRemainingPayment) {
                // Pattern: ORD-XXX-REMAINING-timestamp
                $parts = explode('-REMAINING', $orderNumber);
                $actualOrderNumber = $parts[0];
            } else {
                $actualOrderNumber = $orderNumber;
            }

            // Get transaction status from Midtrans
            $status = Transaction::status($orderNumber);

            // Find order
            $order = Order::where('order_number', $actualOrderNumber)->firstOrFail();

            // Update order status based on Midtrans response
            $transactionStatus = $status->transaction_status;
            $fraudStatus = $status->fraud_status ?? null;

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $this->updateOrderStatus($order, $isRemainingPayment, $status->payment_type, 'manual_check');
                }
            } else if ($transactionStatus == 'settlement') {
                $this->updateOrderStatus($order, $isRemainingPayment, $status->payment_type, 'manual_check');
            } else if ($transactionStatus == 'pending') {
                \Log::info('Payment still pending', [
                    'order_number' => $order->order_number,
                    'is_remaining' => $isRemainingPayment
                ]);
            } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                // Only update to failed if order is still pending or dp_paid
                if (in_array($order->status, ['pending', 'dp_paid'])) {
                    $order->status = 'failed';
                    $order->save();
                    
                    \Log::warning('Payment failed/cancelled', [
                        'order_number' => $order->order_number,
                        'midtrans_order_id' => $orderNumber,
                        'transaction_status' => $transactionStatus,
                        'is_remaining' => $isRemainingPayment
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_number' => $orderNumber,
                    'actual_order_number' => $actualOrderNumber,
                    'is_remaining_payment' => $isRemainingPayment,
                    'order_status' => $order->status,
                    'transaction_status' => $transactionStatus,
                    'payment_type' => $status->payment_type,
                    'transaction_time' => $status->transaction_time,
                    'gross_amount' => $status->gross_amount,
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_type' => $order->payment_type,
                        'total' => $order->total,
                        'dp_amount' => $order->dp_amount,
                        'remaining_amount' => $order->remaining_amount,
                        'dp_paid_at' => $order->dp_paid_at,
                        'remaining_paid_at' => $order->remaining_paid_at,
                        'full_paid_at' => $order->full_paid_at,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error checking payment status', [
                'order_number' => $orderNumber,
                'is_remaining' => isset($isRemainingPayment) ? $isRemainingPayment : false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel order (only if status is pending)
     */
    public function cancel($id)
    {
        $user = auth()->user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be cancelled'
            ], 400);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'data' => $order
        ]);
    }

    /**
     * Submit review for a decoration from completed order
     */
    public function submitReview(Request $request, $id)
    {
        $request->validate([
            'decoration_id' => 'required|exists:decorations,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        
        // Verify order belongs to user and is completed
        $order = Order::where('user_id', $user->id)
            ->where('id', $id)
            ->whereIn('status', ['completed', 'paid'])
            ->firstOrFail();

        // Verify decoration is in the order
        $orderItem = $order->items()
            ->where('decoration_id', $request->decoration_id)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Decoration not found in this order'
            ], 404);
        }

        // Check if already reviewed
        $existingReview = \App\Models\Review::where('user_id', $user->id)
            ->where('decoration_id', $request->decoration_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this decoration'
            ], 400);
        }

        // Create review
        $review = \App\Models\Review::create([
            'user_id' => $user->id,
            'decoration_id' => $request->decoration_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'posted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'data' => $review
        ], 201);
    }
    /**
     * Pay remaining amount after DP payment
     */
    public function payRemaining($id)
    {
        $user = auth()->user();
        
        $order = Order::where('user_id', $user->id)->findOrFail($id);
        
        // Validate order can pay remaining
        if ($order->payment_type !== 'dp') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not a DP payment'
            ], 400);
        }
        
        if ($order->status !== 'dp_paid') {
            return response()->json([
                'success' => false,
                'message' => 'DP has not been paid yet'
            ], 400);
        }
        
        if ($order->remaining_amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No remaining amount to pay'
            ], 400);
        }
        
        // Create Midtrans transaction for remaining payment
        try {
            // Add timestamp to make order_id unique for each attempt
            $timestamp = time();
            $remainingOrderNumber = $order->order_number . '-REMAINING-' . $timestamp;
            
            $params = [
                'transaction_details' => [
                    'order_id' => $remainingOrderNumber,
                    'gross_amount' => (int) $order->remaining_amount,
                ],
                'customer_details' => [
                    'first_name' => $order->first_name,
                    'last_name' => $order->last_name,
                    'email' => $order->email,
                    'phone' => $order->phone ?? '',
                ],
                'item_details' => [[
                    'id' => 'REMAINING-' . $order->id,
                    'price' => (int) $order->remaining_amount,
                    'quantity' => 1,
                    'name' => 'Remaining Payment - ' . $order->order_number,
                ]],
            ];
            
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            
            // Update order with remaining snap token
            $order->update([
                'remaining_snap_token' => $snapToken,
            ]);
            
            \Log::info('Remaining payment snap token created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'remaining_order_number' => $remainingOrderNumber,
                'remaining_amount' => $order->remaining_amount,
            ]);
            
            return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
                'remaining_amount' => $order->remaining_amount,
                'order' => $order
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Midtrans Remaining Payment Error', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create remaining payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to update order status based on payment type
     * Used by manual polling (checkPaymentStatus)
     * 
     * @param Order $order
     * @param bool $isRemainingPayment
     * @param string $paymentType
     * @param string $source
     * @return void
     */
    private function updateOrderStatus($order, $isRemainingPayment, $paymentType, $source = 'manual_check')
    {
        if ($isRemainingPayment) {
            // This is remaining payment - set to paid
            $order->status = 'paid';
            $order->remaining_paid_at = now();
            $order->full_paid_at = now();
            $order->remaining_amount = 0;
            $order->payment_method = $paymentType;
            
            \Log::info('Remaining payment settled via ' . $source, [
                'order_number' => $order->order_number,
                'status' => 'paid',
                'remaining_amount' => 0,
                'remaining_paid_at' => now()->toDateTimeString(),
                'full_paid_at' => now()->toDateTimeString()
            ]);
        } else if ($order->payment_type === 'dp' && $order->remaining_amount > 0) {
            // This is DP payment - set to dp_paid
            $order->status = 'dp_paid';
            $order->dp_paid_at = now();
            $order->payment_method = $paymentType;
            
            \Log::info('DP payment settled via ' . $source, [
                'order_number' => $order->order_number,
                'status' => 'dp_paid',
                'dp_amount' => $order->dp_amount,
                'remaining_amount' => $order->remaining_amount,
                'dp_paid_at' => now()->toDateTimeString()
            ]);
        } else {
            // This is full payment
            $order->status = 'paid';
            $order->full_paid_at = now();
            $order->payment_method = $paymentType;
            
            \Log::info('Full payment settled via ' . $source, [
                'order_number' => $order->order_number,
                'status' => 'paid',
                'total' => $order->total,
                'full_paid_at' => now()->toDateTimeString()
            ]);
        }
        
        $order->save();
    }
}
