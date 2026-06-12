<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ErpDeleteIg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:delete-ig';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus order source Instagram dan memindahkannya ke Shopee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shopeeId = DB::table('order_sources')->where('name', 'Shopee')->value('id');
        $igId = DB::table('order_sources')->where('name', 'Instagram')->value('id');

        if($igId && $shopeeId) {
            DB::table('orders')->where('order_source_id', $igId)->update(['order_source_id' => $shopeeId]);
            DB::table('order_sources')->where('id', $igId)->delete();
            $this->info("Order Source 'Instagram' berhasil dihapus dan order dipindahkan ke 'Shopee'.");
        } else {
            $this->warn("Order Source 'Instagram' atau 'Shopee' tidak ditemukan di database.");
        }
    }
}
