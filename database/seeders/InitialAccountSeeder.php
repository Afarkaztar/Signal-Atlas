<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun awal resmi Tim Kelompok 11 (Kelas B2 — Pemrograman Web IPB University).
 * Tidak ada laporan/proyek dummy: seluruh transaksi dibuat langsung melalui aplikasi.
 */
class InitialAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Afarik Rakas Berdi',
                'email' => 'afarik@signalatlas.id',
                'password' => 'SignalAtlas2026!',
                'role' => 'admin',
                'institution' => 'IPB University · Kelompok 11',
            ],
            [
                'name' => 'Fatma Azzuhra Rizqina',
                'email' => 'fatma@signalatlas.id',
                'password' => 'SignalAtlas2026!',
                'role' => 'admin',
                'institution' => 'IPB University · Kelompok 11',
            ],
            [
                'name' => 'Muhammad Ishaq Raihan Najmi',
                'email' => 'ishaq@signalatlas.id',
                'password' => 'SignalAtlas2026!',
                'role' => 'analyst',
                'institution' => 'IPB University · Kelompok 11',
            ],
            [
                'name' => 'Fathiya Raufa Rahman',
                'email' => 'fathiya@signalatlas.id',
                'password' => 'SignalAtlas2026!',
                'role' => 'analyst',
                'institution' => 'IPB University · Kelompok 11',
            ],
            [
                'name' => 'Revan Fadlan Bukit',
                'email' => 'revan@signalatlas.id',
                'password' => 'SignalAtlas2026!',
                'role' => 'contributor',
                'institution' => 'IPB University · Kelompok 11',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account,
            );
        }
    }
}
