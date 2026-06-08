<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class IndonesiaRegionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('indonesia_regions')->truncate();

        $this->command->info('Fetching provinces...');
        $provincesResp = Http::timeout(30)->get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');
        if (!$provincesResp->successful()) {
            $this->command->error('Failed to fetch provinces!');
            return;
        }
        $provinces = $provincesResp->json();
        $provinceMap = collect($provinces)->keyBy('id');

        $this->command->info('Fetching regencies for ' . count($provinces) . ' provinces...');
        $regencyResponses = Http::pool(function (Pool $pool) use ($provinces) {
            foreach ($provinces as $p) {
                $pool->as($p['id'])->timeout(30)->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$p['id']}.json");
            }
        });

        $rows = [];
        $regencyMap = [];

        foreach ($regencyResponses as $provId => $resp) {
            if (!$resp->successful()) continue;
            $provName = ucwords(strtolower($provinceMap[$provId]['name']));
            foreach ($resp->json() as $reg) {
                $regName = ucwords(strtolower($reg['name']));
                $label   = $regName . ', ' . $provName;
                $rows[]  = [
                    'type'        => 'regency',
                    'label'       => $label,
                    'search_text' => static::buildSearchText($label),
                ];
                $regencyMap[$reg['id']] = [
                    'name'     => $regName,
                    'province' => $provName,
                ];
            }
        }

        $this->command->info('Fetching districts (kecamatan) for ' . count($regencyMap) . ' regencies...');
        $regencyIds = array_keys($regencyMap);
        $chunks     = array_chunk($regencyIds, 20);

        foreach ($chunks as $chunkIndex => $chunk) {
            $this->command->info("Processing chunk " . ($chunkIndex + 1) . "/" . count($chunks) . "...");
            $districtResponses = Http::pool(function (Pool $pool) use ($chunk) {
                foreach ($chunk as $regId) {
                    $pool->as($regId)->timeout(30)->get("https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$regId}.json");
                }
            });

            foreach ($districtResponses as $regId => $resp) {
                if (!$resp->successful()) continue;
                $regInfo = $regencyMap[$regId];
                foreach ($resp->json() as $district) {
                    $distName = ucwords(strtolower($district['name']));
                    $label    = $distName . ', ' . $regInfo['name'] . ', ' . $regInfo['province'];
                    $rows[]   = [
                        'type'        => 'district',
                        'label'       => $label,
                        'search_text' => static::buildSearchText($label),
                    ];
                }
            }

            // Batch insert every chunk
            $districtRows = array_filter($rows, fn($r) => $r['type'] === 'district');
            DB::table('indonesia_regions')->insert(array_values($districtRows));
            $rows = array_filter($rows, fn($r) => $r['type'] !== 'district');
        }

        // Insert all regencies
        DB::table('indonesia_regions')->insert(array_values($rows));

        $total = DB::table('indonesia_regions')->count();
        $this->command->info("Done! Total regions stored: {$total}");
    }

    /**
     * Strip geographic prefixes so keywords can match without them.
     * "Kecamatan Mojoroto, Kota Kediri, Jawa Timur" => "mojoroto kediri jawa timur"
     */
    protected static function buildSearchText(string $label): string
    {
        $prefixes = ['kecamatan', 'kelurahan', 'kabupaten', 'kota', 'provinsi', 'daerah istimewa'];
        $text = strtolower($label);
        foreach ($prefixes as $prefix) {
            $text = str_replace($prefix, '', $text);
        }
        // Remove commas and extra spaces, collapse whitespace
        $text = preg_replace('/[,]+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
