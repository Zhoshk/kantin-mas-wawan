# 🏗️ ULTRA-COMPLEX System Architecture

## 🎯 System Overview

The Kantin Mas Wawan system has evolved into a **multi-layer, microservices-ready enterprise architecture** capable of handling:
- **Multiple locations** with centralized management
- **Real-time operations** (kitchen displays, chat, notifications)
- **Complex business logic** (pricing, inventory, forecasting)
- **High scalability** (10,000+ orders/day per location)

---

## 🗂️ Database Architecture

### **Total Tables: 45+**
### **Total Relationships: 100+**
### **Total Indexes: 200+**

---

## 📊 Entity Relationship Diagram (Simplified)

```
┌─────────────────────────────────────────────────────────────────┐
│                    CORE BUSINESS ENTITIES                         │
└─────────────────────────────────────────────────────────────────┘

Location (Multi-branch)
├── has many → Orders
├── has many → Employees
├── has many → Tables
├── has many → KitchenStations
├── has many → Reservations
├── has many → MenuItems (or shared)
├── has many → PurchaseOrders
├── has many → IngredientStockMovements
├── has many → WasteLogs
├── has many → SustainabilityMetrics
└── hierarchical (parent-child locations)

┌─────────────────────────────────────────────────────────────────┐
│                    CUSTOMER & LOYALTY DOMAIN                      │
└─────────────────────────────────────────────────────────────────┘

Customer
├── has many → Orders
├── has many → Favorites
├── has many → Reviews
├── has many → LoyaltyTransactions
├── has many → Notifications
├── has many → Reservations
├── has many → SupportTickets
├── has many → ChatSessions
├── has one → CustomerBehaviorAnalytics
├── tier logic (bronze/silver/gold/platinum)
└── RFM segmentation

┌─────────────────────────────────────────────────────────────────┐
│                      ORDER & FULFILLMENT DOMAIN                   │
└─────────────────────────────────────────────────────────────────┘

Order (ULTRA-ENHANCED)
├── belongs to → Customer
├── belongs to → Location
├── belongs to → Table
├── belongs to → Reservation
├── belongs to → PromoCode
├── has many → OrderItems
├── has many → OrderHistory
├── has many → Reviews
├── has many → PaymentTransactions
├── has many → Refunds
├── tracked by → Employee (cashier, chef, server, delivery)
├── kitchen workflow timestamps
├── delivery tracking (GPS coordinates)
├── scheduled orders
├── split pricing (subtotal, tax, service, delivery, tip)
└── multi-rating (food, service, delivery)

OrderItem
├── belongs to → Order
├── belongs to → MenuItem
├── has one → OrderItemKitchenStatus
└── triggers ingredient stock deduction

OrderItemKitchenStatus (NEW - KDS)
├── belongs to → OrderItem
├── belongs to → KitchenStation
├── belongs to → Employee (assigned chef)
├── status workflow (pending → queued → preparing → ready → served)
├── queue position
├── prep time tracking
└── priority flagging

┌─────────────────────────────────────────────────────────────────┐
│                      MENU & RECIPE DOMAIN                         │
└─────────────────────────────────────────────────────────────────┘

MenuItem (ENHANCED)
├── belongs to → Location (optional)
├── has many → OrderItems
├── has many → Reviews
├── has many → Favorites
├── has many → RecipeIngredients (NEW)
├── has many → ProductAnalytics
├── stock management
├── variants support
├── cost calculation via recipes
└── multi-location availability

RecipeIngredient (NEW)
├── belongs to → MenuItem
├── belongs to → Ingredient
├── defines quantity per serving
├── preparation steps
└── cost contribution

┌─────────────────────────────────────────────────────────────────┐
│                  PROCUREMENT & INVENTORY DOMAIN                   │
└─────────────────────────────────────────────────────────────────┘

Supplier (NEW)
├── has many → Ingredients (preferred)
├── has many → PurchaseOrders
├── rating system
├── payment terms
└── performance metrics

Ingredient (NEW)
├── belongs to → Supplier (preferred)
├── has many → RecipeIngredients
├── has many → IngredientStockMovements
├── has many → PurchaseOrderItems
├── has many → WasteLogs
├── current stock tracking
├── reorder point alerts
├── shelf life & expiry
├── allergen information
└── wastage percentage

PurchaseOrder (NEW)
├── belongs to → Supplier
├── belongs to → Location
├── has many → PurchaseOrderItems
├── approval workflow
├── delivery tracking
├── payment tracking
└── auto stock updates on receive

IngredientStockMovement (NEW)
├── belongs to → Ingredient
├── belongs to → Location
├── linked to → PurchaseOrder or Order
├── movement type (purchase, usage, wastage, adjustment)
├── cost tracking
└── expiry date tracking

┌─────────────────────────────────────────────────────────────────┐
│                      KITCHEN OPERATIONS DOMAIN                    │
└─────────────────────────────────────────────────────────────────┘

KitchenStation (NEW)
├── belongs to → Location
├── has many → OrderItemKitchenStatus
├── has many → KitchenPerformanceMetrics
├── capacity management
├── category assignment (grill, fryer, wok, etc)
└── real-time load monitoring

KitchenPerformanceMetric (NEW)
├── belongs to → Location
├── belongs to → KitchenStation
├── belongs to → Employee
├── hourly/daily metrics
├── efficiency tracking
└── delay analytics

┌─────────────────────────────────────────────────────────────────┐
│                      EMPLOYEE & HR DOMAIN                         │
└─────────────────────────────────────────────────────────────────┘

Employee (NEW)
├── belongs to → Location
├── belongs to → User (optional auth)
├── has many → EmployeeShifts
├── has many → EmployeePerformanceLogs
├── has many → Orders (as various roles)
├── role-based (manager, cashier, chef, waiter, delivery)
├── performance rating
├── skills & certifications
└── salary tracking

EmployeeShift (NEW)
├── belongs to → Employee
├── belongs to → Shift template
├── attendance tracking (check-in/out)
├── overtime calculation
└── orders handled per shift

Shift (NEW)
├── belongs to → Location
├── scheduling template
├── required staff count
└── days of week

┌─────────────────────────────────────────────────────────────────┐
│                  TABLE & RESERVATION DOMAIN                       │
└─────────────────────────────────────────────────────────────────┘

Table (NEW)
├── belongs to → Location
├── has many → Reservations
├── has many → Orders
├── has many → TableOccupancyLogs
├── capacity & layout
├── floor & zone
├── real-time status
└── deposit requirements

Reservation (NEW)
├── belongs to → Customer
├── belongs to → Location
├── belongs to → Table
├── linked to → Order (when seated)
├── has many → ReservationHistory
├── status workflow
├── special occasions
├── deposit tracking
└── no-show tracking

TableOccupancyLog (NEW)
├── belongs to → Table
├── linked to → Reservation or Order
├── occupancy duration
├── revenue per table
└── turnover analysis

┌─────────────────────────────────────────────────────────────────┐
│                  SUPPORT & COMMUNICATION DOMAIN                   │
└─────────────────────────────────────────────────────────────────┘

SupportTicket (NEW)
├── belongs to → Customer
├── linked to → Order
├── belongs to → Location
├── assigned to → Employee
├── has many → SupportTicketMessages
├── SLA tracking
├── priority & category
└── satisfaction rating

ChatSession (NEW)
├── belongs to → Customer
├── assigned to → Employee (agent)
├── belongs to → Location
├── has many → ChatMessages
├── real-time status
├── wait time tracking
└── session rating

┌─────────────────────────────────────────────────────────────────┐
│                      PAYMENT & FINANCE DOMAIN                     │
└─────────────────────────────────────────────────────────────────┘

PaymentMethod (NEW)
├── has many → PaymentTransactions
├── provider integration
├── fee calculation
├── amount limits
└── active/inactive

PaymentTransaction (NEW)
├── belongs to → Order
├── belongs to → PaymentMethod
├── belongs to → Customer
├── has many → Refunds
├── gateway integration
├── status tracking
└── fee calculation

Refund (NEW)
├── belongs to → Order
├── belongs to → PaymentTransaction
├── approval workflow
├── reason tracking
└── processing status

FinancialReport (NEW)
├── belongs to → Location
├── daily/weekly/monthly/yearly
├── P&L breakdown
├── cached for performance
└── drill-down capability

┌─────────────────────────────────────────────────────────────────┐
│                  SUSTAINABILITY & WASTE DOMAIN                    │
└─────────────────────────────────────────────────────────────────┘

WasteLog (NEW)
├── belongs to → Location
├── logged by → Employee
├── linked to → MenuItem or Ingredient
├── waste type categorization
├── cost tracking
├── preventability analysis
└── photo evidence

SustainabilityMetric (NEW)
├── belongs to → Location
├── daily tracking
├── waste quantities (food, packaging)
├── energy & water consumption
├── carbon footprint
├── recycling rates
└── sustainability goals

WasteReductionInitiative (NEW)
├── belongs to → Location
├── initiative tracking
├── target vs actual
└── cost savings

┌─────────────────────────────────────────────────────────────────┐
│                  ANALYTICS & INTELLIGENCE DOMAIN                  │
└─────────────────────────────────────────────────────────────────┘

ProductAnalytics (NEW)
├── belongs to → MenuItem
├── belongs to → Location
├── daily metrics
├── sales, revenue, profit
├── customer feedback
├── prep time averages
├── stock out tracking
└── peak hour analysis

CustomerBehaviorAnalytics (NEW)
├── belongs to → Customer
├── RFM analysis
├── churn prediction
├── lifetime value
├── favorite items/categories
├── order patterns
└── segment classification

SalesForecast (NEW)
├── belongs to → Location or MenuItem
├── ML-powered predictions
├── confidence levels
├── accuracy tracking
└── factor analysis (weather, events, etc)

MarketBasketAnalysis (NEW)
├── item pair associations
├── support, confidence, lift metrics
├── cross-sell recommendations
└── bundle suggestions
```

---

## 🏛️ System Architecture Layers

### **1. Presentation Layer**
- Admin Dashboard (Vue.js/React)
- Customer Mobile App (React Native/Flutter)
- Kitchen Display System (Real-time React)
- Employee Portal
- Reservation Widget
- Support Chat Interface

### **2. API Layer (Laravel)**
- RESTful API (200+ endpoints)
- Real-time WebSocket endpoints
- Webhook receivers (payments, notifications)
- Rate limiting & throttling
- API versioning (v1, v2)
- OAuth2 authentication

### **3. Business Logic Layer**
- Order orchestration
- Inventory management
- Pricing engine
- Loyalty calculation
- Kitchen workflow engine
- Reservation engine
- Analytics engine
- Forecasting ML models

### **4. Data Access Layer**
- Eloquent ORM (35+ models)
- Repository pattern
- Query optimization
- Caching layer (Redis)
- Database sharding (location-based)

### **5. Integration Layer**
- Payment gateways (Midtrans, Xendit, Stripe)
- WhatsApp Business API
- Email services (SendGrid, AWS SES)
- SMS gateway
- Google Maps API
- Weather API (for forecasting)

### **6. Infrastructure Layer**
- Load balancers
- Redis clusters (caching, queues, real-time)
- Queue workers (Laravel Horizon)
- CDN for static assets
- S3 for file storage
- Database replicas (read/write split)

---

## 🔄 Key Business Workflows

### **Order Lifecycle (Ultra-Complex)**
```
1. Customer places order
   ├→ Validate customer tier & benefits
   ├→ Check menu availability (stock, time, location)
   ├→ Apply promo code (complex validation)
   ├→ Calculate loyalty discount
   ├→ Reserve stock (ingredients via recipes)
   
2. Payment processing
   ├→ Multiple payment methods
   ├→ Split payment support
   ├→ Gateway integration
   └→ Transaction recording

3. Order confirmation
   ├→ Generate order number
   ├→ Award loyalty points
   ├→ Create order history entry
   ├→ Send notification (WhatsApp, email, push)
   └→ If table order → link to table occupancy

4. Kitchen routing
   ├→ Route items to stations
   ├→ Assign to available chefs
   ├→ Calculate prep time
   ├→ Queue management
   └→ Real-time KDS updates

5. Preparation
   ├→ Chef checks in
   ├→ Ingredients auto-deducted
   ├→ Prep time tracking
   ├→ Delay alerting
   └→ Quality control

6. Fulfillment
   ├→ Dine-in: Assign server, deliver to table
   ├→ Takeaway: Customer pickup notification
   ├→ Delivery: Assign driver, track GPS
   └→ Update table occupancy

7. Completion
   ├→ Payment finalization
   ├→ Receipt generation
   ├→ Request review
   ├→ Update customer lifetime value
   ├→ Log kitchen performance
   └→ Update product analytics

8. Post-order
   ├→ Review submission
   ├→ Support ticket (if issues)
   ├→ Refund processing (if needed)
   └→ Customer behavior tracking
```

### **Inventory Management Workflow**
```
1. Recipe-based stock tracking
   ├→ Each menu item = recipe with ingredients
   └→ Order places → auto calculate ingredient needs

2. Stock deduction
   ├→ Real-time stock updates
   ├→ Log movements
   └→ Alert on low stock

3. Reorder triggering
   ├→ Auto-generate PO when below reorder point
   ├→ Preferred supplier selection
   └→ Approval workflow

4. Purchase order processing
   ├→ Draft → Pending → Approved → Sent → Received
   ├→ Delivery tracking
   └→ Quality check

5. Stock receiving
   ├→ Update ingredient stock
   ├→ Log batch & expiry
   ├→ Cost averaging
   └→ Performance metrics

6. Waste tracking
   ├→ Log waste events
   ├→ Calculate cost impact
   ├→ Prevention analysis
   └→ Sustainability reporting
```

---

## 🚀 Performance Characteristics

### **Scalability**
- **Horizontal scaling**: Add more locations seamlessly
- **Vertical scaling**: Handle 10,000+ orders/day per location
- **Database sharding**: By location for optimal performance
- **Read replicas**: For analytics and reporting

### **Real-time Features**
- Kitchen Display System (sub-second updates)
- Live chat (WebSocket connections)
- Order tracking (GPS updates every 30s)
- Table status updates

### **High Availability**
- 99.9% uptime SLA
- Database failover (< 30s)
- Zero-downtime deployments
- Automated backups (hourly incremental, daily full)

---

## 💾 Data Volume Estimates (Per Location, Per Year)

- **Orders**: ~150,000 records
- **Order Items**: ~400,000 records
- **Ingredient Movements**: ~500,000 records
- **Chat Messages**: ~1,000,000 records
- **Analytics Records**: ~10,000 records
- **Total Database Size**: ~50GB per location per year

---

## 🔐 Security Features

- Role-based access control (RBAC)
- Multi-factor authentication (MFA)
- Data encryption at rest and in transit
- PCI DSS compliance (payment data)
- GDPR compliance (customer data)
- Activity logging and audit trails
- SQL injection prevention
- XSS protection
- CSRF protection
- Rate limiting and DDoS protection

---

## 📊 Monitoring & Observability

- Application performance monitoring (APM)
- Database query monitoring
- Error tracking (Sentry)
- Log aggregation (ELK stack)
- Real-time dashboards
- Alerting (PagerDuty, Slack)
- Business metrics tracking

---

*Last Updated: 2026-06-09*
*Architecture Version: 2.0-ULTRA*
