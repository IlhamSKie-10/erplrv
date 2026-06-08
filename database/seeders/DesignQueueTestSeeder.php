<?php

namespace Database\Seeders;

use App\Enums\{
    DesignTaskStatus, DesignStatus, JobComplexity,
    OrderStatus, PackingType, PaymentStatus, PaymentType,
    PriorityTier, ProgressStatus, DeadlineBand
};
use App\Models\{
    CustomerAccount, DesignTask, Order, OrderSource,
    Personnel, Product, ProductCategory, ProductModel,
    ProductType, ProductionQueue, ProductionStage, ProductionWorkOrder, Carrier, User
};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DesignQueueTestSeeder
 *
 * Membuat kombinasi pesanan untuk menguji semua kondisi di Antrian Desain:
 *   - Berbagai asal orderan (Shopee, WhatsApp/WA, TikTok, dll.)
 *   - Berbagai deadline (sudah lewat, hari ini, besok, minggu depan)
 *   - Berbagai status desain (PROCESS, ACC)
 *   - Berbagai cut_methods (CNC, Laser, Outsource, kombinasi, kosong)
 *   - Berbagai print_sticker (YES, NO, REQUIRED_LATER)
 *   - Dengan / tanpa design_acc_at (sesuai business logic CS)
 *   - Dengan / tanpa assigned designer
 *   - Yang sudah diteruskan ke produksi vs. belum
 *
 * Jalankan: php artisan db:seed --class=DesignQueueTestSeeder
 */
class DesignQueueTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Seeding design queue test data...');

        // ── Pastikan data master ada ───────────────────────────────────────
        $user      = $this->ensureUser();
        $personnel = $this->ensurePersonnel($user);
        $sources   = $this->ensureOrderSources();
        $accounts  = $this->ensureAccounts();
        $products  = $this->ensureProducts();
        $carrier   = $this->ensureCarrier();
        $queue     = $this->ensureProductionQueue();
        $stage     = $this->ensureProductionStage();

        // ── Matriks kombinasi untuk seeder ────────────────────────────────
        $scenarios = $this->buildScenarios($sources, $accounts, $products, $personnel);

        foreach ($scenarios as $idx => $s) {
            $seq   = str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
            $month = now()->month;
            $code  = "T{$seq}-{$month}";           // format mirip order baru tapi prefix T

            $order = Order::create([
                'id'              => Str::uuid()->toString(),
                'order_code'      => $code,
                'timestamp'       => $s['created_at'],
                'created_by_id'   => $user->id,
                'order_source_id' => $s['source']->id,
                'account_id'      => $s['account']->id,
                'product_type_id' => $s['product']->product_type_id,
                'product_id'      => $s['product']->id,
                'product_model_id'=> null,
                'city'            => $s['city'],
                'expedition_id'   => $carrier->id,
                'deadline_at'     => $s['deadline'],
                'complexity'      => JobComplexity::MEDIUM,
                'status'          => $s['order_status'],
                'payment_type'    => PaymentType::NON_COD,
                'total_order'     => $s['total'],
                'payment_status'  => PaymentStatus::DP,
                'amount_paid'     => $s['total'] / 2,
                'design_status'   => $s['design_status'],
                'packing_type'    => PackingType::BUBBLE,
                'product_sentence'=> $s['sentence'],
                'admin_notes'     => $s['notes'],
                'created_at'      => $s['created_at'],
                'updated_at'      => $s['created_at'],
            ]);

            // Buat DesignTask jika order bukan DRAFT
            if (!in_array($s['order_status'], [OrderStatus::DRAFT, OrderStatus::CONFIRMED])) {
                $task = DesignTask::create([
                    'id'                  => Str::uuid()->toString(),
                    'order_id'            => $order->id,
                    'assigned_designer_id'=> $s['designer']?->id,
                    'status'              => $s['task_status'],
                    'design_acc_at'       => $s['design_acc_at'],  // null = CS belum ACC
                    'print_sticker'       => $s['print_sticker'],   // plain string
                    'cut_methods'         => $s['cut_methods'],
                    'forwarded_at'        => $s['forwarded_at'],
                    'created_at'          => $s['created_at'],
                    'updated_at'          => $s['created_at'],
                ]);

                // Buat ProductionWorkOrder jika sudah diteruskan ke produksi
                if ($s['forwarded_at']) {
                    ProductionWorkOrder::create([
                        'id'                   => Str::uuid()->toString(),
                        'order_id'             => $order->id,
                        'queue_id'             => $queue->id,
                        'current_stage_id'     => $stage->id,
                        'assigned_personnel_id'=> $personnel->first()?->id,
                        'status'               => ProgressStatus::STARTED,
                        'deadline_band'        => DeadlineBand::SAFE,
                        'priority_tier'        => PriorityTier::TIER_4_SAFE,
                        'estimated_minutes'    => 120,
                        'remaining_minutes'    => 60,
                        'remaining_steps'      => 2,
                        'dynamic_score'        => rand(10, 100),
                        'created_at'           => $s['created_at'],
                        'updated_at'           => $s['created_at'],
                    ]);
                }
            }

            $this->command->line("  ✓ [{$code}] {$s['label']}");
        }

        $this->command->info('✅ Selesai! ' . count($scenarios) . ' kombinasi pesanan dibuat.');
    }

    // ─── Skenario Kombinasi ───────────────────────────────────────────────

    private function buildScenarios(
        \Illuminate\Support\Collection $sources,
        \Illuminate\Support\Collection $accounts,
        \Illuminate\Support\Collection $products,
        \Illuminate\Support\Collection $personnel,
    ): array {
        $shopee  = $sources->firstWhere('code', 'SHOPEE') ?? $sources->first();
        $wa      = $sources->firstWhere('code', 'WA')     ?? $sources->first();
        $tiktok  = $sources->firstWhere('code', 'TIKTOK') ?? $sources->first();
        $other   = $sources->firstWhere('code', 'IG')     ?? $sources->first();

        $acc1    = $accounts->first();
        $acc2    = $accounts->count() > 1 ? $accounts->get(1) : $acc1;
        $acc3    = $accounts->count() > 2 ? $accounts->get(2) : $acc1;

        $prod1   = $products->first();
        $prod2   = $products->count() > 1 ? $products->get(1) : $prod1;

        $des1    = $personnel->first();          // designer assigned
        $desNone = null;                         // belum ada designer

        $now    = now();
        $recent = $now->copy()->subHours(2);

        return [
            // ── 1. DRAFT (tidak muncul di antrian desain) ─────────────────────
            [
                'label'         => '[DRAFT] Shopee — belum submit, deadline aman',
                'source'        => $shopee,
                'account'       => $acc1,
                'product'       => $prod1,
                'designer'      => $desNone,
                'order_status'  => OrderStatus::DRAFT,
                'design_status' => DesignStatus::PROCESS,
                'task_status'   => DesignTaskStatus::PROCESS,
                'design_acc_at' => null,
                'print_sticker' => 'NO',
                'cut_methods'   => [],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->addDays(7),
                'created_at'    => $now->copy()->subDays(1),
                'total'         => 500000,
                'city'          => 'Jakarta Selatan',
                'sentence'      => 'Neon Box 60x40cm - Teks: AuliArt - Kotak / 1 Sisi / Dengan Lampu',
                'notes'         => null,
            ],

            // ── 2. CONFIRMED (tidak muncul di antrian desain) ─────────────────
            [
                'label'         => '[CONFIRMED] WA — sudah dikonfirmasi CS, belum ke desain',
                'source'        => $wa,
                'account'       => $acc2,
                'product'       => $prod1,
                'designer'      => $desNone,
                'order_status'  => OrderStatus::CONFIRMED,
                'design_status' => DesignStatus::PROCESS,
                'task_status'   => DesignTaskStatus::PROCESS,
                'design_acc_at' => null,
                'print_sticker' => 'YES',
                'cut_methods'   => ['CNC'],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->addDays(3),
                'created_at'    => $now->copy()->subHours(5),
                'total'         => 750000,
                'city'          => 'Bandung',
                'sentence'      => 'Letter 3D 30cm - Logo Toko Berkah',
                'notes'         => "Produksi: Gunakan bahan akrilik 5mm\nLink Ref: https://example.com/ref1",
            ],

            // ── 3-A. ANTRIAN DESAIN: CS belum ACC, deadline AMAN ─────────────
            [
                'label'         => '[IN PROGRESS] Shopee — CS belum ACC, deadline 7 hari (aman)',
                'source'        => $shopee,
                'account'       => $acc1,
                'product'       => $prod1,
                'designer'      => $des1,
                'order_status'  => OrderStatus::DESIGN_IN_PROGRESS,
                'design_status' => DesignStatus::PROCESS,
                'task_status'   => DesignTaskStatus::PROCESS,
                'design_acc_at' => null,   // CS belum set ACC → designer melihat "—"
                'print_sticker' => 'YES',
                'cut_methods'   => ['CNC'],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->addDays(7),
                'created_at'    => $now->copy()->subDays(1),
                'total'         => 600000,
                'city'          => 'Surabaya',
                'sentence'      => 'Neon Box 80x60cm - Teks: Bakso Pak Eko - Bulat / 2 Sisi / Dengan Lampu',
                'notes'         => "Produksi: Double-sided\nKhusus: Warna biru navy",
            ],

            // ── 3-B. ANTRIAN DESAIN: CS belum ACC, deadline MENDEKAT ─────────
            [
                'label'         => '[IN PROGRESS] TikTok — CS belum ACC, deadline 2 hari (warning)',
                'source'        => $tiktok,
                'account'       => $acc2,
                'product'       => $prod2,
                'designer'      => $des1,
                'order_status'  => OrderStatus::DESIGN_IN_PROGRESS,
                'design_status' => DesignStatus::PROCESS,
                'task_status'   => DesignTaskStatus::PROCESS,
                'design_acc_at' => null,
                'print_sticker' => 'NO',
                'cut_methods'   => ['LASER'],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->addDays(2),
                'created_at'    => $now->copy()->subDays(3),
                'total'         => 450000,
                'city'          => 'Yogyakarta',
                'sentence'      => 'Logo Akrilik 25cm - Logo Kafe Jomblo',
                'notes'         => 'Khusus: Warna putih susu dengan backlit merah',
            ],

            // ── 3-C. ANTRIAN DESAIN: CS belum ACC, deadline HARI INI ─────────
            [
                'label'         => '[IN PROGRESS] WA — CS belum ACC, deadline HARI INI',
                'source'        => $wa,
                'account'       => $acc3,
                'product'       => $prod1,
                'designer'      => $desNone,   // belum ada designer assigned
                'order_status'  => OrderStatus::DESIGN_IN_PROGRESS,
                'design_status' => DesignStatus::PROCESS,
                'task_status'   => DesignTaskStatus::PROCESS,
                'design_acc_at' => null,
                'print_sticker' => 'NO',
                'cut_methods'   => [],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->startOfDay()->addHours(17),
                'created_at'    => $now->copy()->subDays(2),
                'total'         => 350000,
                'city'          => 'Semarang',
                'sentence'      => 'Neonflex 2m - Teks: OPEN 24 JAM',
                'notes'         => null,
            ],

            // ── 3-D. ANTRIAN DESAIN: CS belum ACC, deadline LEWAT ────────────
            [
                'label'         => '[IN PROGRESS] Shopee — CS belum ACC, deadline LEWAT 3 hari',
                'source'        => $shopee,
                'account'       => $acc1,
                'product'       => $prod2,
                'designer'      => $des1,
                'order_status'  => OrderStatus::DESIGN_IN_PROGRESS,
                'design_status' => DesignStatus::PROCESS,
                'task_status'   => DesignTaskStatus::PROCESS,
                'design_acc_at' => null,
                'print_sticker' => 'YES',
                'cut_methods'   => ['CNC', 'LASER'],    // kombinasi CNC + Laser
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->subDays(3),
                'created_at'    => $now->copy()->subDays(6),
                'total'         => 980000,
                'city'          => 'Medan',
                'sentence'      => 'Letter 3D 50cm - BAKSO CERIA JAYA',
                'notes'         => "Produksi: Huruf timbul 10cm\nLink Ref: https://example.com/ref2\nKhusus: URGENT!",
            ],

            // ── 4-A. CS sudah ACC, designer belum konfirmasi ─────────────────
            [
                'label'         => '[IN PROGRESS] TikTok — CS sudah ACC, tgl terisi otomatis, belum forward',
                'source'        => $tiktok,
                'account'       => $acc2,
                'product'       => $prod1,
                'designer'      => $des1,
                'order_status'  => OrderStatus::DESIGN_IN_PROGRESS,
                'design_status' => DesignStatus::ACC,
                'task_status'   => DesignTaskStatus::PROCESS,   // task masih PROCESS
                'design_acc_at' => $now->copy()->subDays(1)->format('Y-m-d H:i:s'), // CS sudah isi
                'print_sticker' => 'NO',
                'cut_methods'   => ['CNC'],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->addDays(4),
                'created_at'    => $now->copy()->subDays(3),
                'total'         => 1200000,
                'city'          => 'Bali',
                'sentence'      => 'Logo Akrilik 60cm - Resort Sunrise',
                'notes'         => 'Khusus: Material akrilik frost',
            ],

            // ── 4-B. DESIGN_APPROVED: task ACC, belum diteruskan ke produksi ─
            [
                'label'         => '[APPROVED] Shopee — task ACC (designer konfirmasi), siap forward',
                'source'        => $shopee,
                'account'       => $acc3,
                'product'       => $prod1,
                'designer'      => $des1,
                'order_status'  => OrderStatus::DESIGN_APPROVED,
                'design_status' => DesignStatus::ACC,
                'task_status'   => DesignTaskStatus::ACC,
                'design_acc_at' => $now->copy()->subHours(4)->format('Y-m-d H:i:s'),
                'print_sticker' => 'YES',
                'cut_methods'   => ['CNC'],
                'forwarded_at'  => null,                        // belum diteruskan
                'deadline'      => $now->copy()->addDays(5),
                'created_at'    => $now->copy()->subDays(2),
                'total'         => 850000,
                'city'          => 'Jakarta Pusat',
                'sentence'      => 'Neon Box 100x80cm - SALON CANTIKA - Kotak / 1 Sisi / Tanpa Lampu',
                'notes'         => "Produksi: Bahan Acrylic 8mm\nKhusus: Frame stainless",
            ],

            // ── 4-C. DESIGN_APPROVED: ACC + CNC + Laser ──────────────────────
            [
                'label'         => '[APPROVED] WA — ACC + proses CNC & Laser, belum forward',
                'source'        => $wa,
                'account'       => $acc1,
                'product'       => $prod2,
                'designer'      => $des1,
                'order_status'  => OrderStatus::DESIGN_APPROVED,
                'design_status' => DesignStatus::ACC,
                'task_status'   => DesignTaskStatus::ACC,
                'design_acc_at' => $now->copy()->subHours(6)->format('Y-m-d H:i:s'),
                'print_sticker' => 'YES',
                'cut_methods'   => ['CNC', 'LASER'],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->addDays(1),    // mepet!
                'created_at'    => $now->copy()->subDays(4),
                'total'         => 1500000,
                'city'          => 'Bekasi',
                'sentence'      => 'Letter 3D 80cm + Neon Flex - HOTEL BINTANG 5',
                'notes'         => "Produksi: Stainless 2mm + Acrylic LED\nKhusus: Pasang di dinding marmer",
            ],

            // ── 5-A. IN_PRODUCTION: sudah diteruskan ke produksi ─────────────
            [
                'label'         => '[IN PROD] Shopee — ACC + sudah diteruskan ke produksi',
                'source'        => $shopee,
                'account'       => $acc2,
                'product'       => $prod1,
                'designer'      => $des1,
                'order_status'  => OrderStatus::IN_PRODUCTION,
                'design_status' => DesignStatus::ACC,
                'task_status'   => DesignTaskStatus::ACC,
                'design_acc_at' => $now->copy()->subDays(2)->format('Y-m-d H:i:s'),
                'print_sticker' => 'NO',
                'cut_methods'   => ['CNC'],
                'forwarded_at'  => $now->copy()->subDays(2)->addHours(1),
                'deadline'      => $now->copy()->addDays(3),
                'created_at'    => $now->copy()->subDays(5),
                'total'         => 700000,
                'city'          => 'Depok',
                'sentence'      => 'Neon Box 70x50cm - APOTEK SEHAT - Kotak / 1 Sisi / Dengan Lampu',
                'notes'         => null,
            ],

            // ── 5-B. IN_PRODUCTION: semua cut methods, outsource ─────────────
            [
                'label'         => '[IN PROD] TikTok — ACC + CNC + Laser + Outsource, sudah di produksi',
                'source'        => $tiktok,
                'account'       => $acc3,
                'product'       => $prod2,
                'designer'      => $des1,
                'order_status'  => OrderStatus::IN_PRODUCTION,
                'design_status' => DesignStatus::ACC,
                'task_status'   => DesignTaskStatus::ACC,
                'design_acc_at' => $now->copy()->subDays(3)->format('Y-m-d H:i:s'),
                'print_sticker' => 'YES',
                'cut_methods'   => ['CNC', 'LASER'],
                'forwarded_at'  => $now->copy()->subDays(3)->addHours(2),
                'deadline'      => $now->copy()->addDays(2),
                'created_at'    => $now->copy()->subDays(7),
                'total'         => 2000000,
                'city'          => 'Malang',
                'sentence'      => 'Papan Reklame Custom - UNIVERSITAS NEGERI MALANG',
                'notes'         => "Produksi: Full custom\nLink Ref: https://example.com/ref3",
            ],

            // ── 6. Berbagai asal orderan (untuk test badge warna) ────────────
            [
                'label'         => '[IN PROGRESS] Shopee — sumber lain, PROCESS',
                'source'        => $other,
                'account'       => $acc1,
                'product'       => $prod1,
                'designer'      => $des1,
                'order_status'  => OrderStatus::DESIGN_IN_PROGRESS,
                'design_status' => DesignStatus::PROCESS,
                'task_status'   => DesignTaskStatus::PROCESS,
                'design_acc_at' => null,
                'print_sticker' => 'NO',
                'cut_methods'   => ['LASER'],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->addDays(10),
                'created_at'    => $recent,
                'total'         => 400000,
                'city'          => 'Palembang',
                'sentence'      => 'Neonflex 1.5m - Teks: WARUNG MAS BUDI',
                'notes'         => null,
            ],

            // ── 7. Print sticker REQUIRED_LATER + Outsource + deadline lewat ─
            [
                'label'         => '[APPROVED] WA — CNC, Print Tidak, deadline LEWAT 1 hari',
                'source'        => $wa,
                'account'       => $acc2,
                'product'       => $prod2,
                'designer'      => $desNone,
                'order_status'  => OrderStatus::DESIGN_APPROVED,
                'design_status' => DesignStatus::ACC,
                'task_status'   => DesignTaskStatus::ACC,
                'design_acc_at' => $now->copy()->subDays(1)->subHours(3)->format('Y-m-d H:i:s'),
                'print_sticker' => 'NO',
                'cut_methods'   => ['CNC'],
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->subDays(1),
                'created_at'    => $now->copy()->subDays(4),
                'total'         => 550000,
                'city'          => 'Solo',
                'sentence'      => 'Logo Kayu 40cm - TOKO BATIK LARASATI',
                'notes'         => 'Khusus: Kayu jati, finishing natural',
            ],

            // ── 8. cut_methods kosong, print sticker YES ─────────────────────
            [
                'label'         => '[IN PROGRESS] Shopee — cut_methods kosong, print YES, deadline 1 hari',
                'source'        => $shopee,
                'account'       => $acc3,
                'product'       => $prod1,
                'designer'      => $des1,
                'order_status'  => OrderStatus::DESIGN_IN_PROGRESS,
                'design_status' => DesignStatus::PROCESS,
                'task_status'   => DesignTaskStatus::PROCESS,
                'design_acc_at' => null,
                'print_sticker' => 'YES',
                'cut_methods'   => [],   // belum diset
                'forwarded_at'  => null,
                'deadline'      => $now->copy()->addDays(1),
                'created_at'    => $now->copy()->subDays(1),
                'total'         => 320000,
                'city'          => 'Tangerang',
                'sentence'      => 'Token Kayu 10cm - Pernikahan Budi & Sari',
                'notes'         => 'Khusus: Ukir nama + tanggal pernikahan',
            ],
        ];
    }

    // ─── Helper: pastikan data master ada ────────────────────────────────

    private function ensureUser(): User
    {
        return User::first() ?? User::create([
            'id'       => Str::uuid()->toString(),
            'name'     => 'Admin Test',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    private function ensurePersonnel(User $user): \Illuminate\Support\Collection
    {
        if (Personnel::count() === 0) {
            Personnel::create([
                'id'        => Str::uuid()->toString(),
                'user_id'   => $user->id,
                'code'      => 'DSG-001',
                'full_name' => 'Budi Designer',
                'division'  => 'Design',
                'is_active' => true,
            ]);
        }
        return Personnel::all();
    }

    private function ensureOrderSources(): \Illuminate\Support\Collection
    {
        $defs = [
            ['code' => 'SHOPEE',   'name' => 'Shopee'],
            ['code' => 'WA',       'name' => "What's Apps"],
            ['code' => 'TIKTOK',   'name' => 'TikTok'],
        ];
        foreach ($defs as $d) {
            OrderSource::firstOrCreate(
                ['code' => $d['code']],
                ['id' => Str::uuid()->toString(), 'name' => $d['name'], 'is_active' => true]
            );
        }
        return OrderSource::all();
    }

    private function ensureAccounts(): \Illuminate\Support\Collection
    {
        $names = ['Toko Berkah', 'Kafe Jomblo', 'Salon Cantika'];
        foreach ($names as $i => $name) {
            CustomerAccount::firstOrCreate(
                ['name' => $name],
                ['id' => Str::uuid()->toString(), 'code' => 'ACC-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT)]
            );
        }
        return CustomerAccount::all();
    }

    private function ensureProducts(): \Illuminate\Support\Collection
    {
        $cat = ProductCategory::firstOrCreate(
            ['code' => 'general'],
            ['id' => Str::uuid()->toString(), 'name' => 'General']
        );

        $types = [
            'ADV1' => 'Advertising 1',
            'ADV2' => 'Advertising 2',
        ];

        $productDefs = [
            ['code' => 'neon-box',     'name' => 'Neon Box',     'type' => 'ADV1', 'queue' => 'ADVERTISING_1'],
            ['code' => 'letter-3d',    'name' => 'Letter 3D',    'type' => 'ADV2', 'queue' => 'ADVERTISING_2'],
            ['code' => 'logo-akrilik', 'name' => 'Logo Akrilik', 'type' => 'ADV2', 'queue' => 'ADVERTISING_2'],
            ['code' => 'neonflex',     'name' => 'Neonflex',     'type' => 'ADV1', 'queue' => 'ADVERTISING_1'],
        ];

        foreach ($types as $code => $name) {
            ProductType::firstOrCreate(
                ['code' => $code],
                ['id' => Str::uuid()->toString(), 'name' => $name]
            );
        }

        foreach ($productDefs as $pd) {
            $pt = ProductType::where('code', $pd['type'])->first();
            Product::firstOrCreate(
                ['code' => $pd['code']],
                [
                    'id'              => Str::uuid()->toString(),
                    'name'            => $pd['name'],
                    'product_type_id' => $pt->id,
                    'category_id'     => $cat->id,
                    'production_queue'=> $pd['queue'],
                    'is_active'       => true,
                ]
            );
        }

        return Product::with('productType')->get();
    }

    private function ensureCarrier(): Carrier
    {
        return Carrier::firstOrCreate(
            ['code' => 'JNT'],
            ['id' => Str::uuid()->toString(), 'name' => 'J&T CARGO']
        );
    }

    private function ensureProductionQueue(): ProductionQueue
    {
        return ProductionQueue::firstOrCreate(
            ['code' => 'ADVERTISING_1'],
            ['id' => Str::uuid()->toString(), 'name' => 'Advertising 1 Queue']
        );
    }

    private function ensureProductionStage(): ProductionStage
    {
        return ProductionStage::firstOrCreate(
            ['code' => 'LASER'],
            ['id' => Str::uuid()->toString(), 'name' => 'Laser', 'sort_order' => 1]
        );
    }
}
