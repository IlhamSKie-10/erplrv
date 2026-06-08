<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Region\Province;
use App\Models\Region\Regency;
use App\Models\Region\District;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding EMSIFA Region Data (Provinces, Regencies, Districts)...');

        $baseUrl = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        // 1. Fetch & Seed Provinces
        $this->command->info('Fetching Provinces...');
        $provinces = Http::get("{$baseUrl}/provinces.json")->json();
        if (is_array($provinces)) {
            $provinceData = array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name']], $provinces);
            DB::table('provinces')->upsert($provinceData, ['id'], ['name']);
            $this->command->info(count($provinceData) . ' Provinces inserted.');
        }

        // 2. Fetch & Seed Regencies
        $this->command->info('Fetching Regencies...');
        $regencyData = [];
        foreach (Province::pluck('id') as $provinceId) {
            $regencies = Http::get("{$baseUrl}/regencies/{$provinceId}.json")->json();
            if (is_array($regencies)) {
                foreach ($regencies as $r) {
                    $regencyData[] = [
                        'id' => $r['id'],
                        'province_id' => $r['province_id'],
                        'name' => $r['name']
                    ];
                }
            }
        }
        
        $chunks = array_chunk($regencyData, 500);
        foreach ($chunks as $chunk) {
            DB::table('regencies')->upsert($chunk, ['id'], ['province_id', 'name']);
        }
        $this->command->info(count($regencyData) . ' Regencies inserted.');

        // 3. Fetch & Seed Districts
        $this->command->info('Fetching Districts... This may take a moment.');
        $districtData = [];
        $regencyIds = Regency::pluck('id');
        
        // Using a progress bar for districts since there are a lot (~7000)
        $bar = $this->command->getOutput()->createProgressBar(count($regencyIds));
        $bar->start();

        foreach ($regencyIds as $regencyId) {
            $districts = Http::get("{$baseUrl}/districts/{$regencyId}.json")->json();
            if (is_array($districts)) {
                foreach ($districts as $d) {
                    $districtData[] = [
                        'id' => $d['id'],
                        'regency_id' => $d['regency_id'],
                        'name' => $d['name']
                    ];
                }
            }
            $bar->advance();
        }
        $bar->finish();
        $this->command->newLine();

        $chunks = array_chunk($districtData, 500);
        foreach ($chunks as $chunk) {
            DB::table('districts')->upsert($chunk, ['id'], ['regency_id', 'name']);
        }
        $this->command->info(count($districtData) . ' Districts inserted.');
    }
}
