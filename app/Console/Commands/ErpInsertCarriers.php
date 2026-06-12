<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use Illuminate\Support\Str;

class ErpInsertCarriers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:insert-carriers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memasukkan data ekspedisi bawaan sistem';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $names = ['Diambil', 'J&T CARGO', 'SPX HEMAT', 'SPX STANDART', 'TERMURAH'];
        
        foreach($names as $name) {
            Carrier::updateOrCreate(
                ['name' => $name],
                ['id' => Str::uuid()->toString(), 'code' => strtoupper(Str::slug($name))]
            );
        }
        
        $this->info("Data Carriers berhasil dimasukkan!");
    }
}
