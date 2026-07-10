<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $basic = Plan::updateOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'Basic',
                'description' => 'Perfect for small businesses getting started.',
                'price' => 500000,
                'currency' => 'NGN',
                'interval' => 'monthly',
                'is_active' => true,
            ]
        );

        $basic->features()->delete();

        $basic->features()->createMany([
            ['name' => 'Store Front', 'slug' => 'store-front', 'value' => 'true'],
            ['name' => 'Products Limit', 'slug' => 'products-limit', 'value' => '50'],
            ['name' => 'Support', 'slug' => 'support', 'value' => 'Email'],
        ]);

        $pro = Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'For growing businesses with more needs.',
                'price' => 1500000,
                'currency' => 'NGN',
                'interval' => 'monthly',
                'is_active' => true,
            ]
        );

        $pro->features()->delete();

        $pro->features()->createMany([
            ['name' => 'Store Front', 'slug' => 'store-front', 'value' => 'true'],
            ['name' => 'Products Limit', 'slug' => 'products-limit', 'value' => 'Unlimited'],
            ['name' => 'Support', 'slug' => 'support', 'value' => 'Priority'],
            ['name' => 'Custom Domain', 'slug' => 'custom-domain', 'value' => 'true'],
        ]);

        $enterprise = Plan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'Advanced features for large scale operations.',
                'price' => 5000000,
                'currency' => 'NGN',
                'interval' => 'monthly',
                'is_active' => true,
            ]
        );

        $enterprise->features()->delete();

        $enterprise->features()->createMany([
            ['name' => 'Store Front', 'slug' => 'store-front', 'value' => 'true'],
            ['name' => 'Products Limit', 'slug' => 'products-limit', 'value' => 'Unlimited'],
            ['name' => 'Support', 'slug' => 'support', 'value' => '24/7 Phone'],
            ['name' => 'Custom Domain', 'slug' => 'custom-domain', 'value' => 'true'],
            ['name' => 'Dedicated Account Manager', 'slug' => 'account-manager', 'value' => 'true'],
        ]);
    }
}
