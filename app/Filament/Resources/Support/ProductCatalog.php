<?php

namespace App\Filament\Resources\Support;

/**
 * Product catalog config with feature flags.
 * Ported from erplrv/src/lib/product-catalog.ts
 * 
 * Keyed by product `name` (case-insensitive) from the DB.
 * Each entry defines which optional fields are applicable.
 */
class ProductCatalog
{
    /**
     * Returns feature flags for a given product name.
     * Returns null if product is not in catalog (show all fields as fallback).
     */
    public static function getFlags(string $productName): ?array
    {
        $name = strtolower(trim($productName));

        return match (true) {
            str_contains($name, 'neon box') => [
                'has_size'     => true,
                'has_text'     => true,
                'has_color'    => true,
                'has_variant'  => true,  // 1 sisi / 2 sisi
                'has_shape'    => true,  // Bulat / Kotak
                'has_bracket'  => true,  // Bawah / Samping
                'has_lamp'     => true,  // Dengan Lampu / Tanpa Lampu
            ],
            str_contains($name, 'letter 3d') || str_contains($name, 'huruf timbul') => [
                'has_size'     => true,
                'has_text'     => true,
                'has_color'    => true,
                'has_variant'  => false,
                'has_shape'    => false,
                'has_bracket'  => false,
                'has_lamp'     => false,
            ],
            str_contains($name, 'logo akrilik') => [
                'has_size'     => true,
                'has_text'     => true,
                'has_color'    => true,
                'has_variant'  => false,
                'has_shape'    => false,
                'has_bracket'  => false,
                'has_lamp'     => false,
            ],
            str_contains($name, 'neonflex') => [
                'has_size'     => true,
                'has_text'     => true,
                'has_color'    => true,
                'has_variant'  => false,
                'has_shape'    => false,
                'has_bracket'  => false,
                'has_lamp'     => false,
            ],
            str_contains($name, 'logo kayu') || str_contains($name, 'logo ukir') => [
                'has_size'     => true,
                'has_text'     => true,
                'has_color'    => true,
                'has_variant'  => false,
                'has_shape'    => false,
                'has_bracket'  => false,
                'has_lamp'     => false,
            ],
            str_contains($name, 'token') => [
                'has_size'     => true,
                'has_text'     => true,
                'has_color'    => true,
                'has_variant'  => false,
                'has_shape'    => false,
                'has_bracket'  => false,
                'has_lamp'     => false,
            ],
            str_contains($name, 'wall panel') => [
                'has_size'     => true,
                'has_text'     => false,
                'has_color'    => true,
                'has_variant'  => true,
                'has_shape'    => false,
                'has_bracket'  => false,
                'has_lamp'     => false,
            ],
            default => [
                'has_size'     => true,
                'has_text'     => true,
                'has_color'    => true,
                'has_variant'  => false,
                'has_shape'    => false,
                'has_bracket'  => false,
                'has_lamp'     => false,
            ],
        };
    }

    public static function variantOptions(string $productName): array
    {
        $name = strtolower(trim($productName));

        return match (true) {
            str_contains($name, 'wall panel') => ['UV Printed' => 'UV Printed', 'Textured Coating' => 'Textured Coating'],
            default => ['Indoor' => 'Indoor', 'Outdoor' => 'Outdoor', 'Premium' => 'Premium', 'Standard' => 'Standard'],
        };
    }

    public static function shapeOptions(string $productName): array
    {
        return [
            'Kotak' => 'Kotak',
            'Bulat' => 'Bulat',
            'Oval' => 'Oval',
            'Custom' => 'Custom'
        ];
    }

    public static function bracketOptions(string $productName): array
    {
        return [
            'Tanpa Bracket' => 'Tanpa Bracket',
            'Bracket Besi' => 'Bracket Besi',
            'Bracket Hollow' => 'Bracket Hollow',
            'Bracket Stainless' => 'Bracket Stainless'
        ];
    }

    public static function lampOptions(string $productName): array
    {
        return [
            'Tanpa Lampu' => 'Tanpa Lampu',
            'LED Putih' => 'LED Putih',
            'LED Warm White' => 'LED Warm White',
            'RGB' => 'RGB'
        ];
    }
}
