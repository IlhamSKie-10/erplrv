<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Carrier;
use Illuminate\Support\Str;

$names = ['Diambil', 'J&T CARGO', 'SPX HEMAT', 'SPX STANDART', 'TERMURAH'];
foreach($names as $name) {
    Carrier::updateOrCreate(
        ['name' => $name],
        ['id' => Str::uuid()->toString(), 'code' => strtoupper(Str::slug($name))]
    );
}
echo "Carriers inserted successfully!\n";
