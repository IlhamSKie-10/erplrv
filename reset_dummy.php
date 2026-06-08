<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

// Nonaktifkan pemeriksaan foreign key sementara
DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// Truncate tabel-tabel transaksi
DB::table('production_work_orders')->truncate();
DB::table('design_tasks')->truncate();
DB::table('orders')->truncate();

// Aktifkan kembali foreign key
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "Tabel transaksi berhasil dikosongkan.\n";

// Jalankan seeder
Artisan::call('db:seed', [
    '--class' => 'DesignQueueTestSeeder'
]);

echo Artisan::output();
