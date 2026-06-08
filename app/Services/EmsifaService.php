<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EmsifaService
{
    /**
     * Search regions (kecamatan & kabupaten/kota) from local database.
     *
     * Robust against CS shorthand input like "mojoroto kediri":
     * - Case insensitive
     * - Splits keywords and requires ALL to match (AND logic)
     * - Searches on search_text (stripped of prefixes like Kecamatan, Kota, Kabupaten)
     *
     * Result format: "Kecamatan Mojoroto, Kota Kediri, Jawa Timur"
     */
    public static function search(string $query): array
    {
        $query = trim(strtolower($query));
        if (strlen($query) < 2) {
            return [];
        }

        // Split into individual keywords, ignore single-char tokens
        $keywords = array_filter(
            preg_split('/[\s,]+/', $query),
            fn($k) => strlen($k) >= 2
        );

        if (empty($keywords)) {
            return [];
        }

        $builder = DB::table('indonesia_regions');

        // Each keyword must appear in search_text (AND logic)
        foreach ($keywords as $keyword) {
            $builder->where('search_text', 'like', '%' . $keyword . '%');
        }

        $results = $builder
            ->orderByRaw("CASE WHEN type = 'regency' THEN 0 ELSE 1 END")
            ->limit(30)
            ->pluck('label', 'label')
            ->toArray();

        return $results;
    }
}
