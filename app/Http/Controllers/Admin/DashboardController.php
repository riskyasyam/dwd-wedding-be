<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Decoration;
use App\Models\Event;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Models\Review;
use App\Models\Inspiration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard statistics for admin
     * Includes all major metrics and summaries
     */
    public function index()
    {
        // Orders Statistics
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $paidOrders = Order::where('status', 'paid')->count();
        $completedOrders = Order::where('status', 'completed')->count();
        $failedOrders = Order::where('status', 'failed')->count();
        $cancelledOrders = Order::where('status', 'cancelled')->count();
        
        // Revenue Statistics
        $totalRevenue = Order::whereIn('status', ['paid', 'completed'])->sum('total');
        $todayRevenue = Order::whereIn('status', ['paid', 'completed'])
            ->whereDate('created_at', today())
            ->sum('total');
        $thisMonthRevenue = Order::whereIn('status', ['paid', 'completed'])
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->sum('total');
        $lastMonthRevenue = Order::whereIn('status', ['paid', 'completed'])
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('total');
        
        // Orders Trends
        $todayOrders = Order::whereDate('created_at', today())->count();
        $thisMonthOrders = Order::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();
        $lastMonthOrders = Order::whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();
        
        // Users Statistics
        $totalUsers = User::where('role', 'customer')->count();
        $newUsersToday = User::where('role', 'customer')
            ->whereDate('created_at', today())
            ->count();
        $newUsersThisMonth = User::where('role', 'customer')
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();
        $activeUsers = User::where('role', 'customer')
            ->whereHas('orders', function($query) {
                $query->whereIn('status', ['paid', 'completed']);
            })
            ->count();
        
        // Products Statistics
        $totalDecorations = Decoration::count();
        $activeDecorations = $totalDecorations; // All decorations considered active if no is_active column
        $inactiveDecorations = 0;
        
        // Events Statistics
        $totalEvents = Event::count();
        $activeEvents = $totalEvents; // All events considered active if no is_active column
        
        // Vendors Statistics
        $totalVendors = Vendor::count();
        $activeVendors = $totalVendors; // All vendors considered active if no is_active column
        
        // Vouchers Statistics
        $totalVouchers = Voucher::count();
        // Remove queries with non-existent columns
        $activeVouchers = $totalVouchers; // All vouchers considered active if no validation columns
        $expiredVouchers = 0;
        $totalVoucherUsage = Order::whereNotNull('voucher_code')
            ->whereIn('status', ['paid', 'completed'])
            ->count();
        $totalVoucherDiscount = Order::whereNotNull('voucher_code')
            ->whereIn('status', ['paid', 'completed'])
            ->sum('voucher_discount');
        
        // Reviews Statistics
        $totalReviews = Review::count();
        $averageRating = Review::avg('rating') ?? 0;
        $reviewsThisMonth = Review::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();
        
        // Inspirations Statistics
        $totalInspirations = Inspiration::count();
        // Check if inspiration_likes table exists
        try {
            $totalInspirationLikes = DB::table('inspiration_likes')->count();
        } catch (\Exception $e) {
            $totalInspirationLikes = 0;
        }
        
        // Recent Activities
        $recentOrders = Order::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($order) {
                $customerName = 'N/A';
                if ($order->user) {
                    $customerName = $order->user->first_name . ' ' . $order->user->last_name;
                }
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $customerName,
                    'total' => $order->total,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ];
            });
        
        $recentUsers = User::where('role', 'customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at'])
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ];
            });
        
        // Top Selling Decorations
        $topDecorations = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('decorations', 'order_items.decoration_id', '=', 'decorations.id')
            ->select(
                'decorations.id',
                'decorations.name',
                'decorations.base_price',
                'decorations.final_price',
                DB::raw('COUNT(order_items.id) as total_orders'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->whereIn('orders.status', ['paid', 'completed'])
            ->groupBy('decorations.id', 'decorations.name', 'decorations.base_price', 'decorations.final_price')
            ->orderByDesc('total_orders')
            ->limit(5)
            ->get();
        
        // Monthly Revenue Chart Data (Last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = Order::whereIn('status', ['paid', 'completed'])
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total');
            
            $monthlyRevenue[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue
            ];
        }
        
        // Order Status Distribution
        $orderStatusDistribution = [
            ['status' => 'pending', 'count' => $pendingOrders, 'percentage' => $totalOrders > 0 ? round(($pendingOrders / $totalOrders) * 100, 2) : 0],
            ['status' => 'paid', 'count' => $paidOrders, 'percentage' => $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100, 2) : 0],
            ['status' => 'completed', 'count' => $completedOrders, 'percentage' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0],
            ['status' => 'failed', 'count' => $failedOrders, 'percentage' => $totalOrders > 0 ? round(($failedOrders / $totalOrders) * 100, 2) : 0],
            ['status' => 'cancelled', 'count' => $cancelledOrders, 'percentage' => $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100, 2) : 0],
        ];
        
        // Calculate growth percentages
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2) 
            : 0;
        
        $ordersGrowth = $lastMonthOrders > 0 
            ? round((($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100, 2) 
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                // Summary Cards
                'summary' => [
                    'total_revenue' => [
                        'value' => $totalRevenue,
                        'label' => 'Total Revenue',
                        'growth' => $revenueGrowth,
                        'growth_label' => $revenueGrowth >= 0 ? 'increase' : 'decrease',
                    ],
                    'total_orders' => [
                        'value' => $totalOrders,
                        'label' => 'Total Orders',
                        'growth' => $ordersGrowth,
                        'growth_label' => $ordersGrowth >= 0 ? 'increase' : 'decrease',
                    ],
                    'total_customers' => [
                        'value' => $totalUsers,
                        'label' => 'Total Customers',
                        'new_this_month' => $newUsersThisMonth,
                    ],
                    'active_products' => [
                        'value' => $activeDecorations,
                        'label' => 'Active Products',
                        'total' => $totalDecorations,
                    ],
                ],
                
                // Orders Overview
                'orders' => [
                    'total' => $totalOrders,
                    'pending' => $pendingOrders,
                    'paid' => $paidOrders,
                    'completed' => $completedOrders,
                    'failed' => $failedOrders,
                    'cancelled' => $cancelledOrders,
                    'today' => $todayOrders,
                    'this_month' => $thisMonthOrders,
                    'status_distribution' => $orderStatusDistribution,
                ],
                
                // Revenue Overview
                'revenue' => [
                    'total' => $totalRevenue,
                    'today' => $todayRevenue,
                    'this_month' => $thisMonthRevenue,
                    'last_month' => $lastMonthRevenue,
                    'growth_percentage' => $revenueGrowth,
                    'monthly_chart' => $monthlyRevenue,
                ],
                
                // Users Overview
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'new_today' => $newUsersToday,
                    'new_this_month' => $newUsersThisMonth,
                ],
                
                // Products Overview
                'products' => [
                    'decorations' => [
                        'total' => $totalDecorations,
                        'active' => $activeDecorations,
                        'inactive' => $inactiveDecorations,
                    ],
                    'events' => [
                        'total' => $totalEvents,
                        'active' => $activeEvents,
                    ],
                    'vendors' => [
                        'total' => $totalVendors,
                        'active' => $activeVendors,
                    ],
                ],
                
                // Vouchers Overview
                'vouchers' => [
                    'total' => $totalVouchers,
                    'active' => $activeVouchers,
                    'expired' => $expiredVouchers,
                    'total_usage' => $totalVoucherUsage,
                    'total_discount_given' => $totalVoucherDiscount,
                ],
                
                // Reviews Overview
                'reviews' => [
                    'total' => $totalReviews,
                    'average_rating' => round($averageRating, 2),
                    'this_month' => $reviewsThisMonth,
                ],
                
                // Inspirations Overview
                'inspirations' => [
                    'total' => $totalInspirations,
                    'total_likes' => $totalInspirationLikes,
                ],
                
                // Recent Activities
                'recent_activities' => [
                    'orders' => $recentOrders,
                    'users' => $recentUsers,
                ],
                
                // Top Performers
                'top_performers' => [
                    'decorations' => $topDecorations,
                ],
            ]
        ]);
    }
}
