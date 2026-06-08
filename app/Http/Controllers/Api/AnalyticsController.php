<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Customer;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    // GET /api/admin/analytics/dashboard
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today'); // today, week, month, year

        $startDate = match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::today(),
        };

        // Revenue & Orders
        $currentRevenue = Order::where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $currentOrders = Order::where('created_at', '>=', $startDate)->count();

        // Previous period comparison
        $periodLength = $startDate->diffInDays(now());
        $prevStartDate = $startDate->copy()->subDays($periodLength);
        $prevRevenue = Order::whereBetween('created_at', [$prevStartDate, $startDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $revenueChange = $prevRevenue > 0 
            ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100
            : 0;

        // Top selling items
        $topItems = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'menu_items.id',
                'menu_items.name',
                'menu_items.emoji',
                'menu_items.category',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.emoji', 'menu_items.category')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Customer insights
        $newCustomers = Customer::where('created_at', '>=', $startDate)->count();
        $returningCustomers = Customer::whereHas('orders', function ($q) use ($startDate) {
            $q->where('created_at', '>=', $startDate);
        })->where('created_at', '<', $startDate)->count();

        // Average order value
        $avgOrderValue = $currentOrders > 0 ? (int) ($currentRevenue / $currentOrders) : 0;

        // Order status breakdown
        $statusBreakdown = Order::where('created_at', '>=', $startDate)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Peak hours
        $peakHours = Order::where('created_at', '>=', $startDate)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Category performance
        $categoryPerformance = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'menu_items.category',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('menu_items.category')
            ->get();

        // Revenue trend (last 7/30 days)
        $days = $period === 'month' ? 30 : 7;
        $revenueTrend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->startOfDay();
            $revenue = Order::whereDate('created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total_price');
            $revenueTrend[] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => $revenue,
            ];
        }

        return response()->json([
            'summary' => [
                'total_revenue' => $currentRevenue,
                'total_orders' => $currentOrders,
                'revenue_change_percent' => round($revenueChange, 2),
                'avg_order_value' => $avgOrderValue,
                'new_customers' => $newCustomers,
                'returning_customers' => $returningCustomers,
            ],
            'top_items' => $topItems,
            'status_breakdown' => $statusBreakdown,
            'peak_hours' => $peakHours,
            'category_performance' => $categoryPerformance,
            'revenue_trend' => $revenueTrend,
        ]);
    }

    // GET /api/admin/analytics/customer-insights
    public function customerInsights(): JsonResponse
    {
        // Tier distribution
        $tierDistribution = Customer::select('tier', DB::raw('count(*) as count'))
            ->groupBy('tier')
            ->pluck('count', 'tier');

        // Customer lifetime value
        $topCustomers = Customer::orderByDesc('total_spent')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'total_spent', 'total_orders', 'tier']);

        // Loyalty points distribution
        $loyaltyStats = [
            'total_points_issued' => Customer::sum('loyalty_points'),
            'avg_points_per_customer' => (int) Customer::avg('loyalty_points'),
            'customers_with_points' => Customer::where('loyalty_points', '>', 0)->count(),
        ];

        // Customer retention (orders per customer)
        $ordersPerCustomer = DB::table('customers')
            ->select(
                DB::raw('CASE 
                    WHEN total_orders = 1 THEN "1 order"
                    WHEN total_orders BETWEEN 2 AND 5 THEN "2-5 orders"
                    WHEN total_orders BETWEEN 6 AND 10 THEN "6-10 orders"
                    ELSE "10+ orders"
                END as category'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('category')
            ->get();

        // Average days between orders
        $avgDaysBetweenOrders = DB::table('orders as o1')
            ->join('orders as o2', function ($join) {
                $join->on('o1.customer_id', '=', 'o2.customer_id')
                    ->whereRaw('o2.created_at > o1.created_at');
            })
            ->select(DB::raw('AVG(DATEDIFF(o2.created_at, o1.created_at)) as avg_days'))
            ->value('avg_days');

        return response()->json([
            'tier_distribution' => $tierDistribution,
            'top_customers' => $topCustomers,
            'loyalty_stats' => $loyaltyStats,
            'orders_per_customer' => $ordersPerCustomer,
            'avg_days_between_orders' => round($avgDaysBetweenOrders ?? 0, 1),
        ]);
    }

    // GET /api/admin/analytics/menu-performance
    public function menuPerformance(): JsonResponse
    {
        // Items that need attention
        $lowStockItems = MenuItem::lowStock()
            ->active()
            ->get(['id', 'name', 'stock', 'low_stock_threshold', 'times_ordered']);

        $poorRatedItems = MenuItem::where('review_count', '>=', 5)
            ->where('average_rating', '<', 3)
            ->get(['id', 'name', 'average_rating', 'review_count']);

        $unstockedItems = MenuItem::whereHas('orderItems', function ($q) {
            $q->where('created_at', '>=', Carbon::now()->subDays(30));
        })
            ->whereNull('stock')
            ->get(['id', 'name', 'times_ordered']);

        // Items to promote
        $highRatedItems = MenuItem::where('review_count', '>=', 5)
            ->where('average_rating', '>=', 4.5)
            ->orderByDesc('average_rating')
            ->limit(10)
            ->get(['id', 'name', 'average_rating', 'review_count', 'times_ordered']);

        // Revenue contribution by item
        $revenueContribution = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'menu_items.id',
                'menu_items.name',
                'menu_items.category',
                DB::raw('SUM(order_items.subtotal) as revenue'),
                DB::raw('SUM(order_items.quantity) as quantity')
            )
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.category')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();

        return response()->json([
            'low_stock_items' => $lowStockItems,
            'poor_rated_items' => $poorRatedItems,
            'high_rated_items' => $highRatedItems,
            'revenue_contribution' => $revenueContribution,
        ]);
    }

    // GET /api/admin/analytics/reviews-sentiment
    public function reviewsSentiment(): JsonResponse
    {
        // Rating distribution
        $ratingDistribution = Review::visible()
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating');

        // Recent reviews
        $recentReviews = Review::with(['menuItem', 'customer'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Most reviewed items
        $mostReviewedItems = MenuItem::withCount('reviews')
            ->orderByDesc('reviews_count')
            ->limit(10)
            ->get(['id', 'name', 'average_rating', 'review_count']);

        // Reviews requiring response
        $pendingResponses = Review::visible()
            ->whereNull('admin_response')
            ->where('rating', '<=', 3)
            ->count();

        return response()->json([
            'rating_distribution' => $ratingDistribution,
            'recent_reviews' => $recentReviews,
            'most_reviewed_items' => $mostReviewedItems,
            'pending_responses' => $pendingResponses,
        ]);
    }

    // GET /api/admin/analytics/inventory
    public function inventory(): JsonResponse
    {
        // Stock levels
        $stockLevels = MenuItem::whereNotNull('stock')
            ->select('id', 'name', 'category', 'stock', 'low_stock_threshold', 'optimal_stock_level')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    ...$item->toArray(),
                    'stock_status' => $item->stock <= $item->low_stock_threshold ? 'low' :
                        ($item->stock >= $item->optimal_stock_level ? 'optimal' : 'moderate'),
                ];
            });

        // Inventory movements (last 7 days)
        $movements = DB::table('inventory_logs')
            ->join('menu_items', 'inventory_logs.menu_item_id', '=', 'menu_items.id')
            ->where('inventory_logs.created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                'menu_items.name',
                'inventory_logs.type',
                DB::raw('SUM(inventory_logs.quantity_change) as total_change'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('menu_items.name', 'inventory_logs.type')
            ->orderBy('menu_items.name')
            ->get();

        return response()->json([
            'stock_levels' => $stockLevels,
            'movements' => $movements,
        ]);
    }
}
