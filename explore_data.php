<?php
/**
 * Data Explorer (Tinker Alternative)
 * Run: php explore_data.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n╔══════════════════════════════════════════════════╗\n";
echo "║     KANTIN MAS WAWAN - DATA EXPLORER            ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";

// Menu
while (true) {
    echo "Choose an option:\n";
    echo "  1. View Customers\n";
    echo "  2. View Promo Codes\n";
    echo "  3. View Menu Items\n";
    echo "  4. View Orders\n";
    echo "  5. View Loyalty Transactions\n";
    echo "  6. View Notifications\n";
    echo "  7. View Database Stats\n";
    echo "  8. Custom Query\n";
    echo "  9. Exit\n\n";
    echo "Enter choice (1-9): ";
    
    $choice = trim(fgets(STDIN));
    echo "\n";
    
    try {
        switch ($choice) {
            case '1':
                // View Customers
                echo "═══ CUSTOMERS ═══\n\n";
                $customers = DB::table('customers')->get();
                
                if ($customers->isEmpty()) {
                    echo "No customers found.\n\n";
                } else {
                    foreach ($customers as $c) {
                        echo "ID: {$c->id}\n";
                        echo "Name: {$c->name}\n";
                        echo "Phone: {$c->phone}\n";
                        echo "Email: {$c->email}\n";
                        echo "Tier: {$c->tier}\n";
                        echo "Loyalty Points: {$c->loyalty_points}\n";
                        echo "Total Orders: {$c->total_orders}\n";
                        echo "Total Spent: Rp " . number_format($c->total_spent, 0, ',', '.') . "\n";
                        echo "---\n";
                    }
                }
                break;
                
            case '2':
                // View Promo Codes
                echo "═══ PROMO CODES ═══\n\n";
                $promos = DB::table('promo_codes')->get();
                
                if ($promos->isEmpty()) {
                    echo "No promo codes found.\n\n";
                } else {
                    foreach ($promos as $p) {
                        echo "Code: {$p->code}\n";
                        echo "Name: {$p->name}\n";
                        echo "Type: {$p->type}\n";
                        echo "Discount: " . ($p->type == 'percentage' ? "{$p->discount_value}%" : "Rp " . number_format($p->discount_value, 0, ',', '.')) . "\n";
                        echo "Min Purchase: Rp " . number_format($p->min_purchase, 0, ',', '.') . "\n";
                        echo "Times Used: {$p->times_used}\n";
                        echo "Active: " . ($p->is_active ? 'Yes' : 'No') . "\n";
                        echo "Valid: {$p->valid_from} to {$p->valid_until}\n";
                        echo "---\n";
                    }
                }
                break;
                
            case '3':
                // View Menu Items
                echo "═══ MENU ITEMS ═══\n\n";
                $items = DB::table('menu_items')->get();
                
                if ($items->isEmpty()) {
                    echo "No menu items found.\n\n";
                } else {
                    foreach ($items as $item) {
                        echo "ID: {$item->id}\n";
                        echo "Name: {$item->name}\n";
                        echo "Category: {$item->category}\n";
                        echo "Price: Rp " . number_format($item->price, 0, ',', '.') . "\n";
                        if ($item->preparation_time) {
                            echo "Prep Time: {$item->preparation_time} min\n";
                        }
                        if ($item->calories) {
                            echo "Calories: {$item->calories}\n";
                        }
                        if ($item->spice_level) {
                            echo "Spice Level: {$item->spice_level}/5\n";
                        }
                        if ($item->average_rating) {
                            echo "Rating: {$item->average_rating} ({$item->review_count} reviews)\n";
                        }
                        echo "Times Ordered: {$item->times_ordered}\n";
                        echo "Stock: " . ($item->stock === null ? 'Unlimited' : $item->stock) . "\n";
                        echo "Active: " . ($item->is_active ? 'Yes' : 'No') . "\n";
                        echo "---\n";
                    }
                }
                break;
                
            case '4':
                // View Orders
                echo "═══ ORDERS ═══\n\n";
                $orders = DB::table('orders')->orderBy('created_at', 'desc')->limit(10)->get();
                
                if ($orders->isEmpty()) {
                    echo "No orders found.\n\n";
                } else {
                    foreach ($orders as $o) {
                        echo "Order #: {$o->order_number}\n";
                        echo "Customer: {$o->customer_name}\n";
                        if ($o->customer_id) {
                            echo "Customer ID: {$o->customer_id}\n";
                        }
                        echo "Total: Rp " . number_format($o->total_price, 0, ',', '.') . "\n";
                        echo "Status: {$o->status}\n";
                        echo "Payment: {$o->payment_method}\n";
                        if ($o->promo_code_used) {
                            echo "Promo: {$o->promo_code_used}\n";
                        }
                        echo "Date: {$o->created_at}\n";
                        echo "---\n";
                    }
                }
                break;
                
            case '5':
                // View Loyalty Transactions
                echo "═══ LOYALTY TRANSACTIONS ═══\n\n";
                $transactions = DB::table('loyalty_transactions')
                    ->join('customers', 'loyalty_transactions.customer_id', '=', 'customers.id')
                    ->select('loyalty_transactions.*', 'customers.name as customer_name')
                    ->orderBy('loyalty_transactions.created_at', 'desc')
                    ->limit(20)
                    ->get();
                
                if ($transactions->isEmpty()) {
                    echo "No loyalty transactions found.\n\n";
                } else {
                    foreach ($transactions as $t) {
                        echo "Customer: {$t->customer_name}\n";
                        echo "Type: {$t->type}\n";
                        echo "Points: {$t->points}\n";
                        echo "Balance After: {$t->balance_after}\n";
                        echo "Description: {$t->description}\n";
                        echo "Date: {$t->created_at}\n";
                        echo "---\n";
                    }
                }
                break;
                
            case '6':
                // View Notifications
                echo "═══ NOTIFICATIONS ═══\n\n";
                $notifications = DB::table('notifications')
                    ->join('customers', 'notifications.customer_id', '=', 'customers.id')
                    ->select('notifications.*', 'customers.name as customer_name')
                    ->orderBy('notifications.created_at', 'desc')
                    ->limit(20)
                    ->get();
                
                if ($notifications->isEmpty()) {
                    echo "No notifications found.\n\n";
                } else {
                    foreach ($notifications as $n) {
                        echo "To: {$n->customer_name}\n";
                        echo "Type: {$n->type}\n";
                        echo "Title: {$n->title}\n";
                        echo "Message: {$n->message}\n";
                        echo "Channel: {$n->channel}\n";
                        echo "Delivered: " . ($n->is_delivered ? 'Yes' : 'No') . "\n";
                        echo "Date: {$n->created_at}\n";
                        echo "---\n";
                    }
                }
                break;
                
            case '7':
                // Database Stats
                echo "═══ DATABASE STATISTICS ═══\n\n";
                
                $stats = [
                    'Customers' => DB::table('customers')->count(),
                    'Promo Codes' => DB::table('promo_codes')->count(),
                    'Menu Items' => DB::table('menu_items')->count(),
                    'Orders' => DB::table('orders')->count(),
                    'Reviews' => DB::table('reviews')->count(),
                    'Loyalty Transactions' => DB::table('loyalty_transactions')->count(),
                    'Notifications' => DB::table('notifications')->count(),
                    'Favorites' => DB::table('favorites')->count(),
                ];
                
                foreach ($stats as $label => $count) {
                    echo str_pad($label, 25) . ": {$count}\n";
                }
                
                echo "\n--- Customer Tier Breakdown ---\n";
                $tiers = DB::table('customers')
                    ->select('tier', DB::raw('count(*) as count'))
                    ->groupBy('tier')
                    ->get();
                    
                foreach ($tiers as $tier) {
                    echo ucfirst($tier->tier) . ": {$tier->count}\n";
                }
                
                echo "\n--- Active Promo Codes ---\n";
                $activePromos = DB::table('promo_codes')->where('is_active', true)->count();
                echo "Active: {$activePromos}\n";
                
                echo "\n--- Total Revenue ---\n";
                $totalRevenue = DB::table('orders')
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_price');
                echo "Rp " . number_format($totalRevenue, 0, ',', '.') . "\n";
                
                echo "\n";
                break;
                
            case '8':
                // Custom Query
                echo "Enter your SQL query (SELECT only): ";
                $sql = trim(fgets(STDIN));
                
                if (stripos($sql, 'select') !== 0) {
                    echo "Only SELECT queries are allowed.\n\n";
                } else {
                    $results = DB::select($sql);
                    echo "\n";
                    
                    if (empty($results)) {
                        echo "No results.\n\n";
                    } else {
                        foreach ($results as $row) {
                            print_r($row);
                            echo "---\n";
                        }
                    }
                }
                break;
                
            case '9':
                echo "Goodbye!\n\n";
                exit(0);
                
            default:
                echo "Invalid choice. Please enter 1-9.\n\n";
        }
        
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n\n";
    }
}
