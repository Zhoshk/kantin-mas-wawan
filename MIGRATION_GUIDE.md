# Migration Guide: Simple to Complex System

## 🎯 Overview

This guide helps you migrate from the simple canteen system to the enhanced complex system with minimal disruption.

---

## 📋 Pre-Migration Checklist

- [ ] Backup current database
- [ ] Note current order number sequence
- [ ] Export customer phone numbers from existing orders
- [ ] Document custom configurations
- [ ] Test migration on staging environment first
- [ ] Schedule maintenance window
- [ ] Notify users of new features

---

## 🗄️ Database Migration Steps

### Step 1: Backup Existing Data

```bash
# Backup database
php artisan db:backup

# Or using mysqldump
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Step 2: Run New Migrations

```bash
# Run all new migrations
php artisan migrate

# Check migration status
php artisan migrate:status
```

### Expected Output:
```
✓ 2024_01_03_000001_create_customers_table
✓ 2024_01_03_000002_create_promo_codes_table
✓ 2024_01_03_000003_create_reviews_table
✓ 2024_01_03_000004_create_favorites_table
✓ 2024_01_03_000005_create_loyalty_transactions_table
✓ 2024_01_03_000006_create_inventory_logs_table
✓ 2024_01_03_000007_add_advanced_fields_to_menu_items_table
✓ 2024_01_03_000008_add_advanced_fields_to_orders_table
✓ 2024_01_03_000009_create_order_history_table
✓ 2024_01_03_000010_create_notifications_table
```

---

## 🔄 Data Migration Scripts

### Migrate Existing Customers

```php
<?php
// Run this in tinker: php artisan tinker

use App\Models\Customer;
use App\Models\Order;

// Extract unique customers from existing orders
$orders = Order::all()->groupBy('customer_name');

foreach ($orders as $name => $customerOrders) {
    // Get first order for customer
    $firstOrder = $customerOrders->first();
    
    // Create customer profile
    $customer = Customer::create([
        'name' => $name,
        'phone' => $firstOrder->phone ?? 'unknown', // Add phone if available
        'total_orders' => $customerOrders->count(),
        'total_spent' => $customerOrders->where('status', '!=', 'cancelled')->sum('total_price'),
        'last_order_at' => $customerOrders->max('created_at'),
        'created_at' => $firstOrder->created_at,
    ]);
    
    // Update customer tier
    $customer->updateTier();
    
    // Link existing orders to customer
    Order::where('customer_name', $name)->update([
        'customer_id' => $customer->id,
    ]);
    
    echo "✓ Migrated customer: {$name}\n";
}

echo "\n✓ Customer migration completed!\n";
```

### Initialize Menu Item Fields

```php
<?php
// Run this in tinker: php artisan tinker

use App\Models\MenuItem;

MenuItem::chunk(100, function ($items) {
    foreach ($items as $item) {
        $item->update([
            'preparation_time' => 10, // default 10 minutes
            'low_stock_threshold' => 5,
            'optimal_stock_level' => 50,
            'is_featured' => $item->is_hot, // make hot items featured
            'spice_level' => 0,
            'average_rating' => 0,
            'review_count' => 0,
            'times_ordered' => 0,
        ]);
        
        echo "✓ Updated: {$item->name}\n";
    }
});

echo "\n✓ Menu items initialization completed!\n";
```

### Calculate Historical Order Stats

```php
<?php
// Run this in tinker: php artisan tinker

use App\Models\Order;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

// Update menu items with times_ordered
$itemOrders = DB::table('order_items')
    ->select('menu_item_id', DB::raw('SUM(quantity) as total'))
    ->groupBy('menu_item_id')
    ->get();

foreach ($itemOrders as $stat) {
    MenuItem::find($stat->menu_item_id)?->update([
        'times_ordered' => $stat->total,
    ]);
}

// Update order subtotals (same as total_price initially)
Order::whereNull('subtotal')->update([
    'subtotal' => DB::raw('total_price'),
]);

echo "\n✓ Historical stats calculated!\n";
```

### Create Initial Order History

```php
<?php
// Run this in tinker: php artisan tinker

use App\Models\Order;
use App\Models\OrderHistory;

Order::chunk(100, function ($orders) {
    foreach ($orders as $order) {
        // Log creation
        OrderHistory::create([
            'order_id' => $order->id,
            'status_from' => null,
            'status_to' => 'pending',
            'changed_by' => 'system',
            'created_at' => $order->created_at,
        ]);
        
        // Log current status if not pending
        if ($order->status !== 'pending') {
            OrderHistory::create([
                'order_id' => $order->id,
                'status_from' => 'pending',
                'status_to' => $order->status,
                'changed_by' => 'admin',
                'created_at' => $order->updated_at,
            ]);
        }
    }
});

echo "\n✓ Order history created!\n";
```

---

## 🎨 Frontend Migration

### Update Order Form

**Before:**
```javascript
{
  customer_name: "John Doe",
  payment_method: "qris",
  items: [...]
}
```

**After:**
```javascript
{
  customer_id: 123,              // NEW: link to customer
  customer_name: "John Doe",     // Keep for compatibility
  promo_code_used: "WELCOME20",  // NEW: promo code
  payment_method: "qris",
  order_type: "take_away",       // NEW: order type
  loyalty_points_used: 100,      // NEW: redeem points
  special_instructions: "Extra spicy", // NEW: notes
  scheduled_for: "2024-01-15 12:00", // NEW: pre-order
  items: [...]
}
```

### Update Menu Display

**Add to menu items:**
```javascript
// Display ratings
if (item.review_count > 0) {
  showRating(item.average_rating, item.review_count);
}

// Show dietary tags
if (item.dietary_tags) {
  showTags(item.dietary_tags); // vegetarian, halal, etc
}

// Check availability
if (!item.isAvailableNow()) {
  disableOrdering();
  showAvailabilityTime();
}

// Show stock status
if (item.stock !== null && item.stock <= item.low_stock_threshold) {
  showLowStockWarning();
}
```

---

## ⚙️ Configuration Updates

### .env File Updates

Add these new configuration options:

```env
# Loyalty Program
LOYALTY_POINTS_RATE=1
LOYALTY_POINTS_VALUE=100
LOYALTY_POINTS_EXPIRY_DAYS=365

# Order Settings
ORDER_CUTOFF_HOUR=11
MIN_ORDER_AMOUNT=10000
DELIVERY_FEE=5000
SERVICE_FEE_PERCENT=5
TAX_PERCENT=10

# Promo Codes
MAX_PROMO_DISCOUNT=50000

# Inventory
LOW_STOCK_ALERT_THRESHOLD=5
AUTO_RESTOCK_ENABLED=false

# Notifications
NOTIFICATION_CHANNELS=whatsapp,email
WHATSAPP_ENABLED=true
EMAIL_ENABLED=false
```

---

## 🧪 Testing Checklist

After migration, test these scenarios:

### Basic Functionality
- [ ] Old orders still display correctly
- [ ] New orders can be created
- [ ] Order numbers continue sequence
- [ ] Stock tracking works
- [ ] Existing menu items display

### New Features
- [ ] Customer registration
- [ ] Loyalty points earning
- [ ] Loyalty points redemption
- [ ] Promo code validation
- [ ] Promo code application
- [ ] Review submission
- [ ] Favorite items
- [ ] Scheduled orders
- [ ] Analytics dashboard
- [ ] Inventory logs

### Edge Cases
- [ ] Orders with no customer_id (old orders)
- [ ] Menu items with null stock
- [ ] Expired promo codes
- [ ] Insufficient loyalty points
- [ ] Out of stock items
- [ ] Cutoff time validation

---

## 📊 Post-Migration Tasks

### 1. Seeder Data (Optional)

```bash
# Create sample promo codes
php artisan tinker
```

```php
use App\Models\PromoCode;

// Welcome promo for new customers
PromoCode::create([
    'code' => 'WELCOME20',
    'name' => 'Welcome Discount',
    'description' => '20% off for new customers',
    'type' => 'percentage',
    'discount_value' => 20,
    'min_purchase' => 25000,
    'max_discount' => 50000,
    'usage_limit' => 100,
    'usage_per_customer' => 1,
    'valid_from' => now(),
    'valid_until' => now()->addMonths(3),
    'first_order_only' => true,
    'is_active' => true,
]);

// Weekend promo
PromoCode::create([
    'code' => 'WEEKEND10',
    'name' => 'Weekend Special',
    'description' => '10% off on weekends',
    'type' => 'percentage',
    'discount_value' => 10,
    'min_purchase' => 0,
    'valid_from' => now(),
    'valid_until' => now()->addYear(),
    'is_active' => true,
]);
```

### 2. Award Retroactive Loyalty Points

```php
use App\Models\Customer;
use App\Models\Order;

Customer::chunk(50, function ($customers) {
    foreach ($customers as $customer) {
        // Calculate points from past orders
        $completedOrders = Order::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->whereNull('loyalty_points_earned')
            ->get();
        
        foreach ($completedOrders as $order) {
            $points = (int) ($order->total_price * 0.01); // 1% as points
            
            $customer->addLoyaltyPoints(
                $points,
                $order,
                "Retroactive points for order #{$order->order_number}"
            );
            
            $order->update(['loyalty_points_earned' => $points]);
        }
        
        echo "✓ Awarded points to: {$customer->name}\n";
    }
});
```

### 3. Initialize Inventory Logs

```php
use App\Models\MenuItem;
use App\Models\InventoryLog;

MenuItem::whereNotNull('stock')->each(function ($item) {
    InventoryLog::create([
        'menu_item_id' => $item->id,
        'type' => 'adjustment',
        'quantity_before' => 0,
        'quantity_change' => $item->stock,
        'quantity_after' => $item->stock,
        'reason' => 'Initial stock import',
        'performed_by' => 'system',
        'created_at' => now(),
    ]);
});
```

---

## 🚨 Rollback Plan

If migration fails, rollback using:

```bash
# Rollback last batch of migrations
php artisan migrate:rollback --step=10

# Restore database backup
mysql -u username -p database_name < backup_YYYYMMDD.sql

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 📞 Support & Troubleshooting

### Common Issues

**Issue 1: Foreign Key Constraint Errors**
```sql
-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS=0;

-- Run migrations
-- ...

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;
```

**Issue 2: NULL Values in New Fields**
```php
// Default values are set in migrations
// But you can manually update if needed
Order::whereNull('subtotal')->update(['subtotal' => DB::raw('total_price')]);
```

**Issue 3: Existing Orders Missing customer_id**
```php
// This is okay! Old orders can remain without customer_id
// They'll still display in admin, just won't have customer profile linked
```

---

## 📈 Monitoring After Migration

### Week 1 - Monitor These Metrics:
- [ ] Total orders created successfully
- [ ] Customer registration rate
- [ ] Promo code usage
- [ ] Loyalty points earned
- [ ] Review submission rate
- [ ] System errors/exceptions
- [ ] API response times
- [ ] Database query performance

### Week 2-4 - Analyze:
- [ ] Customer adoption of new features
- [ ] Most used features
- [ ] Feature usage patterns
- [ ] Customer feedback
- [ ] Admin feedback
- [ ] Performance bottlenecks
- [ ] Optimization opportunities

---

## ✅ Migration Complete Checklist

- [ ] All migrations ran successfully
- [ ] Existing data preserved
- [ ] New tables created
- [ ] Customers migrated from orders
- [ ] Menu items initialized
- [ ] Promo codes created
- [ ] Loyalty points configured
- [ ] Frontend updated
- [ ] API endpoints tested
- [ ] Admin panel functional
- [ ] Analytics working
- [ ] Documentation updated
- [ ] Team trained
- [ ] Users notified
- [ ] Monitoring setup
- [ ] Backup schedule updated

---

## 🎉 Success!

Your system has been upgraded from a simple ordering system to an enterprise-grade food service platform!

**New Capabilities:**
✅ Customer loyalty program
✅ Advanced analytics
✅ Review system
✅ Promo codes
✅ Inventory management
✅ Scheduled orders
✅ Customer insights
✅ And much more!

---

## 📚 Additional Resources

- [FEATURES.md](./FEATURES.md) - Complete feature documentation
- [COMPLEXITY_SUMMARY.md](./COMPLEXITY_SUMMARY.md) - System complexity analysis
- [README.md](./README.md) - General project information

---

*Migration Guide Version 1.0*
*Last Updated: 2026-06-08*
