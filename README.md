# SignalAtlas — Sistem Informasi Pelaporan, Verifikasi & Prioritisasi Infrastruktur Konektivitas Internet di Asia Tenggara

**Kelompok 11 · Kelas B2 · Pemrograman Web — IPB University**

SignalAtlas adalah aplikasi web dinamis berbasis **Laravel 13** yang menghubungkan **kontributor publik (warga)**, **analis/verifikator**, dan **administrator/pemangku kebijakan** untuk memetakan kesenjangan konektivitas internet di 11 negara Asia Tenggara (ASEAN + Timor-Leste), memverifikasi laporan lapangan secara berjenjang, menghitung skor prioritas wilayah (*Connectivity Gap Score*), serta mengelola siklus hidup proyek pembangunan infrastruktur hingga masalah warga terselesaikan.

---

## Anggota Kelompok 11 & Pembagian Modul

| No | Nama Anggota | Peran Sistem | Fokus Modul & Kontribusi Demonstrasi |
|---|---|---|---|
| 1 | **Afarik Rakas Berdi** | Administrator | Arsitektur sistem, Dashboard Eksekutif, Manajemen Pengguna (`/pengguna`), Persetujuan akhir proyek (`approved`/`cancelled`), & Audit Log (`/log-aktivitas`) |
| 2 | **Fatma Azzuhra Rizqina** | Administrator | Analitik & **Digital Connectivity Index (DCI)** (`/analitik`), Manajemen Data Indikator Makro ASEAN (`/indikator`), & Ringkasan Eksekutif PDF (`/pusat-data/ringkasan-cetak`) |
| 3 | **Muhammad Ishaq Raihan Najmi** | Analis / Verifikator | **Antrean Verifikasi Berjenjang** (`/verifikasi`), validasi/penolakan laporan publik (satuan & massal), & Pusat Impor/Ekspor CSV + Inspektur Database (`/pusat-data`) |
| 4 | **Fathiya Raufa Rahman** | Analis / Verifikator | **Decision Support System Prioritas Wilayah** (`/prioritas`), Simulator Alokasi Anggaran, & Manajemen Proyek Infrastruktur Kanban (`/proyek`) |
| 5 | **Revan Fadlan Bukit** | Kontributor Publik | Diagnostik **Speed Test Real-Time** (`/speedtest`), Pelaporan Sinyal Geospasial & Bukti Foto (`/laporan`), Peta Interaktif (`/peta`), & Sistem Reputasi/Notifikasi (`/notifikasi`) |

---

## Kredensial Akun Resmi (Login di `/masuk`)

Seluruh akun anggota kelompok telah disiapkan melalui [`InitialAccountSeeder`](database/seeders/InitialAccountSeeder.php) tanpa data laporan/proyek *dummy*, sehingga seluruh alur data didemonstrasikan secara **live** oleh tim.

> **Kata Sandi Default Seluruh Akun Tim:** `SignalAtlas2026!`

| Nama | Email | Peran (`role`) | Hak Akses Utama |
|---|---|---|---|
| **Afarik Rakas Berdi** | `afarik@signalatlas.id` | `admin` (Administrator) | Akses penuh seluruh modul + persetujuan proyek & manajemen pengguna |
| **Fatma Azzuhra Rizqina** | `fatma@signalatlas.id` | `admin` (Administrator) | Akses penuh seluruh modul + pengelolaan indikator, analitik DCI & laporan cetak |
| **Muhammad Ishaq Raihan Najmi** | `ishaq@signalatlas.id` | `analyst` (Analis / Verifikator) | Verifikasi laporan warga, prioritas wilayah, proyek, indikator, impor/ekspor & inspektur DB |
| **Fathiya Raufa Rahman** | `fathiya@signalatlas.id` | `analyst` (Analis / Verifikator) | Prioritas wilayah, simulator anggaran, pengusulan & pembaruan progres proyek |
| **Revan Fadlan Bukit** | `revan@signalatlas.id` | `contributor` (Kontributor Publik) | Speed test, buat/ubah/hapus laporan sinyal, pantau proyek & poin reputasi |

*(Anggota atau audiens juga dapat mendaftarkan akun kontributor baru secara langsung melalui halaman `/daftar`).*

---

## Alur Bisnis Utama (End-to-End Business Flow)

```
[1. Kontributor / Speed Test] ──kirim──▶ Laporan Sinyal (status: pending + Severity Score otomatis 0–100)
                                                │
[2. Analis / Verifikator]     ──tinjau──▶ Setujui (verified, +10 poin & notifikasi) / Tolak (rejected)
                                                │
[3. DSS Prioritas Wilayah]    ──hitung──▶ Connectivity Gap Score (0–100) + Rekomendasi Infrastruktur & Biaya
                                                │
[4. Analis & Administrator]   ──kelola──▶ Proyek Infrastruktur (proposed → approved → in_progress → completed)
                                                │
[5. Penyelesaian Otomatis]    ──100%───▶ Seluruh laporan tertaut otomatis menjadi "resolved" (+5 poin bonus)
```

---

## Cara Mengecek Database Secara Langsung (Lokal & Vercel)

1. **Langsung dari Website (Inspektur Database Live):**
   - Login menggunakan akun **Administrator** atau **Analis**, lalu buka menu **Pusat Data (`/pusat-data`)** dan gulir ke bagian **Inspektur Database Live** (atau klik tombol **Inspektur Database** di kanan atas).
   - Di sana terlihat driver aktif (`SQLITE` / `PGSQL` / `MYSQL`), jumlah baris setiap tabel (`users`, `countries`, `regions`, `indicators`, `reports`, `infrastructure_projects`, `project_updates`, `activity_logs`, `notifications`), serta **25 baris terbaru** dari tabel yang dipilih secara *real-time*.
   - Anda juga dapat mengunduh **Backup JSON** (`/pusat-data/backup-json`) atau file **`.sqlite`** (`/pusat-data/unduh-sqlite`).

2. **Menghubungkan Database Cloud Permanen di Vercel (Agar Input 5 Laptop Tersimpan Permanen):**
   - Buat database PostgreSQL gratis di **Neon.tech** atau **Supabase**.
   - Masukkan variabel berikut pada **Vercel Dashboard → Project Settings → Environment Variables**:
     ```env
     DB_CONNECTION=pgsql
     DB_HOST=<host-database-cloud>
     DB_PORT=5432
     DB_DATABASE=<nama-db>
     DB_USERNAME=<username>
     DB_PASSWORD=<password>
     ```
   - Jalankan migrasi & seeder ke database cloud tersebut satu kali dari komputer lokal:
     ```bash
     php artisan migrate:fresh --seed
     ```

---

## Cara Menjalankan di Lokal

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
