<?php

namespace Database\Seeders;

use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\QrConfig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users (Idempotent updateOrCreate)
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@alazharpeduli.or.id'],
            [
                'name' => 'Super Admin Al Azhar',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'viewer@alazhar.or.id'],
            [
                'name' => 'Auditor / Viewer User',
                'password' => Hash::make('password123'),
                'role' => 'viewer',
                'is_active' => true,
            ]
        );

        // 2. Seed Master Kode Aset (Divisi, Kategori 2 huruf, Barang EL01-55/FN01-22/KD01-04, PIC NIA, Lokasi 3 digit)
        $this->call(MasterKodeAsetSeeder::class);

        // 3. Seed Merk Sample
        Merk::firstOrCreate(['nama_merk' => 'Asus']);
        Merk::firstOrCreate(['nama_merk' => 'Toyota']);
        Merk::firstOrCreate(['nama_merk' => 'Olympic']);

        // 4. Seed Lokasi Sample
        Lokasi::firstOrCreate(
            ['kode_lokasi' => 'LAB01'],
            [
                'nama_lokasi' => 'Lab Komputer 1',
                'alamat_lengkap' => 'Gedung A Lt. 2 R.204, Kampus Al Azhar',
                'latitude' => -6.2382,
                'longitude' => 106.8015,
            ]
        );

        Lokasi::firstOrCreate(
            ['kode_lokasi' => 'POOL1'],
            [
                'nama_lokasi' => 'Pool Kendaraan Utama',
                'alamat_lengkap' => 'Area Parkir Barat Kampus Al Azhar',
            ]
        );

        // 5. Seed Penanggung Jawab Sample
        PenanggungJawab::firstOrCreate(
            ['email' => 'budi.santoso@alazhar.or.id'],
            [
                'nama' => 'Budi Santoso, S.Kom',
                'jabatan' => 'Kepala Laboratorium IT',
                'telepon' => '081234567890',
                'user_id' => $superAdmin->id,
            ]
        );

        PenanggungJawab::firstOrCreate(
            ['email' => 'dedi.wijaya@alazhar.or.id'],
            [
                'nama' => 'Dedi Wijaya',
                'jabatan' => 'Koordinator Operasional Pool',
                'telepon' => '089876543210',
            ]
        );

        // 6. Seed Default QR Config (Public Scan & QR Label Settings)
        foreach (QrConfig::getDefaults() as $key => $meta) {
            QrConfig::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $meta['value'],
                    'group' => $meta['group'],
                    'type' => $meta['type'],
                    'label' => $meta['label'],
                    'urutan' => $meta['urutan'],
                ]
            );
        }
    }
}
