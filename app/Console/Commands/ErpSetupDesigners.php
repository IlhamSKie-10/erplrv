<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Str;

class ErpSetupDesigners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:setup-designers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyiapkan data personnel desainer bawaan sistem';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rizky = Personnel::where('division', 'Design')->first();
        if($rizky) {
            $rizky->update(['full_name' => 'Rizky', 'nickname' => 'Rizky']);
            if ($rizky->user_id) {
                User::where('id', $rizky->user_id)->update(['full_name' => 'Rizky']);
            }
        }

        $hadiExists = Personnel::where('full_name', 'Hadi')->first();
        if(!$hadiExists) {
            // Anonymized email for GitHub portfolio safety
            $hadiUser = User::where('email', 'designer@example.com')->first();
            if ($hadiUser) {
                Personnel::unguard();
                Personnel::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $hadiUser->id,
                    'full_name' => 'Hadi',
                    'code' => 'HADI',
                    'division' => 'Design',
                    'is_active' => true
                ]);
                Personnel::reguard();
            } else {
                $this->warn('User designer@example.com tidak ditemukan. Tidak dapat membuat relasi Personnel.');
            }
        }

        $this->info("Setup personnel desainer (Rizky & Hadi) berhasil.");
    }
}
