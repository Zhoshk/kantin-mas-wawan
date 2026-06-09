<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            // BURGER
            ['name'=>'Burger Spesial',    'description'=>'Daging sapi premium, keju melt',     'price'=>25000,'emoji'=>'🍔','category'=>'burger','is_hot'=>true],
            ['name'=>'McChicken',         'description'=>'Ayam crispy ala restoran',            'price'=>20000,'emoji'=>'🍗','category'=>'burger','is_hot'=>false,
             'variants'=>[['name'=>'McChicken Original','price'=>20000,'emoji'=>'🍗'],['name'=>'McChicken Spicy','price'=>22000,'emoji'=>'🌶️']]],
            ['name'=>'Filet-O-Fish',      'description'=>'Ikan gurih, saus tartar',             'price'=>22000,'emoji'=>'🐟','category'=>'burger','is_hot'=>false,
             'variants'=>[['name'=>'Filet-O-Fish','price'=>22000,'emoji'=>'🐟'],['name'=>'Double Filet-O-Fish','price'=>32000,'emoji'=>'🐠']]],
            ['name'=>'Cheeseburger',      'description'=>'Keju lumer di atas daging',           'price'=>18000,'emoji'=>'🍔','category'=>'burger','is_hot'=>false],
            ['name'=>'Quarter Pounder',   'description'=>'Patty jumbo spesial',                 'price'=>30000,'emoji'=>'🥩','category'=>'burger','is_hot'=>true],
            // RICE
            ['name'=>'Nasi Goreng Spesial','description'=>'Nasi goreng telor + ayam',           'price'=>20000,'emoji'=>'🍳','category'=>'rice','is_hot'=>true,
             'variants'=>[['name'=>'Nasi Goreng Biasa','price'=>20000,'emoji'=>'🍳'],['name'=>'Nasi Goreng Special','price'=>25000,'emoji'=>'🥘']]],
            ['name'=>'Nasi Ayam Geprek',  'description'=>'Ayam geprek sambel merah',            'price'=>22000,'emoji'=>'🍱','category'=>'rice','is_hot'=>true],
            ['name'=>'Bubur Ayam',        'description'=>'Bubur lembut, topping komplit',       'price'=>15000,'emoji'=>'🥣','category'=>'rice','is_hot'=>false],
            ['name'=>'Nasi Uduk',         'description'=>'Nasi santan + lauk pilihan',          'price'=>18000,'emoji'=>'🍚','category'=>'rice','is_hot'=>false],
            ['name'=>'Mie Goreng',        'description'=>'Mie goreng telur extra pedas',        'price'=>15000,'emoji'=>'🍜','category'=>'rice','is_hot'=>true],
            // SNACK
            ['name'=>'French Fries',      'description'=>'Kentang goreng renyah',               'price'=>12000,'emoji'=>'🍟','category'=>'snack','is_hot'=>false,
             'variants'=>[['name'=>'Small Fries','price'=>10000,'emoji'=>'🍟'],['name'=>'Medium Fries','price'=>12000,'emoji'=>'🍟'],['name'=>'Large Fries','price'=>15000,'emoji'=>'🍟']]],
            ['name'=>'Chicken Nugget',    'description'=>'Nugget crispy 6 pcs',                 'price'=>18000,'emoji'=>'🍗','category'=>'snack','is_hot'=>true,
             'variants'=>[['name'=>'6 pcs Nugget','price'=>18000,'emoji'=>'🍗'],['name'=>'10 pcs Nugget','price'=>28000,'emoji'=>'🍗']]],
            ['name'=>'Pisang Goreng',     'description'=>'Pisang keju coklat susu',             'price'=>10000,'emoji'=>'🍌','category'=>'snack','is_hot'=>false],
            // DRINK
            ['name'=>'Es Teh Manis',      'description'=>'Teh segar dingin',                    'price'=>5000, 'emoji'=>'🥤','category'=>'drink','is_hot'=>false,
             'variants'=>[['name'=>'Es Teh Manis','price'=>5000,'emoji'=>'🥤'],['name'=>'Es Teh Tawar','price'=>4000,'emoji'=>'🫗']]],
            ['name'=>'Es Jeruk Segar',    'description'=>'Jeruk peras dingin',                  'price'=>8000, 'emoji'=>'🍊','category'=>'drink','is_hot'=>false],
            ['name'=>'Jus Alpukat',       'description'=>'Alpukat segar blended',               'price'=>15000,'emoji'=>'🥑','category'=>'drink','is_hot'=>true],
            ['name'=>'Es Kopi Susu',      'description'=>'Kopi susu kekinian',                  'price'=>15000,'emoji'=>'☕','category'=>'drink','is_hot'=>false,
             'variants'=>[['name'=>'Es Kopi Susu','price'=>15000,'emoji'=>'☕'],['name'=>'Kopi Susu Panas','price'=>12000,'emoji'=>'☕']]],
            ['name'=>'Air Mineral',       'description'=>'600ml botol dingin',                  'price'=>4000, 'emoji'=>'💧','category'=>'drink','is_hot'=>false],
        ];

        foreach ($menus as $menu) {
            MenuItem::create([
                'name'        => $menu['name'],
                'description' => $menu['description'],
                'price'       => $menu['price'],
                'emoji'       => $menu['emoji'],
                'category'    => $menu['category'],
                'is_hot'      => $menu['is_hot'],
                'is_active'   => true,
                'variants'    => $menu['variants'] ?? null,
            ]);
        }
    }
}
