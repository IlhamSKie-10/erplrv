<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class ErpResetDummy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:reset-dummy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengosongkan tabel transaksi dan menjalankan seeder antrean desain dummy (Development Only)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai reset data dummy...');

        if (!app()->environment('local')) {
            $this->warn('Perintah ini hanya boleh dijalankan di environment local.');
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan di environment non-local?')) {
                return;
            }
        }

        // Nonaktifkan pemeriksaan foreign key sementara
        DB::statement('PRAGMA foreign_keys=OFF;');

        // Truncate tabel-tabel transaksi
        DB::table('production_work_orders')->truncate();
        DB::table('design_tasks')->truncate();
        DB::table('orders')->truncate();

        // Aktifkan kembali foreign key
        DB::statement('PRAGMA foreign_keys=ON;');

        $this->info('Tabel transaksi berhasil dikosongkan.');

        // Jalankan seeder
        Artisan::call('db:seed', [
            '--class' => 'DesignQueueTestSeeder'
        ]);

        $this->line(Artisan::output());
        $this->info('Reset data dummy selesai.');
    }
}
