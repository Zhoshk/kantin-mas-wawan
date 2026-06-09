<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Dropping partially created tables...\n";

try {
    DB::statement('DROP TABLE IF EXISTS product_analytics');
    echo "✓ Dropped product_analytics\n";
    
    DB::statement('DROP TABLE IF EXISTS customer_behavior_analytics');
    echo "✓ Dropped customer_behavior_analytics\n";
    
    DB::statement('DROP TABLE IF EXISTS sales_forecasts');
    echo "✓ Dropped sales_forecasts\n";
    
    DB::statement('DROP TABLE IF EXISTS market_basket_analysis');
    echo "✓ Dropped market_basket_analysis\n";
    
    echo "\nTables cleaned successfully! Now run: php artisan migrate\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
