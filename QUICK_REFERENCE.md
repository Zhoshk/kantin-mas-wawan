# Quick Reference Guide

## 🚀 Getting Started

### Installation & Setup
```bash
# Install dependencies (if composer is set up)
composer install

# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed --class=EnhancedSystemSeeder
```

---

## 📡 API Quick Reference

### Authentication
All admin endpoints require `X-Admin-Key` header.

---

## 🧑‍🤝‍🧑 Customer APIs

### Register/Update Customer
```http
POST /api/customers
Content-Type: application/json

{
  "name": "John Doe",
  "phone": "08123456789",
  "email": "john@example.com",
  "birth_date": "1990-01-15",
  "dietary_preferences": ["vegetarian"],
  "allergens": ["peanuts"]
}
```

### Get Customer Profile
```http
GET /api/customers/08123456789
```

### Add to Favorites
```http
POST /api/customers/{customerId}/favorites
Content-Type: application/json

{
  "menu_item_id": 5
}
```

### Get Customer Statistics
```http
GET /api/customers/{customerId}/stats
```

---

## 🍽️ Menu APIs

### Get Active Menu
```http
GET /api/menu
```

### Check Stock
```http
GET /api/menu/{menuItemId}/stock
```

### Get Reviews
```http
GET /api/menu-items/{menuItemId}/reviews
```

---

## 🛒 Order APIs

### Create Order (Enhanced)
```http
POST /api/orders
Content-Type: application/json

{
  "customer_id": 123,
  "customer_name": "John Doe",
  "payment_method": "qris",
  "order_type": "take_away",
  "promo_code_used": "WELCOME20",
  "loyalty_points_used": 100,
  "special_instructions": "Extra pedas",
  "scheduled_for": null,
  "items": [
    {
      "menu_item_id": 1,
      "variant_name": "Large",
      "quantity": 2
    }
  ]
}
```

### Get Order Status
```http
GET /api/orders/{orderNumber}/status
```

---

## 💳 Promo Code APIs

### Validate Promo
```http
POST /api/promo-codes/validate
Content-Type: application/json

{
  "code": "WELCOME20",
  "customer_id": 123,
  "order_total": 50000,
  "items": []
}
```

**Response:**
```json
{
  "valid": true,
  "message": "Kode promo berhasil digunakan!",
  "data": {
    "promo_code": {...},
    "discount_amount": 10000,
    "final_total": 40000
  }
}
```

---

## ⭐ Review APIs

### Submit Review
```http
POST /api/reviews
Content-Type: application/json

{
  "menu_item_id": 5,
  "customer_id": 123,
  "order_id": 456,
  "rating": 5,
  "comment": "Delicious!",
  "tags": ["tasty", "recommended"],
  "images": ["url1.jpg", "url2.jpg"]
}
```

### Mark Review as Helpful
```http
POST /api/reviews/{reviewId}/helpful
```

---

## 🔐 Admin APIs

### Menu Management
```http
# Get all menu (including inactive)
GET /api/admin/menu

# Create menu item
POST /api/admin/menu
{
  "name": "New Item",
  "price": 25000,
  "category": "burger",
  "preparation_time": 15,
  "calories": 450,
  "ingredients": ["chicken", "lettuce"],
  "allergens": ["gluten"],
  "dietary_tags": ["halal"],
  "spice_level": 2,
  "low_stock_threshold": 10,
  "is_featured": true
}

# Toggle active status
PATCH /api/admin/menu/{menuItemId}/toggle
```

### Order Management
```http
# Get all orders with filters
GET /api/admin/orders?status=pending&date=2024-01-01&search=John

# Update order status
PATCH /api/admin/orders/{orderId}/status
{
  "status": "processing"
}
```

### Promo Code Management
```http
# Create promo code
POST /api/admin/promo-codes
{
  "code": "WEEKEND10",
  "name": "Weekend Special",
  "type": "percentage",
  "discount_value": 10,
  "min_purchase": 0,
  "valid_from": "2024-01-01",
  "valid_until": "2024-12-31",
  "usage_limit": 100
}

# Toggle promo status
PATCH /api/admin/promo-codes/{promoCodeId}/toggle
```

### Review Management
```http
# Get all reviews
GET /api/admin/reviews?menu_item_id=5&rating=5

# Admin response
POST /api/admin/reviews/{reviewId}/respond
{
  "response": "Thank you for your feedback!"
}

# Toggle visibility
PATCH /api/admin/reviews/{reviewId}/toggle-visibility
```

### Analytics
```http
# Dashboard overview
GET /api/admin/analytics/dashboard?period=today

# Customer insights
GET /api/admin/analytics/customer-insights

# Menu performance
GET /api/admin/analytics/menu-performance

# Review sentiment
GET /api/admin/analytics/reviews-sentiment

# Inventory status
GET /api/admin/analytics/inventory
```

---

## 🏷️ Common Response Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## 🎯 Quick Code Snippets

### Check Customer Tier Benefits
```php
$customer = Customer::find(1);
$benefits = $customer->getTierBenefits();
// Returns: ['discount', 'points_multiplier', 'free_delivery_threshold', 'birthday_bonus']
```

### Calculate Order with Promo
```php
$promo = PromoCode::where('code', 'WELCOME20')->first();
$isValid = $promo->isValid($customer, $orderTotal);
$discount = $promo->calculateDiscount($orderTotal);
```

### Add Loyalty Points
```php
$customer->addLoyaltyPoints(
    points: 100,
    order: $order,
    description: "Purchase reward"
);
```

### Redeem Loyalty Points
```php
$success = $customer->redeemLoyaltyPoints(
    points: 100,
    order: $order
);
```

### Check Menu Availability
```php
$menuItem = MenuItem::find(5);
$isAvailable = $menuItem->isAvailableNow();
$isLowStock = $menuItem->isLowStock();
```

### Log Inventory Change
```php
InventoryLog::logChange(
    menuItem: $menuItem,
    type: 'sale',
    quantityChange: -2,
    order: $order,
    reason: "Order #{$order->order_number}",
    performedBy: 'system'
);
```

### Update Customer Tier
```php
$customer->updateTier(); // Auto-calculates based on total_spent
```

---

## 📊 Database Scopes

### Order Scopes
```php
Order::today()->get();
Order::scheduled()->get();
Order::byType('delivery')->get();
```

### MenuItem Scopes
```php
MenuItem::active()->get();
MenuItem::featured()->get();
MenuItem::lowStock()->get();
```

### Review Scopes
```php
Review::visible()->get();
Review::verified()->get();
```

### PromoCode Scopes
```php
PromoCode::active()->get();
```

---

## 🔍 Useful Queries

### Top 10 Customers by Spending
```php
Customer::orderByDesc('total_spent')
    ->limit(10)
    ->get();
```

### Items Needing Restock
```php
MenuItem::lowStock()
    ->active()
    ->orderBy('stock')
    ->get();
```

### Recent High-Rated Reviews
```php
Review::visible()
    ->where('rating', '>=', 4)
    ->latest()
    ->limit(10)
    ->get();
```

### Today's Revenue
```php
Order::today()
    ->where('status', '!=', 'cancelled')
    ->sum('total_price');
```

### Most Ordered Items This Month
```php
DB::table('order_items')
    ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->whereMonth('orders.created_at', now()->month)
    ->select('menu_items.name', DB::raw('SUM(order_items.quantity) as total'))
    ->groupBy('menu_items.id', 'menu_items.name')
    ->orderByDesc('total')
    ->limit(10)
    ->get();
```

---

## 🎲 Sample Data Values

### Customer Tiers
- **Bronze**: Rp 0 - 199,999
- **Silver**: Rp 200,000 - 499,999
- **Gold**: Rp 500,000 - 999,999
- **Platinum**: Rp 1,000,000+

### Tier Benefits
| Tier | Discount | Points | Free Delivery | Birthday |
|------|----------|--------|---------------|----------|
| Bronze | 0% | 1.0x | > Rp 100k | Rp 500 |
| Silver | 5% | 1.2x | > Rp 75k | Rp 1,000 |
| Gold | 10% | 1.5x | > Rp 50k | Rp 3,000 |
| Platinum | 15% | 2.0x | > Rp 30k | Rp 5,000 |

### Spice Levels
- 0: Not spicy
- 1: Mild
- 2: Medium
- 3: Spicy
- 4: Very spicy
- 5: Extra spicy

### Order Types
- `dine_in`: Eat at location
- `take_away`: Pick up and go
- `delivery`: Deliver to address

### Order Status Flow
1. `pending` → Order received
2. `processing` → Being prepared
3. `ready` → Ready for pickup/delivery
4. `completed` → Order complete
5. `cancelled` → Order cancelled

### Review Tags
- `tasty`, `delicious`, `yummy`
- `fresh`, `quality`
- `spicy`, `mild`, `sweet`
- `recommended`, `favorite`
- `generous_portion`, `good_value`
- `slow_service`, `fast_service`
- `average`, `disappointing`

---

## 🧪 Testing Endpoints

### Using cURL

```bash
# Register customer
curl -X POST http://localhost:8000/api/customers \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","phone":"08199999999"}'

# Validate promo
curl -X POST http://localhost:8000/api/promo-codes/validate \
  -H "Content-Type: application/json" \
  -d '{"code":"WELCOME20","order_total":50000}'

# Get menu
curl http://localhost:8000/api/menu

# Admin: Get dashboard (requires X-Admin-Key header)
curl -X GET http://localhost:8000/api/admin/analytics/dashboard \
  -H "X-Admin-Key: your-admin-key"
```

---

## 🐛 Debugging Tips

### Enable Query Log
```php
DB::enableQueryLog();
// ... your code ...
dd(DB::getQueryLog());
```

### Check Relationships
```php
$order = Order::with(['items', 'customer', 'promoCode'])->first();
dd($order->toArray());
```

### Validate Promo Code
```php
$promo = PromoCode::find(1);
$customer = Customer::find(1);
dump($promo->isValid($customer, 50000));
dump($promo->calculateDiscount(50000));
```

### Check Stock Availability
```php
$item = MenuItem::find(1);
dump([
    'stock' => $item->stock,
    'is_low_stock' => $item->isLowStock(),
    'is_available' => $item->isAvailableNow(),
]);
```

---

## 📝 Common Tasks

### Add New Menu Item
1. Admin creates via POST `/api/admin/menu`
2. Set all fields including dietary info
3. Initialize stock via inventory log
4. Toggle `is_active` if needed

### Process Order
1. Customer selects items
2. Validate promo code if provided
3. Check stock availability
4. Create order with POST `/api/orders`
5. System reserves stock automatically
6. Customer receives confirmation

### Handle Review
1. Customer submits review after order completion
2. Review appears in admin panel
3. Admin responds if needed
4. Menu item rating auto-updates

### Run Promotion
1. Create promo code via POST `/api/admin/promo-codes`
2. Set validity dates and restrictions
3. Customers apply code at checkout
4. System validates and calculates discount
5. Track usage via `times_used` field

---

## 🔧 Maintenance Commands

```bash
# Clear all cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run migrations
php artisan migrate

# Seed data
php artisan db:seed

# Optimize application
php artisan optimize

# Run tinker (interactive shell)
php artisan tinker
```

---

## 📞 Support

- **Features Documentation**: See `FEATURES.md`
- **Migration Guide**: See `MIGRATION_GUIDE.md`
- **Complexity Analysis**: See `COMPLEXITY_SUMMARY.md`

---

*Quick Reference v1.0 - Updated 2026-06-08*
