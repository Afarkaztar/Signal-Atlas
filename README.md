# SignalAtlas — Sistem Informasi Pelaporan, Verifikasi & Prioritisasi Infrastruktur Konektivitas Internet di Asia Tenggara

**Kelompok 11 · Kelas B2 · Pemrograman Web — IPB University**

SignalAtlas adalah aplikasi web dinamis berbasis **Laravel 13** yang menghubungkan **warga (kontributor publik)**, **analis/verifikator**, dan **administrator/pemangku kebijakan** untuk memetakan kesenjangan konektivitas internet di 11 negara Asia Tenggara (ASEAN + Timor-Leste), memverifikasi laporan lapangan, menghitung skor prioritas wilayah secara otomatis, serta mengelola proyek pembangunan infrastruktur hingga masalah warga terselesaikan.

---

## Ringkasan Fitur & Alur Bisnis

```
[Warga / Speed Test] ──kirim──▶ Laporan (pending + skor keparahan otomatis 0–100)
                                    │
                       Analis verifikasi / tolak (+10 poin reputasi & notifikasi)
                                    ▼
                        Connectivity Gap Score per Wilayah
                     + Rekomendasi Jenis Infrastruktur & Simulator Anggaran
                                    │
                       Usulkan Proyek (proposed → approved → in_progress → completed)
                                    ▼
             Proyek 100% selesai ──otomatis──▶ Laporan tertaut menjadi "resolved" (+5 poin)
```

| Modul | Halaman / Route | Deskripsi |
|---|---|---|
| **Beranda Interaktif** | `/` | Hero animasi partikel & globe orbit, statistik langsung, peringkat DCI, laporan terbaru |
| **Autentikasi & RBAC** | `/masuk`, `/daftar` | 3 peran: **Administrator**, **Analis/Verifikator**, **Kontributor Publik** |
| **Dashboard** | `/dashboard` | KPI sesuai peran, grafik tren 6 bulan, papan peringkat kontributor, log aktivitas |
| **Laporan Sinyal (CRUD)** | `/laporan/*` | Multi-filter, pencarian, pengurutan, pemilih titik peta & geolokasi, unggah foto bukti, pratinjau skor langsung dari server, ekspor CSV |
| **Speed Test** | `/speedtest` | Uji ping, jitter, unduh, dan unggah langsung ke server + tombol "Kirim sebagai laporan" |
| **Peta Geospasial** | `/peta` | Peta Leaflet dengan 3 layer (titik laporan berdenyut, lingkaran Gap Score wilayah, lokasi proyek) |
| **Analitik & Indeks** | `/analitik` | 4 tab: Peringkat **Digital Connectivity Index (DCI)**, Tren 7 indikator (2015–2024), Perbandingan radar 2 negara, Statistik laporan publik |
| **Antrean Verifikasi** | `/verifikasi` | Setujui/tolak cepat (AJAX), aksi massal, aturan anti-konflik kepentingan (tidak bisa meninjau laporan sendiri) |
| **Prioritas Wilayah** | `/prioritas` | Perhitungan **Connectivity Gap Score**, rekomendasi infrastruktur (BTS/Fiber/Satelit/Microwave/Wi-Fi), simulator alokasi anggaran |
| **Proyek Infrastruktur (CRUD)** | `/proyek/*` | Papan Kanban drag-and-drop + tabel, state machine 7 status, slider progres, riwayat timeline |
| **Data Indikator (CRUD)** | `/indikator/*` | CRUD seri waktu 6 indikator resmi per negara & tahun + ekspor CSV |
| **Pusat Data (Impor/Ekspor)** | `/pusat-data` | Impor CSV indikator & laporan (validasi per baris + mode *dry-run*), unduh template CSV, backup JSON, **Ringkasan Eksekutif siap cetak/PDF** |
| **Manajemen Pengguna & Audit** | `/pengguna`, `/log-aktivitas`, `/notifikasi`, `/profil` | Kelola peran/status akun, audit trail semua aksi, notifikasi in-app, pengaturan profil & sandi |

---

## Akun Demo (Kata sandi: `password`)

| Peran | Email | Hak Akses |
|---|---|---|
| **Administrator** | `admin@signalatlas.id` | Seluruh fitur + persetujuan proyek & manajemen pengguna |
| **Analis / Verifikator** | `analis@signalatlas.id` | Verifikasi laporan, prioritas wilayah, proyek, indikator, impor/ekspor |
| **Kontributor Publik** | `kontributor@signalatlas.id` | Speed test, buat/ubah/hapus laporan sendiri, pantau proyek & reputasi |

---

## Cara Menjalankan

```bash
composer install
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve
```

Buka `http://localhost:8000` di browser. Untuk menjalankan pengujian otomatis:

```bash
php artisan test
```
