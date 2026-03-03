<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Coupon::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome Discount',
                'description' => 'Get 10% off your first order',
                'type' => 'percentage',
                'value' => 10,
                'minimum_amount' => 50,
                'usage_limit' => 1000,
                'starts_at' => now(),
                'expires_at' => Carbon::now()->addMonths(6),
                'is_active' => true
            ],
            [
                'code' => 'SAVE20',
                'name' => '20% Off Everything',
                'description' => 'Save 20% on all products',
                'type' => 'percentage',
                'value' => 20,
                'minimum_amount' => 100,
                'usage_limit' => 500,
                'starts_at' => now(),
                'expires_at' => Carbon::now()->addMonths(3),
                'is_active' => true
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Free Shipping',
                'description' => 'Free shipping on orders over $75',
                'type' => 'fixed',
                'value' => 10,
                'minimum_amount' => 75,
                'usage_limit' => null,
                'starts_at' => now(),
                'expires_at' => Carbon::now()->addMonths(4),
                'is_active' => true
            ],
            [
                'code' => 'SPECIAL15',
                'name' => 'Special Edition Discount',
                'description' => '15% off limited edition items',
                'type' => 'percentage',
                'value' => 15,
                'minimum_amount' => 150,
                'usage_limit' => 200,
                'starts_at' => now(),
                'expires_at' => Carbon::now()->addMonths(2),
                'is_active' => true
            ],
            [
                'code' => 'TEAM25',
                'name' => 'Team Fan Discount',
                'description' => '25% off when buying 3 or more items from same team',
                'type' => 'percentage',
                'value' => 25,
                'minimum_amount' => 200,
                'usage_limit' => 300,
                'starts_at' => now(),
                'expires_at' => Carbon::now()->addMonths(5),
                'is_active' => true
            ],
            [
                'code' => 'FLASH50',
                'name' => 'Flash Sale 50% Off',
                'description' => 'Limited time flash sale - 50% off selected items',
                'type' => 'percentage',
                'value' => 50,
                'minimum_amount' => 100,
                'usage_limit' => 100,
                'starts_at' => now(),
                'expires_at' => Carbon::now()->addDays(7),
                'is_active' => true
            ],
            [
                'code' => 'LOYALTY30',
                'name' => 'Loyalty Reward',
                'description' => '30% off for loyal customers',
                'type' => 'percentage',
                'value' => 30,
                'minimum_amount' => 120,
                'usage_limit' => 150,
                'starts_at' => now(),
                'expires_at' => Carbon::now()->addMonths(6),
                'is_active' => true
            ],
            [
                'code' => 'NEWUSER25',
                'name' => 'New User Bonus',
                'description' => '25% off for new registered users',
                'type' => 'percentage',
                'value' => 25,
                'minimum_amount' => 80,
                'usage_limit' => 1000,
                'starts_at' => now(),
                'expires_at' => Carbon::now()->addYear(),
                'is_active' => true
            ]
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon);
        }
    }
}
