<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\InfrastructureProject;
use App\Models\Region;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/peta', '/peta/data', '/analitik', '/speedtest', '/speedtest/ping', '/masuk', '/daftar'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_authenticated_pages_render_for_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        foreach (['/dashboard', '/laporan', '/laporan/create', '/verifikasi', '/prioritas', '/proyek', '/proyek/baru', '/indikator', '/indikator/create', '/pusat-data', '/pusat-data/ringkasan-cetak', '/log-aktivitas', '/pengguna', '/notifikasi', '/profil'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_contributor_cannot_access_staff_pages(): void
    {
        $contributor = User::where('role', 'contributor')->first();
        $this->actingAs($contributor)->get('/verifikasi')->assertForbidden();
        $this->actingAs($contributor)->get('/pengguna')->assertForbidden();
    }

    public function test_full_business_flow_report_to_resolved(): void
    {
        $contributor = User::where('role', 'contributor')->first();
        $analyst = User::where('role', 'analyst')->first();
        $admin = User::where('role', 'admin')->first();
        $region = Region::whereHas('country', fn ($q) => $q->where('iso3', 'TLS'))->where('name', 'Oecusse')->first();

        // 1. Kontributor mengirim laporan blank spot
        $this->actingAs($contributor)->post('/laporan', [
            'region_id' => $region->id,
            'provider' => 'Telin',
            'technology' => 'none',
            'issue_type' => 'blank_spot',
            'description' => 'Tidak ada sinyal sama sekali di seluruh desa perbatasan.',
        ])->assertRedirect();

        $report = Report::latest('id')->first();
        $this->assertSame('critical', $report->severity_level);
        $this->assertSame(100, $report->severity_score);

        // 2. Analis memverifikasi laporan → poin kontributor bertambah
        $pointsBefore = $contributor->fresh()->points;
        $this->actingAs($analyst)->post(route('verification.verify', $report))->assertRedirect();
        $this->assertSame('verified', $report->fresh()->status);
        $this->assertSame($pointsBefore + config('signal.points.verified'), $contributor->fresh()->points);

        // 3. Analis mengusulkan proyek di wilayah tersebut
        $this->actingAs($analyst)->post('/proyek', [
            'region_id' => $region->id,
            'title' => 'VSAT Oecusse',
            'type' => 'satellite',
            'budget_usd' => 60000,
            'beneficiaries' => 2000,
            'target_date' => now()->addMonths(6)->format('Y-m-d'),
        ])->assertRedirect();
        $project = InfrastructureProject::latest('id')->first();

        // 4. Admin menyetujui → laporan otomatis ditautkan, lalu dikerjakan → progres 100% menyelesaikan laporan
        $this->actingAs($admin)->post(route('projects.transition', $project), ['status' => 'approved']);
        $this->assertSame($project->id, $report->fresh()->infrastructure_project_id);

        $this->actingAs($analyst)->post(route('projects.transition', $project), ['status' => 'in_progress']);
        $this->actingAs($analyst)->post(route('projects.progress', $project), ['progress' => 100]);

        $this->assertSame('completed', $project->fresh()->status);
        $this->assertSame('resolved', $report->fresh()->status);
    }

    public function test_csv_import_and_export(): void
    {
        $admin = User::where('role', 'admin')->first();
        $csv = "iso3,year,internet_users_pct,mobile_broadband_per100,fixed_broadband_per100,avg_mobile_speed_mbps,avg_fixed_speed_mbps,data_price_usd_gb,data_source\nIDN,2025,76.5,121,6.1,33.2,36.4,0.39,Test Import\n";
        $file = UploadedFile::fake()->createWithContent('indikator.csv', $csv);

        $this->actingAs($admin)->post(route('data.import', 'indicators'), ['file' => $file, 'mode' => 'upsert'])->assertRedirect();
        $this->assertDatabaseHas('indicators', ['country_id' => Country::where('iso3', 'IDN')->value('id'), 'year' => 2025]);
        $this->actingAs($admin)->get(route('reports.export'))->assertOk();
    }
}
