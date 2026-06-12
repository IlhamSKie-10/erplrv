<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$stages = ['LAS' => 'LAS', 'LASER' => 'LASER', 'RANGKAI' => 'RANGKAI', 'STRC UV' => 'STCR_UV', 'CD' => 'CD', 'FINISHING' => 'FINISHING', 'BUBBLE' => 'BUBBLE', 'Kirim' => 'DATE'];
$i = 0;
foreach($stages as $name => $code) {
    \App\Models\ProductionStage::updateOrCreate(
        ['code' => $code],
        ['name' => $name, 'sort_order' => ++$i, 'default_estimated_minutes' => 60]
    );
}
echo 'Seeded!';
