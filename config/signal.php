<?php

/*
|--------------------------------------------------------------------------
| SignalAtlas — Domain Dictionary
|--------------------------------------------------------------------------
| Semua label, enum, dan parameter aturan bisnis dipusatkan di sini agar
| mudah diubah tanpa menyentuh controller / view.
*/

return [

    'app_name' => 'SignalAtlas',
    'app_tagline' => 'Sistem Informasi Pelaporan, Verifikasi & Prioritisasi Infrastruktur Konektivitas Internet Asia Tenggara',

    'roles' => [
        'admin' => 'Administrator',
        'analyst' => 'Analis / Verifikator',
        'contributor' => 'Kontributor Publik',
    ],

    'area_types' => [
        'urban' => 'Perkotaan',
        'suburban' => 'Pinggiran Kota',
        'rural' => 'Perdesaan',
        'remote' => 'Terpencil / 3T',
    ],

    'technologies' => [
        'none' => 'Tidak ada sinyal',
        '2g' => '2G / EDGE',
        '3g' => '3G / HSPA',
        '4g' => '4G / LTE',
        '5g' => '5G',
        'fiber' => 'Fiber / Kabel',
    ],

    'issue_types' => [
        'blank_spot' => 'Blank spot (tanpa sinyal)',
        'outage' => 'Gangguan total / putus',
        'unstable' => 'Sinyal tidak stabil',
        'slow' => 'Kecepatan lambat',
        'expensive' => 'Harga tidak terjangkau',
    ],

    'providers' => [
        'Telkomsel', 'Indosat Ooredoo Hutchison', 'XL Axiata', 'Smartfren', 'IndiHome', 'Biznet',
        'Maxis', 'CelcomDigi', 'Unifi', 'Globe Telecom', 'Smart (PLDT)', 'DITO',
        'Singtel', 'StarHub', 'AIS', 'True', 'Viettel', 'VNPT', 'Mobifone',
        'Metfone', 'Unitel', 'MPT', 'Telin', 'Lainnya',
    ],

    'report_statuses' => [
        'pending' => 'Menunggu Verifikasi',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
        'resolved' => 'Terselesaikan',
    ],

    'severity_levels' => [
        'low' => 'Rendah',
        'medium' => 'Sedang',
        'high' => 'Tinggi',
        'critical' => 'Kritis',
    ],

    'project_types' => [
        'bts' => 'Menara BTS 4G/5G',
        'fiber' => 'Jaringan Fiber Optik',
        'satellite' => 'Internet Satelit (VSAT/LEO)',
        'public_wifi' => 'Wi-Fi Publik',
        'microwave' => 'Backhaul Microwave',
    ],

    'project_statuses' => [
        'proposed' => 'Diusulkan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'in_progress' => 'Dalam Pengerjaan',
        'on_hold' => 'Ditunda',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ],

    /*
    | State machine proyek: status => [status tujuan yang diizinkan]
    */
    'project_transitions' => [
        'proposed' => ['approved', 'rejected'],
        'approved' => ['in_progress', 'cancelled'],
        'in_progress' => ['on_hold', 'completed'],
        'on_hold' => ['in_progress', 'cancelled'],
        'rejected' => [],
        'completed' => [],
        'cancelled' => [],
    ],

    // Transisi yang hanya boleh dilakukan Administrator
    'admin_only_transitions' => ['approved', 'rejected', 'cancelled'],

    // Estimasi biaya satuan (USD) untuk kalkulator rekomendasi
    'unit_costs' => [
        'bts' => 180000,
        'fiber' => 250000,
        'satellite' => 60000,
        'public_wifi' => 25000,
        'microwave' => 90000,
    ],

    // Poin gamifikasi kontributor
    'points' => [
        'verified' => 10,
        'resolved' => 5,
    ],
];
