<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rizky = App\Models\Personnel::where('division', 'Design')->first();
if($rizky) {
    $rizky->update(['full_name' => 'Rizky', 'nickname' => 'Rizky']);
    if ($rizky->user_id) {
        App\Models\User::where('id', $rizky->user_id)->update(['full_name' => 'Rizky']);
    }
}

$hadiExists = App\Models\Personnel::where('full_name', 'Hadi')->first();
if(!$hadiExists) {
    $hadiUser = App\Models\User::where('email', 'hadi@auliart.com')->first();
    if ($hadiUser) {
        App\Models\Personnel::unguard();
        App\Models\Personnel::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $hadiUser->id,
            'full_name' => 'Hadi',
            'code' => 'HADI',
            'division' => 'Design',
            'is_active' => true
        ]);
        App\Models\Personnel::reguard();
    }
}

echo "Created Rizky & Hadi\n";
