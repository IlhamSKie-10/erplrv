<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$shopeeId = DB::table('order_sources')->where('name', 'Shopee')->value('id');
$igId = DB::table('order_sources')->where('name', 'Instagram')->value('id');

if($igId && $shopeeId) {
    DB::table('orders')->where('order_source_id', $igId)->update(['order_source_id' => $shopeeId]);
    DB::table('order_sources')->where('id', $igId)->delete();
    echo "Deleted IG from DB\n";
} else {
    echo "Not found IG or Shopee\n";
}
