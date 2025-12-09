<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get all orders (admin view)
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.decoration.images']);

        // Filter by status 
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search by order number
        if ($request->has('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

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
        $order = Order::with(['user', 'items.decoration.images'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update order status (admin can manually change status)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,failed,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Get order statistics
     */
    public function statistics()
    {
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $paidOrders = Order::where('status', 'paid')->count();
        $completedOrders = Order::where('status', 'completed')->count();
        $failedOrders = Order::where('status', 'failed')->count();
        $cancelledOrders = Order::where('status', 'cancelled')->count();

        // Total revenue (only paid and completed orders)
        $totalRevenue = Order::whereIn('status', ['paid', 'completed'])->sum('total');

        // Orders this month
        $ordersThisMonth = Order::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();

        // Revenue this month
        $revenueThisMonth = Order::whereIn('status', ['paid', 'completed'])
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->sum('total');

        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'paid_orders' => $paidOrders,
                'completed_orders' => $completedOrders,
                'failed_orders' => $failedOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_revenue' => $totalRevenue,
                'orders_this_month' => $ordersThisMonth,
                'revenue_this_month' => $revenueThisMonth,
            ]
        ]);
    }

    /**
     * Get recent orders
     */
    public function recent($limit = 10)
    {
        $orders = Order::with(['user', 'items.decoration'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get all orders from a specific user (admin view)
     * Returns detailed order history for a specific user
     */
    public function getUserOrders(Request $request, $userId)
    {
        $query = Order::with(['user', 'items.decoration.images'])
            ->where('user_id', $userId);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // Transform orders to include detailed price breakdown
        $orders->getCollection()->transform(function ($order) {
            $itemsWithPriceBreakdown = $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'order_id' => $item->order_id,
                    'decoration_id' => $item->decoration_id,
                    'type' => $item->type,
                    'quantity' => $item->quantity,
                    'base_price' => $item->base_price, // Harga asli
                    'discount' => $item->discount, // Diskon
                    'price' => $item->price, // Harga setelah diskon (base_price - discount)
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'decoration' => $item->decoration,
                ];
            });

            $order->items = $itemsWithPriceBreakdown;
            return $order;
        });

        // Calculate user statistics
        $totalOrders = Order::where('user_id', $userId)->count();
        $totalSpent = Order::where('user_id', $userId)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total');
        $pendingOrders = Order::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();
        $completedOrders = Order::where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'data' => $orders,
            'statistics' => [
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'pending_orders' => $pendingOrders,
                'completed_orders' => $completedOrders,
            ]
        ]);
    }
}
