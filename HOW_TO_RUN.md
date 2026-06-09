# 🚀 How to Run Kantin Mas Wawan System

## ✅ System is Currently Running!

Your Laravel development server is now active and accessible at:

```
🌐 Main Application: http://127.0.0.1:8000
🌐 Alternative:      http://localhost:8000
```

---

## 🎯 Quick Access Guide

### **1. Open in Browser**

Simply open your web browser and navigate to:

```
http://localhost:8000
```

or

```
http://127.0.0.1:8000
```

### **2. Available Endpoints**

Since this is a backend API system, you can access:

#### **API Endpoints** (Test with Postman, Insomnia, or curl)
```
http://localhost:8000/api/
```

#### **Example API Routes** (you may need to create controllers first):
- `GET  /api/locations` - List all locations
- `GET  /api/menu-items` - List menu items  
- `GET  /api/orders` - List orders
- `POST /api/orders` - Create new order
- `GET  /api/customers` - List customers
- `GET  /api/employees` - List employees
- `GET  /api/suppliers` - List suppliers
- `GET  /api/analytics/dashboard` - Analytics dashboard

---

## 🛠️ **Starting & Stopping the Server**

### **Method 1: Using Laravel Artisan (Simplest)**

**Start the server:**
```bash
php artisan serve
```

**Access at:** http://localhost:8000

**Stop the server:** Press `Ctrl + C` in the terminal

---

### **Method 2: Start All Services (Advanced)**

If you want to run multiple services (server, queue, frontend):

```bash
# Option A: Use composer script
composer run dev

# Option B: Manual start
php artisan serve          # Terminal 1
php artisan queue:work     # Terminal 2 (for background jobs)
npm run dev               # Terminal 3 (if you have frontend)
```

---

## 📱 **Testing the API**

### **Using Browser** (for GET requests)
Just open: `http://localhost:8000/api/orders`

### **Using curl** (command line)
```bash
# Get all orders
curl http://localhost:8000/api/orders

# Get specific order
curl http://localhost:8000/api/orders/1

# Create new order (POST)
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{"customer_name":"John Doe","total_price":50000}'
```

### **Using Postman or Insomnia**
1. Download Postman: https://www.postman.com/
2. Create new request
3. Set URL: `http://localhost:8000/api/orders`
4. Choose method: GET, POST, PUT, DELETE
5. Send request

---

## 🗄️ **Database Management**

### **View Database with phpMyAdmin**
If you have XAMPP or similar:
1. Open http://localhost/phpmyadmin
2. Select database: `kantin_mas_wawan`
3. Browse all 49 tables!

### **View Database with TablePlus/DBeaver**
1. Download TablePlus or DBeaver
2. Connect with:
   - Host: 127.0.0.1
   - Port: 3306
   - Database: kantin_mas_wawan
   - Username: root
   - Password: (your password)

### **Using Tinker (Laravel Console)**
```bash
php artisan tinker

# Try these commands:
>>> App\Models\Order::count()
>>> App\Models\MenuItem::all()
>>> App\Models\Location::first()
>>> DB::table('orders')->count()
```

---

## 📊 **View System Statistics**

Run the stats script anytime:
```bash
php system_stats.php
```

This shows:
- Total tables (49)
- Total columns (740)
- Most complex tables
- All Phase 2 tables

---

## 🔧 **Common Commands**

### **View All Routes**
```bash
php artisan route:list
```

### **Check Migration Status**
```bash
php artisan migrate:status
```

### **Rollback Migrations** (if needed)
```bash
php artisan migrate:rollback
```

### **Clear Cache**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### **Run Seeders** (populate with sample data)
```bash
php artisan db:seed
```

---

## 🎨 **Next Steps to Build UI**

Currently you have the backend (database + models). To create a user interface:

### **Option 1: Create API Controllers**

Create controllers for each model:
```bash
php artisan make:controller Api/LocationController --api
php artisan make:controller Api/SupplierController --api
php artisan make:controller Api/EmployeeController --api
php artisan make:controller Api/ReservationController --api
```

### **Option 2: Build Admin Dashboard**

1. **Using Laravel Breeze/Jetstream:**
```bash
composer require laravel/breeze --dev
php artisan breeze:install
npm install && npm run dev
```

2. **Using Filament (Recommended for Admin Panel):**
```bash
composer require filament/filament
php artisan filament:install --panels
php artisan make:filament-resource Location
php artisan make:filament-resource Supplier
php artisan make:filament-resource Employee
```

3. **Using React/Vue:**
```bash
# Install Inertia.js
composer require inertiajs/inertia-laravel
npm install @inertiajs/vue3
# or
npm install @inertiajs/react
```

### **Option 3: Use API with Frontend Framework**

Build separate frontend using:
- **React** + Vite
- **Vue.js** + Vite  
- **Next.js** (React)
- **Nuxt.js** (Vue)

---

## 🔐 **Security Notes**

For production deployment:
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Set up proper authentication
- [ ] Configure CORS properly
- [ ] Use HTTPS
- [ ] Set up firewall rules
- [ ] Regular database backups

---

## 🆘 **Troubleshooting**

### **Port Already in Use**
```bash
# Use different port
php artisan serve --port=8001
```

### **Database Connection Error**
Check `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kantin_mas_wawan
DB_USERNAME=root
DB_PASSWORD=your_password
```

### **Permission Errors**
```bash
# Windows
icacls storage /grant Users:F /t
icacls bootstrap/cache /grant Users:F /t
```

### **Composer Dependencies Issue**
```bash
composer install
composer update
```

---

## 📚 **Documentation**

- **Laravel Docs:** https://laravel.com/docs
- **API Development:** https://laravel.com/docs/controllers#api-resource-controllers
- **Database:** https://laravel.com/docs/database
- **Eloquent ORM:** https://laravel.com/docs/eloquent

---

## 🎯 **Current System Status**

✅ **Database:** 49 tables, 740 columns, 162 indexes  
✅ **Models:** 27 Eloquent models created  
✅ **Migrations:** All completed successfully  
✅ **Server:** Running on http://localhost:8000  
🔨 **Controllers:** Need to be created  
🔨 **Frontend:** Need to be built  
🔨 **API Routes:** Need to be defined  

---

## 🚀 **Recommended Development Flow**

1. ✅ **Database Schema** - DONE! (49 tables)
2. ✅ **Models** - DONE! (27 models)
3. 🔨 **Create Controllers** - Next step
4. 🔨 **Define Routes** - Next step
5. 🔨 **Build API** - Next step
6. 🔨 **Create Frontend** - Final step
7. 🔨 **Deploy to Production** - When ready

---

## 💡 **Pro Tips**

- Use **Postman** to test your API during development
- Install **Laravel Debugbar** for debugging: `composer require barryvdh/laravel-debugbar --dev`
- Use **Laravel Telescope** for monitoring: `composer require laravel/telescope --dev`
- Keep your `.env` file secure and never commit it
- Regular database backups: `php artisan backup:run`

---

**Your ultra-complex enterprise system is ready to go!** 🎉

Access it now at: **http://localhost:8000**

For questions, check the documentation files:
- `TRANSFORMATION_SUMMARY.md`
- `ULTRA_ARCHITECTURE.md`
- `IMPLEMENTATION_COMPLETE.md`
