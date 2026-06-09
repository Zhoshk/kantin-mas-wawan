<?php
/**
 * Standalone Database Viewer
 * NO Laravel dependencies - just pure PHP + MySQL
 * Run: php view_data.php
 */

// Database configuration
$host = '127.0.0.1';
$port = 3306;
$dbname = 'kantin_mas_wawan';
$username = 'root';
$password = '';

// Connect to database
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ]
    );
    
    echo "\n";
    echo "╔══════════════════════════════════════════════════╗\n";
    echo "║     KANTIN MAS WAWAN - DATABASE VIEWER          ║\n";
    echo "╚══════════════════════════════════════════════════╝\n\n";
    echo "Connected to database: {$dbname}\n\n";
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Main menu loop
while (true) {
    echo "═══════════════════════════════════════════════════\n";
    echo "MENU:\n";
    echo "  1. View Customers\n";
    echo "  2. View Promo Codes\n";
    echo "  3. View Menu Items\n";
    echo "  4. View Orders\n";
    echo "  5. View Loyalty Transactions\n";
    echo "  6. View Notifications\n";
    echo "  7. Database Statistics\n";
    echo "  8. Table List\n";
    echo "  9. Exit\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "Enter choice (1-9): ";
    
    $choice = trim(fgets(STDIN));
    echo "\n";
    
    try {
        switch ($choice) {
            case '1':
                // Customers
                echo "═══ CUSTOMERS ═══\n\n";
                $stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
                $customers = $stmt->fetchAll();
                
                if (empty($customers)) {
                    echo "No customers found.\n\n";
                } else {
                    foreach ($customers as $c) {
                        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        echo "ID: {$c->id}\n";
                        echo "Name: {$c->name}\n";
                        echo "Phone: {$c->phone}\n";
                        echo "Email: {$c->email}\n";
                        echo "Tier: " . strtoupper($c->tier) . "\n";
                        echo "Loyalty Points: " . number_format($c->loyalty_points) . "\n";
                        echo "Total Orders: {$c->total_orders}\n";
                        echo "Total Spent: Rp " . number_format($c->total_spent, 0, ',', '.') . "\n";
                        if ($c->birth_date) {
                            echo "Birth Date: {$c->birth_date}\n";
                        }
                        echo "Member Since: {$c->created_at}\n";
                    }
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    echo "\nTotal: " . count($customers) . " customers\n\n";
                }
                break;
                
            case '2':
                // Promo Codes
                echo "═══ PROMO CODES ═══\n\n";
                $stmt = $pdo->query("SELECT * FROM promo_codes ORDER BY is_active DESC, created_at DESC");
                $promos = $stmt->fetchAll();
                
                if (empty($promos)) {
                    echo "No promo codes found.\n\n";
                } else {
                    foreach ($promos as $p) {
                        $status = $p->is_active ? "✓ ACTIVE" : "✗ INACTIVE";
                        $discount = $p->type == 'percentage' 
                            ? "{$p->discount_value}%" 
                            : "Rp " . number_format($p->discount_value, 0, ',', '.');
                            
                        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        echo "Code: {$p->code} [{$status}]\n";
                        echo "Name: {$p->name}\n";
                        echo "Type: {$p->type}\n";
                        echo "Discount: {$discount}\n";
                        echo "Min Purchase: Rp " . number_format($p->min_purchase, 0, ',', '.') . "\n";
                        echo "Usage: {$p->times_used}";
                        if ($p->usage_limit) {
                            echo " / {$p->usage_limit}";
                        }
                        echo "\n";
                        echo "Valid: {$p->valid_from} to {$p->valid_until}\n";
                    }
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    echo "\nTotal: " . count($promos) . " promo codes\n\n";
                }
                break;
                
            case '3':
                // Menu Items
                echo "═══ MENU ITEMS ═══\n\n";
                $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY category, name");
                $items = $stmt->fetchAll();
                
                if (empty($items)) {
                    echo "No menu items found.\n\n";
                } else {
                    $currentCategory = '';
                    foreach ($items as $item) {
                        if ($currentCategory != $item->category) {
                            $currentCategory = $item->category;
                            echo "\n▼▼▼ " . strtoupper($currentCategory) . " ▼▼▼\n\n";
                        }
                        
                        $status = $item->is_active ? "✓" : "✗";
                        $featured = $item->is_featured ? "⭐" : "";
                        
                        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        echo "[{$status}] {$item->emoji} {$item->name} {$featured}\n";
                        echo "Price: Rp " . number_format($item->price, 0, ',', '.') . "\n";
                        
                        if ($item->description) {
                            echo "Description: {$item->description}\n";
                        }
                        
                        if ($item->preparation_time) {
                            echo "Prep Time: {$item->preparation_time} min\n";
                        }
                        
                        if ($item->calories) {
                            echo "Calories: {$item->calories}\n";
                        }
                        
                        if ($item->spice_level > 0) {
                            $spice = str_repeat("🌶", $item->spice_level);
                            echo "Spice: {$spice} ({$item->spice_level}/5)\n";
                        }
                        
                        if ($item->average_rating > 0) {
                            $stars = number_format($item->average_rating, 1);
                            echo "Rating: ⭐ {$stars} ({$item->review_count} reviews)\n";
                        }
                        
                        echo "Times Ordered: {$item->times_ordered}\n";
                        
                        if ($item->stock !== null) {
                            echo "Stock: {$item->stock}";
                            if ($item->stock <= $item->low_stock_threshold) {
                                echo " ⚠️ LOW STOCK";
                            }
                            echo "\n";
                        } else {
                            echo "Stock: ∞ Unlimited\n";
                        }
                    }
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    echo "\nTotal: " . count($items) . " menu items\n\n";
                }
                break;
                
            case '4':
                // Orders
                echo "═══ ORDERS (Last 10) ═══\n\n";
                $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
                $orders = $stmt->fetchAll();
                
                if (empty($orders)) {
                    echo "No orders found.\n\n";
                } else {
                    foreach ($orders as $o) {
                        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        echo "Order #: {$o->order_number}\n";
                        echo "Customer: {$o->customer_name}";
                        if ($o->customer_id) {
                            echo " (ID: {$o->customer_id})";
                        }
                        echo "\n";
                        echo "Total: Rp " . number_format($o->total_price, 0, ',', '.') . "\n";
                        echo "Status: " . strtoupper($o->status) . "\n";
                        echo "Payment: {$o->payment_method} - {$o->payment_status}\n";
                        
                        if ($o->promo_code_used) {
                            echo "Promo: {$o->promo_code_used} (Discount: Rp " . number_format($o->discount_amount, 0, ',', '.') . ")\n";
                        }
                        
                        if ($o->loyalty_points_used > 0) {
                            echo "Points Used: {$o->loyalty_points_used}\n";
                        }
                        
                        if ($o->loyalty_points_earned > 0) {
                            echo "Points Earned: {$o->loyalty_points_earned}\n";
                        }
                        
                        echo "Date: {$o->created_at}\n";
                    }
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    echo "\nShowing last 10 orders\n\n";
                }
                break;
                
            case '5':
                // Loyalty Transactions
                echo "═══ LOYALTY TRANSACTIONS (Last 20) ═══\n\n";
                $stmt = $pdo->query("
                    SELECT lt.*, c.name as customer_name 
                    FROM loyalty_transactions lt
                    JOIN customers c ON lt.customer_id = c.id
                    ORDER BY lt.created_at DESC
                    LIMIT 20
                ");
                $transactions = $stmt->fetchAll();
                
                if (empty($transactions)) {
                    echo "No transactions found.\n\n";
                } else {
                    foreach ($transactions as $t) {
                        $sign = $t->points >= 0 ? '+' : '';
                        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        echo "Customer: {$t->customer_name}\n";
                        echo "Type: " . strtoupper($t->type) . "\n";
                        echo "Points: {$sign}{$t->points}\n";
                        echo "Balance After: {$t->balance_after}\n";
                        echo "Description: {$t->description}\n";
                        echo "Date: {$t->created_at}\n";
                    }
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    echo "\nShowing last 20 transactions\n\n";
                }
                break;
                
            case '6':
                // Notifications
                echo "═══ NOTIFICATIONS (Last 20) ═══\n\n";
                $stmt = $pdo->query("
                    SELECT n.*, c.name as customer_name 
                    FROM notifications n
                    JOIN customers c ON n.customer_id = c.id
                    ORDER BY n.created_at DESC
                    LIMIT 20
                ");
                $notifications = $stmt->fetchAll();
                
                if (empty($notifications)) {
                    echo "No notifications found.\n\n";
                } else {
                    foreach ($notifications as $n) {
                        $delivered = $n->is_delivered ? "✓ DELIVERED" : "⏳ PENDING";
                        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        echo "To: {$n->customer_name}\n";
                        echo "Type: {$n->type} | Channel: {$n->channel}\n";
                        echo "Title: {$n->title}\n";
                        echo "Message: {$n->message}\n";
                        echo "Status: {$delivered}\n";
                        echo "Date: {$n->created_at}\n";
                    }
                    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                    echo "\nShowing last 20 notifications\n\n";
                }
                break;
                
            case '7':
                // Statistics
                echo "═══ DATABASE STATISTICS ═══\n\n";
                
                $tables = [
                    'customers' => 'Customers',
                    'promo_codes' => 'Promo Codes',
                    'menu_items' => 'Menu Items',
                    'orders' => 'Orders',
                    'order_items' => 'Order Items',
                    'reviews' => 'Reviews',
                    'favorites' => 'Favorites',
                    'loyalty_transactions' => 'Loyalty Transactions',
                    'inventory_logs' => 'Inventory Logs',
                    'notifications' => 'Notifications',
                    'order_history' => 'Order History',
                ];
                
                echo "▼ Table Counts:\n\n";
                foreach ($tables as $table => $label) {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
                    $count = $stmt->fetch()->count;
                    echo "  " . str_pad($label, 25) . ": " . number_format($count) . "\n";
                }
                
                echo "\n▼ Customer Tier Breakdown:\n\n";
                $stmt = $pdo->query("SELECT tier, COUNT(*) as count FROM customers GROUP BY tier");
                $tiers = $stmt->fetchAll();
                foreach ($tiers as $tier) {
                    echo "  " . str_pad(ucfirst($tier->tier), 15) . ": {$tier->count}\n";
                }
                
                echo "\n▼ Revenue Statistics:\n\n";
                $stmt = $pdo->query("SELECT SUM(total_price) as total FROM orders WHERE status != 'cancelled'");
                $revenue = $stmt->fetch()->total ?? 0;
                echo "  Total Revenue: Rp " . number_format($revenue, 0, ',', '.') . "\n";
                
                $stmt = $pdo->query("SELECT AVG(total_price) as avg FROM orders WHERE status != 'cancelled'");
                $avgOrder = $stmt->fetch()->avg ?? 0;
                echo "  Avg Order Value: Rp " . number_format($avgOrder, 0, ',', '.') . "\n";
                
                echo "\n▼ Promo Code Stats:\n\n";
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM promo_codes WHERE is_active = 1");
                $activePromos = $stmt->fetch()->count;
                echo "  Active Promos: {$activePromos}\n";
                
                $stmt = $pdo->query("SELECT SUM(times_used) as total FROM promo_codes");
                $totalUsage = $stmt->fetch()->total ?? 0;
                echo "  Total Usage: {$totalUsage}\n";
                
                echo "\n▼ Loyalty Program:\n\n";
                $stmt = $pdo->query("SELECT SUM(loyalty_points) as total FROM customers");
                $totalPoints = $stmt->fetch()->total ?? 0;
                echo "  Total Points: " . number_format($totalPoints) . "\n";
                
                $stmt = $pdo->query("SELECT AVG(loyalty_points) as avg FROM customers");
                $avgPoints = $stmt->fetch()->avg ?? 0;
                echo "  Avg per Customer: " . number_format($avgPoints, 0) . "\n";
                
                echo "\n";
                break;
                
            case '8':
                // Table List
                echo "═══ ALL TABLES ═══\n\n";
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $i = 1;
                foreach ($tables as $table) {
                    echo "  {$i}. {$table}\n";
                    $i++;
                }
                echo "\nTotal: " . count($tables) . " tables\n\n";
                break;
                
            case '9':
                echo "Thank you for using Kantin Mas Wawan!\n";
                echo "Your enhanced system is ready to use! 🚀\n\n";
                exit(0);
                
            default:
                echo "Invalid choice. Please enter 1-9.\n\n";
        }
        
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n\n";
    }
}
