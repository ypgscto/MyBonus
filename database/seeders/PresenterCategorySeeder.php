<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\PresenterCategory;
use Illuminate\Database\Seeder;

class PresenterCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pegawai', 'description' => 'Kategori presenter pegawai'],
            ['name' => 'Mahasiswa', 'description' => 'Kategori presenter mahasiswa'],
            ['name' => 'Tamu', 'description' => 'Kategori presenter tamu'],
            ['name' => 'Vendor', 'description' => 'Kategori presenter vendor'],
        ];

        foreach ($categories as $category) {
            PresenterCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'status' => RecordStatus::Aktif,
                ]
            );
        }
    }
}
