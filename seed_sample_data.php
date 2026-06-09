<?php

/**
 * Quick Sample Data Seeder
 * Run: php seed_sample_data.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SEEDING SAMPLE DATA ===\n\n";

try {
    // 1. Create Sample Customers
    echo "👥 Creating sample customers...\n";
    
    $customers = [
        [
            'name' => 'John Platinum',
            'phone' => '081234567801',
            'email' => 'john@example.com',
            'loyalty_points' => 5000,
            'tier' => 'platinum',
            'total_orders' => 50,
            'total_spent' => 1500000,
            'birth_date' => '1990-01-15',
        ],
        [
            'name' => 'Jane Gold',
            'phone' => '081234567802',
            'email' => 'jane@example.com',
            'loyalty_points' => 3000,
            'tier' => 'gold',
            'total_orders' => 30,
            'total_spent' => 750000,
            'birth_date' => '1995-06-20',
        ],
        [
            'name' => 'Bob Silver',
            'phone' => '081234567803',
            'email' => 'bob@example.com',
            'loyalty_points' => 1000,
            'tier' => 'silver',
            'total_orders' => 15,
            'total_spent' => 350000,
            'birth_date' => '1988-03-10',
        ],
    ];

    foreach ($customers as $customer) {
        $customer['dietary_preferences'] = json_encode(['halal']);
        $customer['allergens'] = json_encode([]);
        $customer['created_at'] = now();
        $customer['updated_at'] = now();
        DB::table('customers')->insert($customer);
        echo "   ✓ {$customer['name']} ({$customer['tier']})\n";
    }

    // 2. Create Promo Codes
    echo "\n💳 Creating promo codes...\n";
    
    $promoCodes = [
        [
            'code' => 'WELCOME20',
            'name' => 'Welcome Discount',
            'description' => '20% off for new customers',
            'type' => 'percentage',
            'discount_value' => 20,
            'min_purchase' => 25000,
            'max_discount' => 50000,
            'usage_limit' => 100,
            'first_order_only' => true,
        ],
        [
            'code' => 'WEEKEND10',
            'name' => 'Weekend Special',
            'description' => '10% off on weekends',
            'type' => 'percentage',
            'discount_value' => 10,
            'min_purchase' => 0,
            'max_discount' => 30000,
            'usage_limit' => null,
        ],
        [
            'code' => 'FREEDELIVERY',
            'name' => 'Free Delivery',
            'description' => 'Free delivery for orders over 100k',
            'type' => 'free_delivery',
            'discount_value' => 0,
            'min_purchase' => 100000,
            'max_discount' => null,
            'usage_limit' => null,
        ],
    ];

    foreach ($promoCodes as $promo) {
        $promo['usage_per_customer'] = 1;
        $promo['times_used'] = 0;
        $promo['valid_from'] = now();
        $promo['valid_until'] = now()->addMonths(6);
        $promo['is_active'] = true;
        $promo['created_at'] = now();
        $promo['updated_at'] = now();
        DB::table('promo_codes')->insert($promo);
        echo "   ✓ {$promo['code']} - {$promo['name']}\n";
    }

    // 3. Enhance Menu Items (if they exist)
    echo "\n🍽️  Enhancing menu items...\n";
    
    $menuItems = DB::table('menu_items')->get();
    
    if ($menuItems->isEmpty()) {
        echo "   ⚠ No menu items found. Skipping...\n";
    } else {
        foreach ($menuItems as $item) {
            DB::table('menu_items')
                ->where('id', $item->id)
                ->update([
                    'preparation_time' => rand(5, 20),
                    'calories' => rand(200, 800),
                    'ingredients' => json_encode(['ingredient1', 'ingredient2', 'ingredient3']),
                    'allergens' => json_encode([]),
                    'dietary_tags' => json_encode(['halal']),
                    'spice_level' => rand(0, 3),
                    'average_rating' => rand(35, 50) / 10, // 3.5 to 5.0
                    'review_count' => rand(5, 50),
                    'times_ordered' => rand(10, 100),
                    'low_stock_threshold' => 10,
                    'optimal_stock_level' => 50,
                    'is_featured' => rand(0, 1) == 1,
                ]);
            echo "   ✓ {$item->name}\n";
        }
    }

    // 4. Create Sample Reviews (if menu items exist)
    if (!$menuItems->isEmpty() && DB::table('customers')->count() > 0) {
        echo "\n⭐ Creating sample reviews...\n";
        
        // Skip reviews if no orders exist
        $orderCount = DB::table('orders')->count();
        if ($orderCount == 0) {
            echo "   ⚠ No orders found. Skipping reviews...\n";
        } else {
            $customerIds = DB::table('customers')->pluck('id')->toArray();
            $menuItemIds = DB::table('menu_items')->pluck('id')->toArray();
            $orderIds = DB::table('orders')->pluck('id')->toArray();
            
            for ($i = 0; $i < min(5, count($orderIds)); $i++) {
                $review = [
                    'menu_item_id' => $menuItemIds[array_rand($menuItemIds)],
                    'customer_id' => $customerIds[array_rand($customerIds)],
                    'order_id' => $orderIds[array_rand($orderIds)],
                    'rating' => rand(4, 5),
                    'comment' => 'Delicious! Highly recommended.',
                    'tags' => json_encode(['tasty', 'recommended']),
                    'helpful_count' => rand(0, 10),
                    'is_verified_purchase' => true,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                DB::table('reviews')->insert($review);
                $reviewNum = $i + 1;
                echo "   ✓ Review #{$reviewNum}\n";
            }
        }
    }

    // 5. Create Sample Loyalty Transactions
    echo "\n💰 Creating loyalty transactions...\n";
    
    $customers = DB::table('customers')->get();
    foreach ($customers as $customer) {
        $transaction = [
            'customer_id' => $customer->id,
            'order_id' => null,
            'type' => 'bonus',
            'points' => 1000,
            'balance_after' => $customer->loyalty_points,
            'description' => 'Welcome bonus',
            'expires_at' => now()->addYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('loyalty_transactions')->insert($transaction);
        echo "   ✓ {$customer->name}: +1000 points\n";
    }

    // 6. Create Sample Notifications
    echo "\n🔔 Creating sample notifications...\n";
    
    foreach ($customers as $customer) {
        $notification = [
            'customer_id' => $customer->id,
            'type' => 'welcome',
            'title' => 'Welcome to Kantin Mas Wawan!',
            'message' => "Hi {$customer->name}, thank you for joining us. Enjoy your {$customer->tier} tier benefits!",
            'data' => json_encode(['tier' => $customer->tier]),
            'channel' => 'in_app',
            'sent_at' => now(),
            'is_delivered' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('notifications')->insert($notification);
        echo "   ✓ Welcome notification for {$customer->name}\n";
    }

    // Summary
    echo "\n";
    echo "═══════════════════════════════════════════\n";
    echo "✅ SAMPLE DATA SEEDED SUCCESSFULLY!\n";
    echo "═══════════════════════════════════════════\n\n";
    
    echo "📊 Data Summary:\n";
    echo "   • Customers: " . DB::table('customers')->count() . "\n";
    echo "   • Promo Codes: " . DB::table('promo_codes')->count() . "\n";
    echo "   • Reviews: " . DB::table('reviews')->count() . "\n";
    echo "   • Loyalty Transactions: " . DB::table('loyalty_transactions')->count() . "\n";
    echo "   • Notifications: " . DB::table('notifications')->count() . "\n";
    echo "   • Menu Items: " . DB::table('menu_items')->count() . "\n\n";

    echo "🎉 You can now:\n";
    echo "   1. View customers in database\n";
    echo "   2. Test promo code validation\n";
    echo "   3. Check loyalty points system\n";
    echo "   4. Browse enhanced menu items\n";
    echo "   5. Use the API endpoints (once controllers are loaded)\n\n";

    echo "📚 Documentation:\n";
    echo "   • See FEATURES.md for complete feature list\n";
    echo "   • See QUICK_REFERENCE.md for API usage\n";
    echo "   • See SYSTEM_OVERVIEW.md for system details\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n\n";
}
