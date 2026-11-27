# Panduan Pencerdasan Kode, Arsitektur & Persiapan Presentasi — SignalAtlas

**Kelompok 11 · Kelas B2 · Pemrograman Web — IPB University**  
Dokumen pendamping [`README.md`](README.md) untuk pegangan internal anggota kelompok dalam memahami alur sistem, arsitektur program, letak baris kode setiap fitur, skenario demonstrasi langsung (*live demo*), serta persiapan tanya-jawab teknis.

---

## Daftar Isi
1. [Bagian 1 — Konsep Utama, Arsitektur Sistem & Peta Folder](#bagian-1--konsep-utama-arsitektur-sistem--peta-folder)
2. [Bagian 2 — Bedah Logika, Rumus & Algoritma (`app/Services/`)](#bagian-2--bedah-logika-rumus--algoritma-appservices)
3. [Bagian 3 — Bedah Kode Spesifik per Anggota Kelompok (1–5)](#bagian-3--bedah-kode-spesifik-per-anggota-kelompok-15)
   - [1. Revan Fadlan Bukit (`contributor`)](#1-revan-fadlan-bukit--modul-kontributor-diagnostik--geospasial)
   - [2. Muhammad Ishaq Raihan Najmi (`analyst`)](#2-muhammad-ishaq-raihan-najmi--modul-verifikasi-pusat-data-csv--inspektur-db)
   - [3. Fathiya Raufa Rahman (`analyst`)](#3-fathiya-raufa-rahman--modul-dss-prioritas-wilayah--proyek-infrastruktur)
   - [4. Fatma Azzuhra Rizqina (`admin`)](#4-fatma-azzuhra-rizqina--modul-analitik-dci-indikator-makro--laporan-eksekutif)
   - [5. Afarik Rakas Berdi (`admin`)](#5-afarik-rakas-berdi--arsitektur-inti-keamanan-rbac-dashboard-persetujuan-proyek-user--audit)
4. [Bagian 4 — Koreografi Live Demo, Arsitektur Vercel & Antisipasi Q&A Teknis](#bagian-4--koreografi-live-demo-arsitektur-vercel--antisipasi-qa-teknis)

---

# Bagian 1 — Konsep Utama, Arsitektur Sistem & Peta Folder

## 1.1 Gambaran Umum Sistem (Elevator Pitch)
**SignalAtlas** adalah **Sistem Informasi Pelaporan, Verifikasi, dan Prioritisasi Infrastruktur Konektivitas Internet di Asia Tenggara** (mencakup 11 negara: 10 negara ASEAN + Timor-Leste).

* **Latar Belakang Masalah:** Data statistik makro sering tidak menangkap titik *blank spot* mikro di lapangan, sementara keluhan warga tidak terstruktur dan tidak terhubung langsung dengan perencanaan anggaran pembangunan infrastruktur (BTS, fiber optik, satelit).
* **Solusi SignalAtlas:** Menghubungkan data *bottom-up* (*crowdsourcing* warga) dengan data *top-down* (indikator resmi World Bank/ITU/Ookla) dalam satu **siklus kerja tertutup (*closed-loop workflow*)** berbasis **3 peran pengguna (RBAC)**:
  1. **Kontributor Publik (`contributor`)** — Menguji kecepatan jaringan (*real-time speed test*) dan mengirim laporan masalah sinyal berkoordinat GPS + bukti foto.
  2. **Analis / Verifikator (`analyst`)** — Memverifikasi/menolak laporan warga agar bebas *spam*, menganalisis *Decision Support System (DSS)* prioritas wilayah, serta mengusulkan dan memperbarui progres proyek infrastruktur.
  3. **Administrator (`admin`)** — Pemangku kebijakan yang menyetujui/membatalkan anggaran proyek, mengelola data indikator makro & pengguna, serta memantau *audit trail* seluruh aktivitas sistem.

## 1.2 Alur Kerja Bisnis Utama (*End-to-End Business Flow*)

```text
[1. KONTRIBUTOR / REVAN]
   Uji jaringan di /speedtest atau isi form /laporan/baru
   ──▶ Server otomatis menghitung Severity Score (0–100) & kode RPT-YYMM-XXXX (status: pending)
          │
          ▼
[2. ANALIS VERIFIKATOR / ISHAQ]
   Meninjau antrean di /verifikasi (dilengkapi aturan anti-self-review)
   ──▶ Klik Setujui: status jadi "verified", pelapor otomatis dapat +10 Poin & Notifikasi in-app
          │
          ▼
[3. ANALIS PERENCANA / FATHIYA]
   Membuka /prioritas: sistem menggabungkan laporan "verified" + populasi + tipe wilayah (3T/desa/kota)
   ──▶ Menghitung Connectivity Gap Score (0–100) & merekomendasikan jenis infrastruktur + estimasi biaya
   ──▶ Mengusulkan proyek baru PRJ-YYYY-XXX di /proyek (status: proposed)
          │
          ▼
[4. ADMINISTRATOR / AFARIK & FATMA]
   Admin menyetujui proyek (status: proposed ──▶ approved)
   ──▶ Otomatis menautkan seluruh laporan "verified" di wilayah tersebut ke proyek ini
   ──▶ Analis/Admin memperbarui progres pengerjaan (in_progress: 0% ──▶ 100%)
          │
          ▼
[5. PENYELESAIAN OTOMATIS (CLOSED-LOOP)]
   Saat progres proyek mencapai 100% (status: completed):
   ──▶ Seluruh laporan warga yang tertaut OTOMATIS berubah menjadi "resolved"
   ──▶ Pelapor otomatis mendapat bonus +5 Poin Reputasi & Notifikasi penyelesaian
   ──▶ Skor kesenjangan wilayah di /prioritas & /peta otomatis turun karena masalah tertangani
```

## 1.3 Arsitektur 5 Lapis (Laravel 13 MVC + Service Layer + Domain Config)
Program ini memisahkan logika bisnis dari Controller agar kode bersih, modular, dan mudah dipelihara:

| Lapisan | Lokasi Folder / File | Fungsi di Belakang Layar |
|---|---|---|
| **1. Routing & Middleware** | [`routes/web.php`](routes/web.php), [`EnsureRole.php`](app/Http/Middleware/EnsureRole.php) | Pintu gerbang URL. Memeriksa status login (`auth`), keaktifan akun (`is_active`), dan hak akses peran (`role:admin,analyst`). |
| **2. Domain Dictionary** | [`config/signal.php`](config/signal.php) | *Single Source of Truth* (kamus terpusat): daftar peran, daftar ISP ASEAN, kategori masalah, aturan transisi status proyek, estimasi biaya satuan USD, dan poin reputasi. |
| **3. Controller** | `app/Http/Controllers/*.php` (16 file) | Menerima HTTP `Request`, menjalankan validasi input, memanggil *Service*, lalu mengembalikan *View* atau respons JSON. |
| **4. Service Layer (Otak Bisnis)** | `app/Services/*.php` (8 file) | Tempat seluruh rumus perhitungan skor, transaksi database (`DB::transaction`), pengiriman notifikasi, pencatatan log audit, dan pemrosesan CSV berada. |
| **5. Model & View** | `app/Models/*.php` (8 file) & `resources/views/**/*.blade.php` | **Eloquent ORM** berkomunikasi dengan database secara aman dari *SQL Injection*. **Blade View** merender antarmuka bertema *Dark Aurora Glassmorphism* dengan Chart.js, Leaflet, dan Quill. |

**Keunggulan Arsitektur Frontend (*Zero-Build Asset*):**
* Seluruh CSS kustom berada di [`public/assets/css/app.css`](public/assets/css/app.css) dan utilitas JS global (`window.SA`) berada di [`public/assets/js/app.js`](public/assets/js/app.js), dimuat dengan *cache-busting* otomatis `filemtime()` di [`resources/views/partials/head.blade.php`](resources/views/partials/head.blade.php#L10).
* Peta interaktif menggunakan **Leaflet + OpenStreetMap / Esri Satellite** tanpa memerlukan API Key berbayar ([`app.js` baris 16–34](public/assets/js/app.js#L16-L34)).
* Efek suara antarmuka (*UI Sound Effects*) disintesis secara matematis menggunakan **Web Audio API** langsung di browser tanpa file audio eksternal ([`app.js` baris 50–85](public/assets/js/app.js#L50-L85)).

## 1.4 Skema Database (9 Tabel Utama di `database/migrations/`)
1. **`users`** ([`0001_01_01_000000_create_users_table.php`](database/migrations/0001_01_01_000000_create_users_table.php#L14-L29)) — Menyimpan kredensial pengguna, `role` (`admin` | `analyst` | `contributor`), `points` reputasi, dan flag `is_active`. Akun resmi 5 anggota disiapkan oleh [`InitialAccountSeeder.php`](database/seeders/InitialAccountSeeder.php).
2. **`countries` & `regions`** ([`2026_11_01_000001_create_countries_and_regions_tables.php`](database/migrations/2026_11_01_000001_create_countries_and_regions_tables.php#L11-L35)) — Relasi `1-to-Many` antara 11 negara Asia Tenggara dengan provinsi/distrik (`area_type`: `urban`, `suburban`, `rural`, `remote`, populasi, dan koordinat GPS). Data referensi diisi oleh [`ReferenceDataSeeder.php`](database/seeders/ReferenceDataSeeder.php).
3. **`indicators`** ([`2026_11_01_000002_create_indicators_table.php`](database/migrations/2026_11_01_000002_create_indicators_table.php#L11-L26)) — Data seri waktu indikator makro per negara & tahun (2015–2024) dengan *unique constraint* komposit `['country_id', 'year']`.
4. **`infrastructure_projects`, `project_updates`, & `reports`** ([`2026_11_01_000003_create_projects_and_reports_tables.php`](database/migrations/2026_11_01_000003_create_projects_and_reports_tables.php#L11-L74)):
   * `reports` menyimpan laporan sinyal warga, kecepatan unduh/unggah, latensi, bukti foto, `severity_score`, `verified_by`, dan relasi `infrastructure_project_id`.
   * `infrastructure_projects` menyimpan data proyek, anggaran USD, persentase `progress`, `priority_score`, `proposed_by`, dan `approved_by`.
   * `project_updates` menyimpan *timeline* riwayat perubahan status dan progres proyek.
5. **`activity_logs` & `notifications`** ([`2026_11_01_000004_create_activity_logs_and_notifications_tables.php`](database/migrations/2026_11_01_000004_create_activity_logs_and_notifications_tables.php#L11-L30)) — `activity_logs` mencatat *audit trail* lintas modul beserta IP pengguna; `notifications` menyimpan notifikasi *in-app* berbasis UUID.

---

# Bagian 2 — Bedah Logika, Rumus & Algoritma (`app/Services/`)

## 2.1 Skor Keparahan Laporan (*Severity Score* 0–100)
* **Lokasi Kode:** [`app/Services/SeverityCalculator.php`](app/Services/SeverityCalculator.php#L17-L75)
* **Cara Kerja (`calculate()`, baris 23–65):** Menjumlahkan 4 komponen penalti hingga maksimal **100 poin**:
  1. **Kecepatan Unduh (maks 45 poin, baris 30–38):** `<= 0.5 Mbps` atau *blank spot* tanpa sinyal = **45** | `< 3 Mbps` = **36** | `< 10 Mbps` = **26** | `< 25 Mbps` = **14** | `< 50 Mbps` = **6** | `>= 50 Mbps` = **0**.
  2. **Latensi / Ping (maks 15 poin, baris 40–47):** `> 300 ms` (atau kosong saat *blank spot*) = **15** | `> 150 ms` = **11** | `> 80 ms` = **6** | `> 40 ms` = **2** | `<= 40 ms` = **0**.
  3. **Jenis Masalah (maks 25 poin, baris 19):** `blank_spot` (**25**), `outage` (**20**), `unstable` (**15**), `slow` (**10**), `expensive` (**8**).
  4. **Teknologi Jaringan (maks 15 poin, baris 21):** `none` (**15**), `2g` (**11**), `3g` (**7**), `4g` (**3**), `5g` / `fiber` (**0**).
* **Ambang Level (`levelFor()`, baris 67–75):** `>= 75` **Kritis (`critical`)**, `>= 50` **Tinggi (`high`)**, `>= 25` **Sedang (`medium`)**, `< 25` **Rendah (`low`)**.
* **Pratinjau Langsung di Form:** Saat user mengisi form laporan ([`resources/views/reports/_form.blade.php` baris 239–258](resources/views/reports/_form.blade.php#L239-L258)), JS memanggil endpoint `POST /api/severity-preview` dengan *debounce* 250 ms sehingga pratinjau di layar dihitung langsung oleh rumus PHP yang sama di server.

## 2.2 Alur Verifikasi & Aturan Anti-Konflik Kepentingan
* **Lokasi Kode:** [`app/Services/ReportWorkflow.php`](app/Services/ReportWorkflow.php#L16-L90)
* **Penjaga Aturan (`guard()`, baris 77–90):**
  1. Hanya `admin` atau `analyst` yang dapat memproses verifikasi.
  2. Hanya laporan berstatus `pending` yang boleh ditinjau (mencegah verifikasi ganda).
  3. **Anti-Self-Review (baris 87–89):** Verifikator dilarang meninjau laporan yang dibuat oleh akunnya sendiri (`$report->user_id === $reviewer->id`).
* **Transaksi Database Atomik (`DB::transaction`, baris 22–44):** Mengubah status ke `verified`, menambah **+10 poin** ke akun pelapor (otomatis menghitung ulang lencana *Scout / Pathfinder / Navigator / Sentinel* di [`User.php` baris 82–90](app/Models/User.php#L82-L90)), mengirim notifikasi ke lonceng pelapor, dan mencatat `ActivityLogger`.

## 2.3 DSS Prioritas Wilayah (*Connectivity Gap Score*) & Simulator Anggaran
* **Lokasi Kode:** [`app/Services/PriorityService.php`](app/Services/PriorityService.php#L21-L110) & [`app/Http/Controllers/PriorityController.php`](app/Http/Controllers/PriorityController.php#L18-L27)
* **Rumus *Gap Score* (0–100) pada `analyse()` (baris 53–67):** Hanya menghitung laporan berstatus **`verified`**:
  * `0.5 * rata_rata_severity` (maks **50 poin**)
  * `min(4 * jumlah_laporan_aktif, 25)` (maks **25 poin**)
  * Bobot afirmasi wilayah (`AREA_WEIGHTS`, baris 23): `remote` (**+15**), `rural` (**+10**), `suburban` (**+5**), `urban` (**0**)
  * Faktor populasi logaritmik (baris 59): `min(10, max(0, (log10(population) - 4) * 3.3))` (maks **10 poin**)
* **Skor Efektif (`effective_score`, baris 67):** Jika wilayah sudah memiliki proyek aktif (`approved`, `in_progress`, `on_hold`), skor dipotong 50% (`gap * 0.5`) agar wilayah yang belum tertangani naik ke puncak antrean prioritas.
* **Rekomendasi Infrastruktur Otomatis (`recommendType()`, baris 87–97):**
  * Rasio *blank spot* `>= 50%` di wilayah `remote` ──▶ **Internet Satelit (`satellite`)**
  * Rasio *blank spot* `>= 50%` di wilayah lainnya ──▶ **Menara BTS 4G/5G (`bts`)**
  * Wilayah `urban`/`suburban` dengan rata-rata unduh `< 25 Mbps` ──▶ **Fiber Optik (`fiber`)**
  * Wilayah `rural` ──▶ **Backhaul Microwave (`microwave`)**
  * Masalah ringan lainnya ──▶ **Wi-Fi Publik (`public_wifi`)**
* **Algoritma *Greedy* Simulator Anggaran ([`PriorityController.php` baris 19–27](app/Http/Controllers/PriorityController.php#L19-L27)):** Memilih wilayah belum tertangani secara berurutan dari `effective_score` tertinggi selama estimasi biaya proyek mencukupi sisa plafon anggaran.

## 2.4 *State Machine* Proyek & *Auto-Resolve* Laporan Warga
* **Lokasi Kode:** [`app/Services/ProjectWorkflow.php`](app/Services/ProjectWorkflow.php#L24-L140) & [`config/signal.php`](config/signal.php#L88-L100)
* **Aturan Transisi:**
  * `proposed` ──▶ `approved` / `rejected` *(khusus Admin)*
  * `approved` ──▶ `in_progress` / `cancelled` *(cancelled khusus Admin)*
  * `in_progress` ──▶ `on_hold` / `completed`
  * `on_hold` ──▶ `in_progress` / `cancelled`
* **Efek Samping Otomatis:**
  * Saat status menjadi **`approved` (baris 51–58):** Seluruh laporan `verified` di wilayah tersebut yang belum tertaut proyek otomatis dihubungkan ke proyek ini (`infrastructure_project_id`).
  * Saat progres mencapai **`100%` / `completed` (baris 60–64 & 122–140):** Method `resolveLinkedReports()` otomatis mengubah seluruh laporan tertaut menjadi **`resolved`**, memberikan bonus **+5 poin** kepada masing-masing pelapor, dan mengirim notifikasi penyelesaian.

## 2.5 *Digital Connectivity Index (DCI)* Makro ASEAN
* **Lokasi Kode:** [`app/Services/ConnectivityIndexService.php`](app/Services/ConnectivityIndexService.php#L21-L110)
* **Komponen & Bobot (`components()`, baris 33–44):**
  1. Pengguna Internet (`35%`) — nilai langsung `0–100%`
  2. Mobile Broadband per 100 penduduk (`20%`) — dinormalisasi terhadap cap `150`
  3. Fixed Broadband per 100 penduduk (`15%`) — dinormalisasi terhadap cap `40`
  4. Rata-rata Kecepatan Mobile & Fixed (`20%`) — dinormalisasi terhadap cap `100 Mbps`
  5. Keterjangkauan Harga Data USD/GB (`10%`) — diinversi `(1 - min(harga, 5) / 5) * 100` (semakin murah harga per GB, semakin tinggi skornya)
* **Penanganan Data Kosong (`score()`, baris 46–60):** Jika suatu negara belum memiliki salah satu metrik (`null`), bobot komponen yang tersedia didistribusikan ulang secara proporsional (`$total / $weight`) agar skor tetap adil.

---

# Bagian 3 — Bedah Kode Spesifik per Anggota Kelompok (1–5)

## 1. Revan Fadlan Bukit — Modul Kontributor, Diagnostik & Geospasial
* **Akun Demo Live:** `revan@signalatlas.id` (`contributor`)
* **File Utama yang Wajib Dikuasai:**
  * **Speed Test (`/speedtest`):** [`SpeedTestController.php`](app/Http/Controllers/SpeedTestController.php#L20-L50) & [`resources/views/speedtest/index.blade.php`](resources/views/speedtest/index.blade.php#L92-L165).
    * Mengukur *Ping* & *Jitter* dari 8 sampel RTT tanpa cache (`Cache-Control: no-store`), mengukur *Download* dengan membaca *binary stream* `random_bytes(64 * 1024)` melalui `ReadableStream` browser, dan mengukur *Upload* dengan mengirim payload `Uint8Array` 512 KB.
    * Tombol **"Kirim sebagai laporan"** otomatis mem-*prefill* hasil ukur ke form laporan melalui query string (baris 148).
  * **CRUD Laporan Sinyal (`/laporan`):** [`ReportController.php`](app/Http/Controllers/ReportController.php), [`Report.php`](app/Models/Report.php), & `resources/views/reports/*.blade.php`.
    * **Tabel AJAX Tanpa Reload:** [`reports/index.blade.php` baris 102–143](resources/views/reports/index.blade.php#L102-L143) memperbarui *sorting* kolom, filter, dan *pagination* secara dinamis menggunakan `fetch()` + `DOMParser` + `history.pushState`.
    * **Upload Foto & Notifikasi Kritis:** [`ReportController::store()` baris 80–93](app/Http/Controllers/ReportController.php#L80-L93) menyimpan foto ke disk publik `reports/` dan langsung mengirim peringatan ke seluruh Analis/Admin jika level laporan `critical`.
    * **Editor WYSIWYG + Sanitasi Anti-XSS:** Form menggunakan editor **Quill 2.0** ([`_form.blade.php` baris 158–237](resources/views/reports/_form.blade.php#L158-L237)), sedangkan backend menyanitasi HTML memakai `DOMDocument` di [`Report::sanitizeHtml()` baris 125–212](app/Models/Report.php#L125-L212) agar bebas dari injeksi `<script>` / XSS.
    * **Proteksi Kepemilikan:** Kontributor hanya dapat mengedit/menghapus laporannya sendiri selama status masih `pending` (`authorizeEdit()` baris 284–288).
  * **Peta Interaktif (`/peta`) & Notifikasi (`/notifikasi`):** [`MapController.php`](app/Http/Controllers/MapController.php#L21-L75) menyuplai JSON 3 layer (titik laporan berdenyut, radius *Gap Score* wilayah, dan lokasi proyek); [`NotificationController.php`](app/Http/Controllers/NotificationController.php) mengelola status baca notifikasi pengguna.

---

## 2. Muhammad Ishaq Raihan Najmi — Modul Verifikasi, Pusat Data CSV & Inspektur DB
* **Akun Demo Live:** `ishaq@signalatlas.id` (`analyst`)
* **File Utama yang Wajib Dikuasai:**
  * **Antrean Verifikasi (`/verifikasi`):** [`VerificationController.php`](app/Http/Controllers/VerificationController.php#L18-L98) & [`resources/views/verification/index.blade.php`](resources/views/verification/index.blade.php).
    * Mengurutkan antrean `pending` dari skor keparahan tertinggi (`orderByDesc('severity_score')`, baris 24) agar laporan darurat selalu ditangani lebih dulu.
    * **Aksi Cepat AJAX:** Klik *Setujui* atau *Tolak* mengirim request JSON tanpa reload halaman, menggeser baris keluar dari tabel, dan langsung memperbarui angka *badge* antrean di sidebar ([`verification/index.blade.php` baris 111–128](resources/views/verification/index.blade.php#L111-L128)).
    * **Verifikasi/Penolakan Massal (`bulk()`, baris 64–89):** Memproses banyak ID sekaligus dan otomatis melewati laporan milik sendiri (`$skipped[]`) tanpa menghentikan batch.
  * **Pusat Data CSV & Mode *Dry-Run* (`/pusat-data`):** [`DataCenterController.php`](app/Http/Controllers/DataCenterController.php#L104-L271) & [`CsvService.php`](app/Services/CsvService.php).
    * `CsvService` menyertakan **UTF-8 BOM** agar rapi di Microsoft Excel dan otomatis mendeteksi delimiter `,` maupun `;`.
    * `importIndicators()` & `importReports()` memvalidasi CSV per baris (melaporkan nomor baris yang gagal) dan mendukung mode simulasi **Dry-Run** menggunakan `$dryRun ? DB::rollBack() : DB::commit()` (baris 220 & 268).
  * **Inspektur Database Live & Backup (`index()` baris 34–92 & `downloadSqlite()` baris 94–102):**
    * Menampilkan koneksi database aktif, jumlah baris ke-9 tabel secara *real-time*, pratinjau 25 baris terbaru per tabel (dengan menghapus kolom sensitif `password` & `remember_token` di baris 61), serta fitur unduh Backup JSON & file `.sqlite`.

---

## 3. Fathiya Raufa Rahman — Modul DSS Prioritas Wilayah & Proyek Infrastruktur
* **Akun Demo Live:** `fathiya@signalatlas.id` (`analyst`)
* **File Utama yang Wajib Dikuasai:**
  * **DSS Prioritas Wilayah & Simulator Anggaran (`/prioritas`):** [`PriorityController.php`](app/Http/Controllers/PriorityController.php), [`PriorityService.php`](app/Services/PriorityService.php), & [`resources/views/priorities/index.blade.php`](resources/views/priorities/index.blade.php).
    * Menghitung peringkat wilayah menggunakan agregasi SQL teroptimasi (`withCount` & `withAvg` di [`PriorityService.php` baris 31–43](app/Services/PriorityService.php#L31-L43)) tanpa masalah *N+1 query*.
    * Menyediakan slider **Simulator Alokasi Anggaran** (\$100.000 – \$5.000.000) yang menandai wilayah terpilih beserta total estimasi warga penerima manfaat.
    * Tombol **"Usulkan"** pada tabel prioritas mengirim parameter rekomendasi langsung ke form pembuatan proyek ([`ProjectController::create()` baris 50–68](app/Http/Controllers/ProjectController.php#L50-L68)).
  * **Manajemen Proyek & Papan Kanban (`/proyek`):** [`ProjectController.php`](app/Http/Controllers/ProjectController.php), [`ProjectWorkflow.php`](app/Services/ProjectWorkflow.php), & `resources/views/projects/*.blade.php`.
    * Menampilkan proyek dalam **2 tampilan** (Papan Kanban *Drag-and-Drop* HTML5 & Tabel Daftar) serta mendukung ekspor CSV proyek.
    * Di [`projects/index.blade.php` baris 103–135](resources/views/projects/index.blade.php#L103-L135), kartu Kanban memeriksa `data-allowed` secara visual saat diseret (hijau jika diizinkan *state machine*, merah jika dilarang) sebelum mengirim transisi via AJAX.
    * Mengelola pembaruan persentase progres lapangan (`progress()`, baris 145–156) dan riwayat *timeline* `project_updates` di halaman detail proyek.

---

## 4. Fatma Azzuhra Rizqina — Modul Analitik DCI, Indikator Makro & Laporan Eksekutif
* **Akun Demo Live:** `fatma@signalatlas.id` (`admin`)
* **File Utama yang Wajib Dikuasai:**
  * **Analitik Konektivitas 4-Tab (`/analitik`):** [`AnalyticsController.php`](app/Http/Controllers/AnalyticsController.php#L14-L60), [`ConnectivityIndexService.php`](app/Services/ConnectivityIndexService.php), & [`resources/views/analytics/index.blade.php`](resources/views/analytics/index.blade.php).
    * **Tab 1 — Peringkat DCI:** Tabel peringkat 11 negara beserta *grade* (A–E), perubahan skor dari tahun sebelumnya (`delta`), dan grafik *Horizontal Bar* Chart.js.
    * **Tab 2 — Tren Multi-Indikator:** Grafik garis interaktif 2015–2024 untuk 7 metrik dengan *toggle* filter negara secara instan di sisi klien.
    * **Tab 3 — Bandingkan Negara:** Grafik **Radar 5 Dimensi** untuk membandingkan profil kekuatan dua negara secara berdampingan.
    * **Tab 4 — Laporan Publik:** Statistik agregasi laporan terverifikasi berdasarkan kategori masalah, teknologi, sebaran negara, dan peringkat kecepatan rata-rata operator/ISP (`GROUP BY` + `HAVING n >= 2`, baris 40–42).
  * **CRUD Data Indikator Makro (`/indikator`):** [`IndicatorController.php`](app/Http/Controllers/IndicatorController.php#L17-L114) & `resources/views/indicators/*.blade.php`.
    * Menggunakan validasi unik komposit `Rule::unique('indicators', 'year')->where('country_id', $countryId)->ignore($ignore)` (baris 97–98) agar tidak terjadi duplikasi tahun pada negara yang sama, serta menyediakan fitur ekspor CSV indikator.
  * **Ringkasan Eksekutif Cetak / PDF (`/pusat-data/ringkasan-cetak`):** [`DataCenterController::print()`](app/Http/Controllers/DataCenterController.php#L153-L164) & [`resources/views/data/print.blade.php`](resources/views/data/print.blade.php) — menghasilkan laporan eksekutif siap cetak/simpan PDF (`@media print`).

---

## 5. Afarik Rakas Berdi — Arsitektur Inti, Keamanan RBAC, Dashboard, Persetujuan Proyek, User & Audit
* **Akun Demo Live:** `afarik@signalatlas.id` (`admin`)
* **File Utama yang Wajib Dikuasai:**
  * **Arsitektur, Routing & Middleware RBAC:** [`bootstrap/app.php`](bootstrap/app.php), [`routes/web.php`](routes/web.php), [`EnsureRole.php`](app/Http/Middleware/EnsureRole.php), & [`AppServiceProvider.php`](app/Providers/AppServiceProvider.php).
    * Membagi zona akses route (`guest`, `auth`, `role:admin,analyst`, `role:admin`), menginjeksi hitungan *badge* global melalui `View::composer`, dan memaksa *logout* otomatis jika akun dinonaktifkan.
  * **Autentikasi (`/masuk`, `/daftar`, `/keluar`):** [`AuthController.php`](app/Http/Controllers/AuthController.php#L21-L95).
    * Dilengkapi *rate-limiting* `throttle:10,1`, *password hashing* otomatis, regenerasi sesi anti-*session fixation* (`$request->session()->regenerate()`), dan pengiriman notifikasi sambutan saat registrasi.
  * **Beranda (`/`) & Dashboard Kontekstual (`/dashboard`):** [`HomeController.php`](app/Http/Controllers/HomeController.php) & [`DashboardController.php`](app/Http/Controllers/DashboardController.php#L16-L56).
    * Dashboard beradaptasi dengan peran login (`$user->isStaff() ? Report::query() : $user->reports()`): menampilkan KPI keseluruhan, tren 6 bulan, & Top 5 Prioritas untuk Staf; atau statistik kontribusi pribadi & peringkat *leaderboard* untuk Kontributor.
  * **Otoritas Persetujuan Proyek, Manajemen Pengguna (`/pengguna`) & Audit Log (`/log-aktivitas`):** [`UserController.php`](app/Http/Controllers/UserController.php), [`ActivityLogController.php`](app/Http/Controllers/ActivityLogController.php), & [`ActivityLogger.php`](app/Services/ActivityLogger.php).
    * Memegang hak eksklusif Admin untuk menyetujui (`approved`), menolak (`rejected`), atau membatalkan (`cancelled`) proyek infrastruktur.
    * Dilengkapi proteksi anti-*self-lockout* di `UserController` (baris 52, 66, 75) agar Admin tidak bisa menurunkan peran, menonaktifkan, atau menghapus akunnya sendiri.

---

# Bagian 4 — Koreografi Live Demo, Arsitektur Vercel & Antisipasi Q&A Teknis

## 4.1 Urutan Estafet *Live Demo* Kelompok (Dari 0 Sampai *Auto-Resolved*)

1. **Pembukaan (Afarik):** Tampilkan Beranda `/`, jelaskan konsep *closed-loop* 3 peran, dan demonstrasikan *Command Palette* (`Ctrl + K`).
2. **Input Lapangan (Revan — `revan@signalatlas.id`):** Jalankan `/speedtest` ──▶ klik **Kirim sebagai laporan** (pilih wilayah 3T misal Papua / NTT, tipe *Blank Spot*) ──▶ tunjukkan *live preview* skor keparahan, editor WYSIWYG + upload foto ──▶ kirim laporan (`pending`).
3. **Verifikasi & Inspeksi DB (Ishaq — `ishaq@signalatlas.id`):** Tunjukkan notifikasi laporan kritis masuk ──▶ buka `/verifikasi` dan klik **Setujui** (baris hilang via AJAX tanpa reload) ──▶ buka `/pusat-data` untuk demo **Impor CSV (Dry-Run & Simpan)** serta buktikan data masuk di **Inspektur Database Live**.
4. **Prioritisasi & Usulan Proyek (Fathiya — `fathiya@signalatlas.id`):** Buka `/prioritas`, tunjukkan *Connectivity Gap Score* wilayah tadi naik ke puncak beserta rekomendasi teknologinya, geser **Simulator Anggaran**, lalu klik **Usulkan** ──▶ simpan proyek baru (`proposed`) di papan Kanban `/proyek`.
5. **Persetujuan Anggaran, Penyelesaian 100% & Audit (Afarik — `afarik@signalatlas.id`):** Seret kartu proyek di Kanban ke **`Disetujui`** (otomatis menautkan laporan terverifikasi) lalu ke **`Dalam Pengerjaan`** ──▶ geser progres proyek ke **`100%`** sehingga proyek menjadi **`Selesai`** dan laporan Revan otomatis berubah menjadi **`Terselesaikan` (`resolved`)** (+15 total poin reputasi). Tunjukkan jejak lengkapnya di `/log-aktivitas` dan `/pengguna`.
6. **Analitik Makro & Laporan Eksekutif PDF (Fatma — `fatma@signalatlas.id`):** Tampilkan ke-4 tab visualisasi di `/analitik` (Peringkat DCI, Tren 10 Tahun, Radar 2 Negara, dan Statistik Laporan Publik hasil demo), tunjukkan pengelolaan `/indikator`, lalu tutup dengan membuka **Ringkasan Eksekutif Cetak/PDF** (`/pusat-data/ringkasan-cetak`).

---

## 4.2 Cara Kerja Deployment Serverless di Vercel
* **Konfigurasi Routing:** [`vercel.json`](vercel.json) melayani aset statis `/assets/*` langsung dari Edge CDN dan meneruskan route dinamis Laravel ke runtime PHP Serverless [`api/index.php`](api/index.php).
* **Penyesuaian Lingkungan Read-Only:** Di [`api/index.php` baris 11–56](api/index.php#L11-L56) dan [`bootstrap/app.php` baris 28–30](bootstrap/app.php#L28-L30), sistem mengarahkan direktori *cache* dan kompilasi Blade ke `/tmp/storage` serta menggunakan `SESSION_DRIVER=cookie`.
* **Mode Database Ganda:** Mendukung koneksi **Cloud Database Permanen** (PostgreSQL/MySQL seperti Neon atau Supabase melalui Environment Variables Vercel) agar input dari 5 perangkat tersimpan permanen, disertai *automatic fallback* ke `/tmp/database.sqlite` apabila variabel cloud belum disetel.

---

## 4.3 Antisipasi Pertanyaan Penguji (Q&A Teknis)

| Pertanyaan Penguji | Poin Jawaban & Bukti Kode |
|---|---|
| **Bagaimana sistem menangkal SQL Injection, CSRF, dan XSS?** | **SQL Injection** dicegah melalui *parameter binding* Eloquent ORM. **CSRF** dilindungi token `@csrf` dan header `X-CSRF-TOKEN` di [`public/assets/js/app.js`](public/assets/js/app.js#L43). **XSS** pada teks laporan WYSIWYG disaring di server menggunakan `DOMDocument` whitelist pada [`Report::sanitizeHtml()`](app/Models/Report.php#L125-L212). |
| **Apakah pembatasan peran hanya menyembunyikan menu di tampilan?** | Tidak. Seluruh route staf dan admin diproteksi di sisi server oleh middleware [`EnsureRole`](app/Http/Middleware/EnsureRole.php#L15-L34). Jika Kontributor mengetik paksa URL `/verifikasi` atau `/pengguna`, server menolak dengan status **HTTP 403 Forbidden**. |
| **Apakah fitur Speed Test benar-benar mengukur jaringan ke server?** | Ya. [`SpeedTestController`](app/Http/Controllers/SpeedTestController.php#L20-L50) melayani endpoint ping tanpa cache, *streaming* biner acak `StreamedResponse` untuk diukur `ReadableStream` browser, dan endpoint penerima payload biner untuk uji unggah. |
| **Bagaimana jika file CSV yang diimpor memiliki baris yang datanya salah?** | [`DataCenterController`](app/Http/Controllers/DataCenterController.php#L182-L270) memvalidasi per baris dengan `Validator::make()`. Baris yang salah dilewati dan dilaporkan nomor barisnya, sementara baris valid tetap diproses. Tersedia juga mode **Dry-Run** berbasis `DB::rollBack()`. |
| **Mengapa skor wilayah berkurang setelah proyek disetujui?** | [`PriorityService::analyse()`](app/Services/PriorityService.php#L67) membedakan `gap_score` murni dengan `effective_score` (dikali `0.5` saat ada proyek aktif) agar simulasi anggaran memprioritaskan wilayah kritis lain yang belum memiliki proyek berjalan. |
| **Bagaimana cara tabel laporan & verifikasi berubah tanpa reload halaman?** | Memanfaatkan `fetch()` API dan respons kondisional `$request->expectsJson()` di Laravel Controller serta pembaruan parsial DOM (`DOMParser` + `history.pushState` pada [`reports/index.blade.php`](resources/views/reports/index.blade.php#L102-L143)), menghadirkan pengalaman cepat setara SPA di atas arsitektur Blade. |
