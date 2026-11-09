<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\InfrastructureProject;
use App\Models\ProjectUpdate;
use App\Models\Region;
use App\Models\Report;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\PriorityService;
use App\Services\SeverityCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data demo: akun per peran, laporan publik, proyek beserta timeline-nya.
 */
class DemoDataSeeder extends Seeder
{
    private const PROVIDERS = [
        'IDN' => ['Telkomsel', 'Indosat Ooredoo Hutchison', 'XL Axiata', 'Smartfren', 'IndiHome', 'Biznet'],
        'MYS' => ['Maxis', 'CelcomDigi', 'Unifi'],
        'PHL' => ['Globe Telecom', 'Smart (PLDT)', 'DITO'],
        'SGP' => ['Singtel', 'StarHub'],
        'THA' => ['AIS', 'True'],
        'VNM' => ['Viettel', 'VNPT', 'Mobifone'],
        'BRN' => ['Lainnya'],
        'KHM' => ['Metfone', 'Lainnya'],
        'LAO' => ['Unitel', 'Lainnya'],
        'MMR' => ['MPT', 'Lainnya'],
        'TLS' => ['Telin', 'Lainnya'],
    ];

    private const DESCRIPTIONS = [
        'blank_spot' => [
            'Tidak ada sinyal sama sekali di desa kami, warga harus naik ke bukit untuk menelepon.',
            'Blank spot total di sepanjang jalan poros antar kecamatan, sangat berbahaya saat darurat.',
            'Sekolah dan puskesmas tidak bisa mengakses internet karena tidak ada jaringan.',
        ],
        'outage' => [
            'Jaringan putus total sejak kemarin sore, belum ada pemulihan dari operator.',
            'Sering terjadi gangguan total ketika hujan deras, layanan mati berjam-jam.',
            'Internet mati setiap malam akibat pemadaman listrik di menara BTS.',
        ],
        'unstable' => [
            'Sinyal naik turun antara 3G dan 4G, panggilan video selalu terputus.',
            'Koneksi tidak stabil saat jam kerja, rapat daring sering terputus.',
            'Paket data hilang-hilangan, latensi melonjak tiba-tiba.',
        ],
        'slow' => [
            'Kecepatan sangat lambat pada jam sibuk 19.00–22.00, tidak bisa streaming kelas daring.',
            'Unduhan materi kuliah berjalan sangat lambat meski sinyal penuh.',
            'Internet rumah lambat dibanding paket yang dijanjikan provider.',
        ],
        'expensive' => [
            'Harga paket data terlalu mahal dibanding pendapatan warga desa.',
            'Satu-satunya provider di daerah ini mematok harga tinggi tanpa alternatif.',
        ],
    ];

    public function run(): void
    {
        mt_srand(2026);

        // ---------------------------------------------------------- Users
        $admin = User::create(['name' => 'Admin SignalAtlas', 'email' => 'admin@signalatlas.id', 'password' => 'password', 'role' => 'admin', 'institution' => 'Sekretariat Proyek SignalAtlas', 'last_login_at' => now()]);
        $analysts = collect([
            User::create(['name' => 'Rania Pratiwi', 'email' => 'analis@signalatlas.id', 'password' => 'password', 'role' => 'analyst', 'institution' => 'Pusat Kajian Digital ASEAN', 'last_login_at' => now()->subHours(3)]),
            User::create(['name' => 'Bima Santoso', 'email' => 'bima@signalatlas.id', 'password' => 'password', 'role' => 'analyst', 'institution' => 'Kementerian Kominfo (mitra)', 'last_login_at' => now()->subDay()]),
        ]);
        $demo = User::create(['name' => 'Kontributor Demo', 'email' => 'kontributor@signalatlas.id', 'password' => 'password', 'role' => 'contributor', 'institution' => 'IPB University', 'last_login_at' => now()->subHours(6)]);

        $names = ['Siti Rahmawati', 'Nguyen Van An', 'Maria Santos', 'Ahmad Faiz', 'Somchai Prasert', 'Dewi Lestari',
            'Lim Wei Jie', 'Sokha Chea', 'Aung Ko Ko', 'Juan Dela Cruz', 'Putri Ayu', 'Khamla Vong'];
        $contributors = collect($names)->map(fn ($n, $i) => User::create([
            'name' => $n,
            'email' => strtolower(str_replace(' ', '.', $n)).'@mail.test',
            'password' => 'password',
            'role' => 'contributor',
            'last_login_at' => now()->subDays(mt_rand(0, 30)),
        ]))->push($demo);

        // ---------------------------------------------------------- Reports
        $severity = new SeverityCalculator;
        $regions = Region::with('country')->get();
        $weighted = $regions->flatMap(fn ($r) => array_fill(0, ['remote' => 6, 'rural' => 5, 'suburban' => 3, 'urban' => 2][$r->area_type], $r));
        $seq = [];

        for ($i = 0; $i < 190; $i++) {
            /** @var Region $region */
            $region = $weighted[mt_rand(0, $weighted->count() - 1)];
            [$tech, $issue, $down, $up, $lat] = $this->profile($region->area_type);
            $created = Carbon::now()->subDays(mt_rand(0, 220))->subMinutes(mt_rand(0, 1440));
            $calc = $severity->calculate(['download_mbps' => $down, 'latency_ms' => $lat, 'issue_type' => $issue, 'technology' => $tech]);
            $spread = $region->country->iso3 === 'SGP' ? 0.015 : 0.35;

            $roll = mt_rand(1, 100);
            $status = $created->gt(now()->subDays(12)) ? ($roll <= 70 ? 'pending' : 'verified')
                : ($roll <= 12 ? 'rejected' : ($roll <= 22 ? 'pending' : 'verified'));

            $prefix = 'RPT-'.$created->format('ym').'-';
            $seq[$prefix] = ($seq[$prefix] ?? 0) + 1;
            $reporter = $contributors[mt_rand(0, $contributors->count() - 1)];
            $reviewer = mt_rand(0, 3) ? $analysts->random() : $admin;
            $verifiedAt = $status !== 'pending' ? $created->copy()->addHours(mt_rand(2, 72)) : null;

            Report::create([
                'code' => $prefix.str_pad((string) $seq[$prefix], 4, '0', STR_PAD_LEFT),
                'user_id' => $reporter->id,
                'region_id' => $region->id,
                'provider' => collect(self::PROVIDERS[$region->country->iso3])->random(),
                'technology' => $tech,
                'issue_type' => $issue,
                'download_mbps' => $down,
                'upload_mbps' => $up,
                'latency_ms' => $lat,
                'description' => collect(self::DESCRIPTIONS[$issue])->random(),
                'latitude' => round($region->latitude + (mt_rand(-1000, 1000) / 1000) * $spread, 6),
                'longitude' => round($region->longitude + (mt_rand(-1000, 1000) / 1000) * $spread, 6),
                'severity_score' => $calc['severity_score'],
                'severity_level' => $calc['severity_level'],
                'status' => $status,
                'source' => mt_rand(1, 4) === 1 ? 'speedtest' : 'manual',
                'verified_by' => $verifiedAt ? $reviewer->id : null,
                'verified_at' => $verifiedAt,
                'review_note' => $status === 'rejected' ? 'Data tidak dapat dikonfirmasi / duplikat laporan lain.' : ($verifiedAt ? 'Sesuai dengan data cakupan operator.' : null),
                'created_at' => $created,
                'updated_at' => $verifiedAt ?? $created,
            ]);
        }

        // ---------------------------------------------------------- Projects
        $ranked = app(PriorityService::class)->rank()->where('gap_score', '>', 0)->values();
        $plan = ['completed', 'completed', 'completed', 'in_progress', 'in_progress', 'in_progress', 'in_progress',
            'approved', 'approved', 'on_hold', 'proposed', 'proposed', 'proposed', 'rejected'];
        $staff = $analysts->push($admin);

        foreach ($plan as $index => $status) {
            $row = $ranked[$index] ?? null;
            if (! $row) {
                break;
            }
            $region = $row['region'];
            $type = $row['recommended_type'];
            $proposedAt = now()->subDays(mt_rand(60, 200));
            $start = in_array($status, ['in_progress', 'on_hold', 'completed'], true) ? $proposedAt->copy()->addDays(mt_rand(10, 25)) : null;
            $progress = match ($status) {
                'completed' => 100, 'in_progress' => mt_rand(20, 85), 'on_hold' => mt_rand(15, 45), default => 0
            };
            $proposer = $staff->random();

            $project = InfrastructureProject::create([
                'code' => 'PRJ-'.now()->format('Y').'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'region_id' => $region->id,
                'title' => config("signal.project_types.$type").' — '.$region->name,
                'type' => $type,
                'description' => $row['reason'].' Proyek ini disusun berdasarkan '.$row['active_reports'].' laporan terverifikasi dari warga.',
                'budget_usd' => $row['estimated_cost'],
                'status' => $status,
                'progress' => $progress,
                'priority_score' => $row['gap_score'],
                'beneficiaries' => max(50, $row['estimated_beneficiaries']),
                'start_date' => $start,
                'target_date' => ($start ?? $proposedAt)->copy()->addMonths(mt_rand(5, 12)),
                'completed_at' => $status === 'completed' ? now()->subDays(mt_rand(3, 30)) : null,
                'proposed_by' => $proposer->id,
                'approved_by' => in_array($status, ['approved', 'in_progress', 'on_hold', 'completed'], true) ? $admin->id : null,
                'created_at' => $proposedAt,
            ]);

            $timeline = [['proposed', null, 'Proyek diusulkan berdasarkan skor prioritas '.$row['gap_score'], $proposedAt]];
            if ($status === 'rejected') {
                $timeline[] = ['rejected', 'proposed', 'Anggaran tahun berjalan tidak mencukupi.', $proposedAt->copy()->addDays(7)];
            }
            if (in_array($status, ['approved', 'in_progress', 'on_hold', 'completed'], true)) {
                $timeline[] = ['approved', 'proposed', 'Disetujui dalam rapat koordinasi regional.', $proposedAt->copy()->addDays(6)];
            }
            if ($start) {
                $timeline[] = ['in_progress', 'approved', 'Kontraktor mulai bekerja di lapangan.', $start];
            }
            if ($status === 'on_hold') {
                $timeline[] = ['on_hold', 'in_progress', 'Ditunda karena musim hujan & akses jalan terputus.', $start->copy()->addDays(40)];
            }
            if ($status === 'completed') {
                $timeline[] = ['completed', 'in_progress', 'Infrastruktur beroperasi penuh dan diserahterimakan.', $project->completed_at];
            }
            foreach ($timeline as [$to, $from, $note, $at]) {
                ProjectUpdate::create(['infrastructure_project_id' => $project->id, 'user_id' => $to === 'proposed' ? $proposer->id : $admin->id,
                    'from_status' => $from, 'to_status' => $to, 'progress' => $to === 'completed' ? 100 : null, 'note' => $note, 'created_at' => $at]);
            }
            if ($status === 'in_progress') {
                ProjectUpdate::create(['infrastructure_project_id' => $project->id, 'user_id' => $staff->random()->id, 'progress' => $progress,
                    'note' => 'Pemasangan perangkat dan uji coba sinyal berjalan sesuai jadwal.', 'created_at' => now()->subDays(mt_rand(1, 10))]);
            }

            // Tautkan / selesaikan laporan sesuai alur bisnis
            if (in_array($status, ['approved', 'in_progress', 'on_hold', 'completed'], true)) {
                $region->reports()->where('status', 'verified')->whereNull('infrastructure_project_id')->update([
                    'infrastructure_project_id' => $project->id,
                    'status' => $status === 'completed' ? 'resolved' : 'verified',
                    'resolved_at' => $status === 'completed' ? $project->completed_at : null,
                ]);
            }
        }

        // ---------------------------------------------------------- Points
        User::where('role', 'contributor')->get()->each(function (User $u) {
            $verified = $u->reports()->whereIn('status', ['verified', 'resolved'])->count();
            $resolved = $u->reports()->where('status', 'resolved')->count();
            $u->update(['points' => $verified * config('signal.points.verified') + $resolved * config('signal.points.resolved')]);
        });

        // ---------------------------------------------------------- Notifications & logs
        $demo->notify(new SystemNotification('Selamat bergabung di SignalAtlas!', 'Mulai dengan menjalankan Speed Test atau kirim laporan sinyal pertama Anda.', route('speedtest'), 'sparkles', 'success'));
        if ($r = $demo->reports()->where('status', 'resolved')->first()) {
            $demo->notify(new SystemNotification('Masalah Anda terselesaikan 🎉', "Laporan {$r->code} diselesaikan melalui proyek infrastruktur.", route('reports.show', $r), 'party-popper', 'success'));
        }
        $admin->notify(new SystemNotification('Ada laporan menunggu verifikasi', Report::where('status', 'pending')->count().' laporan baru membutuhkan tinjauan.', route('verification.index'), 'inbox', 'info'));

        $logs = [
            [$admin, 'auth.login', 'Masuk ke sistem'],
            [$analysts[0], 'report.verify', 'Memverifikasi 12 laporan wilayah Papua'],
            [$admin, 'project.transition', 'Menyetujui proyek PRJ-'.now()->format('Y').'-008'],
            [$analysts[1], 'indicators.import', 'Mengimpor 22 baru, 0 diperbarui dari indikator-2024.csv'],
            [$demo, 'report.create', 'Membuat laporan baru dari hasil speed test'],
            [$admin, 'user.update', 'Mengubah peran bima@signalatlas.id: contributor → analyst'],
            [$analysts[0], 'report.export', 'Mengekspor 140 laporan ke CSV'],
        ];
        foreach ($logs as $i => [$user, $action, $desc]) {
            ActivityLog::create(['user_id' => $user->id, 'action' => $action, 'description' => $desc, 'ip_address' => '127.0.0.1', 'created_at' => now()->subHours(($i + 1) * 5)]);
        }
    }

    /** Profil teknis acak berdasarkan tipe wilayah: [tech, issue, down, up, latency]. */
    private function profile(string $area): array
    {
        $pick = fn (array $opts) => $opts[mt_rand(0, count($opts) - 1)];

        $tech = match ($area) {
            'remote' => $pick(['none', 'none', '2g', '3g', '3g', '4g']),
            'rural' => $pick(['none', '2g', '3g', '3g', '4g', '4g']),
            'suburban' => $pick(['3g', '4g', '4g', '4g', 'fiber']),
            default => $pick(['4g', '4g', '5g', 'fiber', 'fiber']),
        };

        if ($tech === 'none') {
            return ['none', 'blank_spot', null, null, null];
        }

        $issue = $pick(match ($tech) {
            '2g', '3g' => ['slow', 'slow', 'unstable', 'outage', 'expensive'],
            default => ['slow', 'unstable', 'outage', 'expensive'],
        });

        $base = ['2g' => 0.3, '3g' => 2.5, '4g' => 14, '5g' => 90, 'fiber' => 45][$tech];
        $down = round($base * (mt_rand(30, 160) / 100), 2);
        if ($issue === 'outage') {
            $down = round($down * 0.1, 2);
        }

        return [$tech, $issue, $down, round($down * (mt_rand(15, 45) / 100), 2),
            (int) (['2g' => 420, '3g' => 180, '4g' => 70, '5g' => 25, 'fiber' => 18][$tech] * (mt_rand(60, 180) / 100))];
    }
}
