<?php

use App\Models\ProductionStage;
use Illuminate\Support\Str;

$stages = ['LAS', 'LASER', 'RANGKAI', 'STRC UV', 'CD', 'FINISHING', 'BUBBLE', 'KIRIM', 'CNC', 'CAT'];

foreach ($stages as $index => $stageName) {
    ProductionStage::firstOrCreate(
        ['name' => $stageName],
        [
            'code' => strtoupper(Str::slug($stageName)),
            'sort_order' => $index + 10,
            'default_estimated_minutes' => 60,
            'requires_previous_stage' => false,
        ]
    );
}

echo "Stages seeded successfully.\n";
