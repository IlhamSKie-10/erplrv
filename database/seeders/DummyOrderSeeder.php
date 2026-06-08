<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummyOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first();
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => bcrypt('password'),
            ]);
        }

        $orderSources = \App\Models\OrderSource::all();
        $accounts = \App\Models\CustomerAccount::all();
        $products = \App\Models\Product::with('productType')->get();
        $productModels = \App\Models\ProductModel::all();
        $carriers = \App\Models\Carrier::all();

        // Ensure we have some base data
        if ($orderSources->isEmpty()) {
            $orderSources->push(\App\Models\OrderSource::create(['id' => \Illuminate\Support\Str::uuid()->toString(), 'code' => 'WA', 'name' => 'What\'s apps']));
        }
        if ($accounts->isEmpty()) {
            $accounts->push(\App\Models\CustomerAccount::create(['id' => \Illuminate\Support\Str::uuid()->toString(), 'name' => 'Dummy Account', 'code' => 'ACC-DUMMY']));
        }
        if ($products->isEmpty()) {
            $cat = \App\Models\ProductCategory::firstOrCreate(
                ['name' => 'General'],
                ['id' => \Illuminate\Support\Str::uuid()->toString(), 'code' => 'CAT1']
            );
            $pt = \App\Models\ProductType::where('name', 'Advertising 1')->first();
            if (!$pt) {
                $pt = \App\Models\ProductType::create(['id' => \Illuminate\Support\Str::uuid()->toString(), 'code' => 'ADV1', 'name' => 'Advertising 1']);
            }
            $products->push(\App\Models\Product::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(), 
                'code' => 'PRD-1', 
                'name' => 'Neon Box', 
                'product_type_id' => $pt->id, 
                'category_id' => $cat->id,
                'is_active' => true
            ]));
        }
        if ($productModels->isEmpty()) {
            $pt = \App\Models\ProductType::first();
            $productModels->push(\App\Models\ProductModel::create(['id' => \Illuminate\Support\Str::uuid()->toString(), 'code' => 'MOD-1', 'name' => 'Model 1', 'product_type_id' => $pt->id]));
        }
        if ($carriers->isEmpty()) {
            $carriers->push(\App\Models\Carrier::create(['id' => \Illuminate\Support\Str::uuid()->toString(), 'code' => 'JNT', 'name' => 'J&T CARGO']));
        }

        $statuses = [
            \App\Enums\OrderStatus::DRAFT,
            \App\Enums\OrderStatus::CONFIRMED,
            \App\Enums\OrderStatus::DESIGN_IN_PROGRESS,
            \App\Enums\OrderStatus::DESIGN_APPROVED,
            \App\Enums\OrderStatus::IN_PRODUCTION,
            \App\Enums\OrderStatus::READY_TO_SHIP,
        ];

        $personnels = \App\Models\Personnel::all();
        if ($personnels->isEmpty()) {
            $personnels->push(\App\Models\Personnel::create(['id' => \Illuminate\Support\Str::uuid()->toString(), 'user_id' => $user->id, 'full_name' => 'Designer 1', 'code' => 'DSG-1', 'division' => 'DESIGNER']));
        }
        
        $queues = \App\Models\ProductionQueue::all();
        if ($queues->isEmpty()) {
            $queues->push(\App\Models\ProductionQueue::create(['id' => \Illuminate\Support\Str::uuid()->toString(), 'name' => 'Advertising 1 Queue', 'code' => 'ADVERTISING_1']));
        }

        $stages = \App\Models\ProductionStage::all();
        if ($stages->isEmpty()) {
            $stages->push(\App\Models\ProductionStage::create(['id' => \Illuminate\Support\Str::uuid()->toString(), 'name' => 'Laser Stage', 'code' => 'LASER', 'sort_order' => 1]));
        }

        for ($i = 1; $i <= 50; $i++) {
            $product = $products->random();
            $status = $statuses[array_rand($statuses)];
            $createdAt = now()->subDays(rand(0, 14)); 
            $deadline = now()->addDays(rand(-5, 5)); 
            
            $order = \App\Models\Order::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'order_code' => 'DUMMY/' . now()->format('ym') . '/' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT) . '-' . $i,
                'timestamp' => $createdAt,
                'created_by_id' => $user->id,
                'order_source_id' => $orderSources->random()->id,
                'account_id' => $accounts->random()->id,
                'product_type_id' => $product->product_type_id,
                'product_id' => $product->id,
                'product_model_id' => $productModels->random()->id,
                'city' => 'Jakarta ' . rand(1, 5),
                'expedition_id' => $carriers->random()->id,
                'deadline_at' => $deadline,
                'complexity' => \App\Enums\JobComplexity::MEDIUM,
                'status' => $status,
                'payment_type' => \App\Enums\PaymentType::NON_COD,
                'total_order' => rand(10, 50) * 100000,
                'payment_status' => \App\Enums\PaymentStatus::DP,
                'amount_paid' => rand(5, 10) * 100000,
                'design_status' => \App\Enums\DesignStatus::PROCESS,
                'packing_type' => \App\Enums\PackingType::BUBBLE,
                'product_sentence' => 'Dummy Product ' . $i,
                'admin_notes' => 'Generated by seeder',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if (in_array($status, [\App\Enums\OrderStatus::DESIGN_IN_PROGRESS, \App\Enums\OrderStatus::DESIGN_APPROVED, \App\Enums\OrderStatus::IN_PRODUCTION, \App\Enums\OrderStatus::READY_TO_SHIP])) {
                \App\Models\DesignTask::create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'order_id' => $order->id,
                    'assigned_designer_id' => $personnels->random()->id,
                    'status' => ($status === \App\Enums\OrderStatus::DESIGN_IN_PROGRESS) ? \App\Enums\DesignTaskStatus::PROCESS : \App\Enums\DesignTaskStatus::ACC,
                    'print_sticker' => \App\Enums\PrintStickerOption::NO,
                    'cut_methods' => [],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            if (in_array($status, [\App\Enums\OrderStatus::IN_PRODUCTION, \App\Enums\OrderStatus::READY_TO_SHIP])) {
                \App\Models\ProductionWorkOrder::create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'order_id' => $order->id,
                    'queue_id' => $queues->random()->id,
                    'current_stage_id' => $stages->random()->id,
                    'assigned_personnel_id' => $personnels->random()->id,
                    'status' => ($status === \App\Enums\OrderStatus::IN_PRODUCTION) ? \App\Enums\ProgressStatus::STARTED : \App\Enums\ProgressStatus::COMPLETED,
                    'deadline_band' => \App\Enums\DeadlineBand::SAFE,
                    'priority_tier' => \App\Enums\PriorityTier::TIER_4_SAFE,
                    'estimated_minutes' => 60,
                    'remaining_minutes' => 60,
                    'remaining_steps' => 1,
                    'dynamic_score' => rand(10, 100),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
