<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\PromoCode;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Review;
use App\Models\InventoryLog;

class EnhancedSystemSeeder extends Seeder
{
    /**
     * Seed the enhanced system with sample data
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding enhanced system data...');

        // 1. Create Sample Customers
        $this->createCustomers();

        // 2. Create Promo Codes
        $this->createPromoCodes();

        // 3. Update Menu Items with Advanced Fields
        $this->enhanceMenuItems();

        // 4. Create Sample Reviews
        $this->createReviews();

        // 5. Initialize Inventory Logs
        $this->initializeInventoryLogs();

        $this->command->info('✅ Enhanced system seeding completed!');
    }

    /**
     * Create sample customers with different tiers
     */
    protected function createCustomers(): void
    {
        $this->command->info('👥 Creating sample customers...');

        $customers = [
            [
                'name' => 'John Platinum',
                'phone' => '081234567801',
                'email' => 'john.platinum@example.com',
                'loyalty_points' => 5000,
                'tier' => 'platinum',
                'total_orders' => 50,
                'total_spent' => 1500000,
                'birth_date' => '1990-05-15',
                'dietary_preferences' => ['halal'],
                'allergens' => [],
            ],
            [
                'name' => 'Jane Gold',
                'phone' => '081234567802',
                'email' => 'jane.gold@example.com',
                'loyalty_points' => 3000,
                'tier' => 'gold',
                'total_orders' => 30,
                'total_spent' => 750000,
                'birth_date' => '1995-08-20',
                'dietary_preferences' => ['vegetarian'],
                'allergens' => ['peanuts'],
            ],
            [
                'name' => 'Bob Silver',
                'phone' => '081234567803',
                'email' => 'bob.silver@example.com',
                'loyalty_points' => 1000,
                'tier' => 'silver',
                'total_orders' => 15,
                'total_spent' => 350000,
                'birth_date' => '1988-03-10',
                'dietary_preferences' => [],
                'allergens' => ['dairy'],
            ],
            [
                'name' => 'Alice Bronze',
                'phone' => '081234567804',
                'email' => 'alice.bronze@example.com',
                'loyalty_points' => 200,
                'tier' => 'bronze',
                'total_orders' => 5,
                'total_spent' => 125000,
                'birth_date' => '2000-11-25',
                'dietary_preferences' => ['halal'],
                'allergens' => [],
            ],
            [
                'name' => 'Charlie NewUser',
                'phone' => '081234567805',
                'email' => 'charlie.new@example.com',
                'loyalty_points' => 0,
                'tier' => 'bronze',
                'total_orders' => 0,
                'total_spent' => 0,
                'birth_date' => '1992-07-30',
                'dietary_preferences' => [],
                'allergens' => ['gluten'],
            ],
        ];

        foreach ($customers as $customerData) {
            $customer = Customer::create($customerData);
            $this->command->info("   ✓ Created customer: {$customer->name} ({$customer->tier})");
        }
    }

    /**
     * Create various promo codes
     */
    protected function createPromoCodes(): void
    {
        $this->command->info('🎟️  Creating promo codes...');

        $promoCodes = [
            [
                'code' => 'WELCOME20',
                'name' => 'Welcome Discount',
                'description' => '20% off for first-time customers',
                'type' => 'percentage',
                'discount_value' => 20,
                'min_purchase' => 25000,
                'max_discount' => 50000,
                'usage_limit' => 100,
                'usage_per_customer' => 1,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(6),
                'first_order_only' => true,
                'is_active' => true,
            ],
            [
                'code' => 'WEEKEND10',
                'name' => 'Weekend Special',
                'description' => '10% off every weekend',
                'type' => 'percentage',
                'discount_value' => 10,
                'min_purchase' => 0,
                'max_discount' => 30000,
                'usage_limit' => null,
                'usage_per_customer' => 10,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'FIXED50K',
                'name' => 'Rp 50,000 Off',
                'description' => 'Fixed Rp 50,000 discount for orders over Rp 200,000',
                'type' => 'fixed',
                'discount_value' => 50000,
                'min_purchase' => 200000,
                'max_discount' => null,
                'usage_limit' => 50,
                'usage_per_customer' => 1,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'PLATINUM15',
                'name' => 'Platinum Member Exclusive',
                'description' => '15% off for Platinum members only',
                'type' => 'percentage',
                'discount_value' => 15,
                'min_purchase' => 0,
                'max_discount' => 100000,
                'usage_limit' => null,
                'usage_per_customer' => 5,
                'valid_from' => now(),
                'valid_until' => now()->addYear(),
                'customer_tiers' => ['platinum'],
                'is_active' => true,
            ],
            [
                'code' => 'FREEDELIVERY',
                'name' => 'Free Delivery',
                'description' => 'Free delivery for orders over Rp 100,000',
                'type' => 'free_delivery',
                'discount_value' => 0,
                'min_purchase' => 100000,
                'max_discount' => null,
                'usage_limit' => null,
                'usage_per_customer' => 999,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(12),
                'is_active' => true,
            ],
            [
                'code' => 'EXPIRED',
                'name' => 'Expired Promo',
                'description' => 'This promo has expired',
                'type' => 'percentage',
                'discount_value' => 50,
                'min_purchase' => 0,
                'max_discount' => null,
                'usage_limit' => 10,
                'usage_per_customer' => 1,
                'valid_from' => now()->subMonths(2),
                'valid_until' => now()->subMonth(),
                'is_active' => false,
            ],
        ];

        foreach ($promoCodes as $promoData) {
            $promo = PromoCode::create($promoData);
            $status = $promo->is_active ? '✓' : '✗';
            $this->command->info("   {$status} Created promo: {$promo->code}");
        }
    }

    /**
     * Enhance menu items with advanced fields
     */
    protected function enhanceMenuItems(): void
    {
        $this->command->info('🍽️  Enhancing menu items...');

        $menuEnhancements = [
            'Burger Ayam Spesial' => [
                'preparation_time' => 15,
                'calories' => 450,
                'ingredients' => ['chicken', 'lettuce', 'tomato', 'cheese', 'bun'],
                'allergens' => ['gluten', 'dairy'],
                'dietary_tags' => ['halal'],
                'spice_level' => 2,
                'is_featured' => true,
                'low_stock_threshold' => 10,
                'optimal_stock_level' => 50,
            ],
            'Nasi Goreng Kampung' => [
                'preparation_time' => 12,
                'calories' => 550,
                'ingredients' => ['rice', 'egg', 'chicken', 'vegetables', 'spices'],
                'allergens' => ['egg'],
                'dietary_tags' => ['halal'],
                'spice_level' => 3,
                'is_featured' => true,
                'low_stock_threshold' => 8,
                'optimal_stock_level' => 40,
            ],
            'Es Teh Manis' => [
                'preparation_time' => 3,
                'calories' => 150,
                'ingredients' => ['tea', 'sugar', 'ice'],
                'allergens' => [],
                'dietary_tags' => ['vegan', 'halal'],
                'spice_level' => 0,
                'low_stock_threshold' => 20,
                'optimal_stock_level' => 100,
            ],
        ];

        foreach ($menuEnhancements as $itemName => $enhancements) {
            $item = MenuItem::where('name', 'LIKE', "%{$itemName}%")->first();
            if ($item) {
                $item->update($enhancements);
                $this->command->info("   ✓ Enhanced: {$item->name}");
            }
        }

        // Set random times_ordered for existing items
        MenuItem::all()->each(function ($item) {
            $item->update([
                'times_ordered' => rand(5, 100),
                'preparation_time' => $item->preparation_time ?? rand(5, 20),
            ]);
        });
    }

    /**
     * Create sample reviews
     */
    protected function createReviews(): void
    {
        $this->command->info('⭐ Creating sample reviews...');

        $customers = Customer::all();
        $menuItems = MenuItem::all();

        if ($customers->isEmpty() || $menuItems->isEmpty()) {
            $this->command->warn('   ⚠ Skipping reviews (no customers or menu items)');
            return;
        }

        $reviews = [
            [
                'rating' => 5,
                'comment' => 'Luar biasa enak! Porsinya juga besar. Pasti order lagi!',
                'tags' => ['tasty', 'recommended', 'generous_portion'],
            ],
            [
                'rating' => 5,
                'comment' => 'Best burger in town! Dagingnya juicy dan bumbunya pas.',
                'tags' => ['tasty', 'fresh', 'recommended'],
            ],
            [
                'rating' => 4,
                'comment' => 'Enak sih tapi agak kelamaan nyampenya.',
                'tags' => ['tasty', 'slow_service'],
            ],
            [
                'rating' => 5,
                'comment' => 'Langganan terus deh. Gak pernah ngecewain!',
                'tags' => ['consistent', 'recommended'],
            ],
            [
                'rating' => 3,
                'comment' => 'Standar aja. Tidak istimewa tapi tidak mengecewakan.',
                'tags' => ['average'],
            ],
            [
                'rating' => 5,
                'comment' => 'Pedasnya mantap! Cocok buat yang suka pedas.',
                'tags' => ['spicy', 'tasty', 'recommended'],
            ],
            [
                'rating' => 4,
                'comment' => 'Harga sebanding dengan rasa. Worth it!',
                'tags' => ['good_value', 'tasty'],
            ],
            [
                'rating' => 5,
                'comment' => 'Favoritku banget! Udah 10x order ga pernah bosen.',
                'tags' => ['favorite', 'consistent', 'recommended'],
            ],
        ];

        // Create fake orders first for the reviews
        foreach ($reviews as $index => $reviewData) {
            $customer = $customers->random();
            $menuItem = $menuItems->random();

            // Create a completed order
            $order = Order::create([
                'order_number' => 'ORD-' . str_pad(1000 + $index, 3, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'total_price' => $menuItem->price,
                'subtotal' => $menuItem->price,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => 'qris',
                'order_type' => 'take_away',
                'completed_at' => now()->subDays(rand(1, 30)),
            ]);

            // Create order item
            $order->items()->create([
                'menu_item_id' => $menuItem->id,
                'item_name' => $menuItem->name,
                'price' => $menuItem->price,
                'quantity' => 1,
                'subtotal' => $menuItem->price,
            ]);

            // Create review
            $review = Review::create([
                'menu_item_id' => $menuItem->id,
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
                'tags' => $reviewData['tags'],
                'helpful_count' => rand(0, 15),
                'is_verified_purchase' => true,
                'is_visible' => true,
            ]);

            $this->command->info("   ✓ Review by {$customer->name} for {$menuItem->name} ({$review->rating}⭐)");
        }
    }

    /**
     * Initialize inventory logs
     */
    protected function initializeInventoryLogs(): void
    {
        $this->command->info('📦 Initializing inventory logs...');

        MenuItem::whereNotNull('stock')->each(function ($item) {
            InventoryLog::create([
                'menu_item_id' => $item->id,
                'type' => 'restock',
                'quantity_before' => 0,
                'quantity_change' => $item->stock,
                'quantity_after' => $item->stock,
                'reason' => 'Initial stock from seeder',
                'performed_by' => 'system',
            ]);

            $this->command->info("   ✓ Initialized inventory for: {$item->name} (Stock: {$item->stock})");
        });
    }
}
