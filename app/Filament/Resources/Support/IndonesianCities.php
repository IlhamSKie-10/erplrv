<?php

namespace App\Filament\Resources\Support;

/**
 * Indonesian cities/regencies list for smart city combobox.
 * Ported from auliart-production/src/lib/constants.ts
 */
class IndonesianCities
{
    public static function list(): array
    {
        return [
            // Jakarta
            'Jakarta Pusat', 'Jakarta Barat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Utara',
            // Jawa Barat
            'Bandung', 'Bekasi', 'Bogor', 'Depok', 'Cianjur', 'Cirebon', 'Garut',
            'Indramayu', 'Karawang', 'Kuningan', 'Majalengka', 'Purwakarta', 'Subang',
            'Sukabumi', 'Sumedang', 'Tasikmalaya',
            // Jawa Tengah
            'Semarang', 'Yogyakarta', 'Solo', 'Blora', 'Boyolali', 'Brebes', 'Cilacap',
            'Demak', 'Grobogan', 'Jepara', 'Karanganyar', 'Kendal', 'Klaten', 'Kudus',
            'Magelang', 'Pati', 'Pekalongan', 'Pemalang', 'Purbalingga', 'Purworejo',
            'Rembang', 'Salatiga', 'Sukoharjo', 'Tegal', 'Temanggung', 'Wonogiri', 'Wonosobo',
            // Jawa Timur
            'Surabaya', 'Malang', 'Gresik', 'Sidoarjo', 'Banyuwangi', 'Blitar',
            'Bojonegoro', 'Bondowoso', 'Jember', 'Jombang', 'Kediri', 'Lamongan',
            'Lumajang', 'Madiun', 'Magetan', 'Mojokerto', 'Nganjuk', 'Ngawi',
            'Pacitan', 'Pamekasan', 'Pasuruan', 'Ponorogo', 'Sampang', 'Sumenep',
            'Tuban', 'Tulungagung',
            // Banten
            'Tangerang', 'Serang', 'Cilegon', 'Lebak', 'Pandeglang', 'Tangerang Selatan',
            // Bali
            'Denpasar', 'Badung', 'Bangli', 'Buleleng', 'Gianyar', 'Jembrana',
            'Karangasem', 'Klungkung', 'Tabanan',
            // NTB
            'Mataram', 'Bima', 'Dompu', 'Lombok Barat', 'Lombok Tengah', 'Lombok Timur',
            'Lombok Utara', 'Sumbawa', 'Sumbawa Barat',
            // NTT
            'Kupang', 'Ende', 'Flores Timur', 'Manggarai', 'Manggarai Barat',
            // Sumatera Utara
            'Medan', 'Binjai', 'Deli Serdang', 'Karo', 'Langkat', 'Simalungun',
            'Asahan', 'Labuhanbatu', 'Pematangsiantar', 'Tebing Tinggi',
            // Sumatera Barat
            'Padang', 'Bukittinggi', 'Payakumbuh', 'Pariaman', 'Agam', 'Dharmasraya',
            // Riau
            'Pekanbaru', 'Batam', 'Bengkalis', 'Kampar', 'Dumai',
            // Jambi
            'Jambi', 'Batanghari', 'Bungo', 'Kerinci',
            // Sumatera Selatan
            'Palembang', 'Prabumulih', 'Lubuk Linggau', 'Banyuasin',
            // Lampung
            'Bandar Lampung', 'Metro', 'Lampung Selatan', 'Lampung Tengah', 'Lampung Utara',
            // Kalimantan Barat
            'Pontianak', 'Singkawang', 'Kubu Raya', 'Mempawah', 'Sambas', 'Sanggau',
            // Kalimantan Tengah
            'Palangkaraya', 'Kotawaringin Barat', 'Kotawaringin Timur', 'Kapuas',
            // Kalimantan Selatan
            'Banjarmasin', 'Banjarbaru', 'Banjar', 'Tabalong', 'Tanah Bumbu',
            // Kalimantan Timur
            'Samarinda', 'Balikpapan', 'Bontang', 'Berau', 'Kutai Kartanegara',
            // Kalimantan Utara
            'Tanjung Selor', 'Nunukan',
            // Sulawesi Utara
            'Manado', 'Bitung', 'Tomohon', 'Minahasa',
            // Sulawesi Tengah
            'Palu', 'Donggala', 'Poso',
            // Sulawesi Selatan
            'Makassar', 'Parepare', 'Palopo', 'Gowa', 'Maros', 'Bone', 'Wajo', 'Luwu',
            // Sulawesi Tenggara
            'Kendari', 'Bau-bau', 'Kolaka', 'Konawe',
            // Gorontalo
            'Gorontalo',
            // Maluku
            'Ambon', 'Ternate', 'Tidore',
            // Papua
            'Jayapura', 'Merauke', 'Biak', 'Mimika', 'Nabire',
        ];
    }

    public static function options(): array
    {
        return array_combine(static::list(), static::list());
    }
}
