<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Indicator;
use Illuminate\Database\Seeder;

/**
 * Data referensi: negara ASEAN + Timor-Leste, wilayah, dan indikator 2015–2024.
 * Nilai indikator adalah data ilustratif yang dibulatkan & diinterpolasi
 * dari publikasi World Bank WDI, ITU, dan Ookla Speedtest Global Index.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            // name, iso3, iso2, capital, population, lat, lng
            ['Indonesia', 'IDN', 'ID', 'Jakarta', 281_600_000, -2.5, 118.0],
            ['Malaysia', 'MYS', 'MY', 'Kuala Lumpur', 35_100_000, 4.2, 101.9],
            ['Filipina', 'PHL', 'PH', 'Manila', 115_800_000, 12.9, 121.8],
            ['Singapura', 'SGP', 'SG', 'Singapura', 5_920_000, 1.35, 103.82],
            ['Thailand', 'THA', 'TH', 'Bangkok', 71_700_000, 15.87, 100.99],
            ['Vietnam', 'VNM', 'VN', 'Hanoi', 100_300_000, 14.06, 108.28],
            ['Brunei', 'BRN', 'BN', 'Bandar Seri Begawan', 455_000, 4.54, 114.73],
            ['Kamboja', 'KHM', 'KH', 'Phnom Penh', 17_400_000, 12.57, 104.99],
            ['Laos', 'LAO', 'LA', 'Vientiane', 7_660_000, 19.86, 102.5],
            ['Myanmar', 'MMR', 'MM', 'Naypyidaw', 54_500_000, 21.91, 95.96],
            ['Timor-Leste', 'TLS', 'TL', 'Dili', 1_400_000, -8.87, 125.73],
        ];

        $regions = [
            'IDN' => [
                ['DKI Jakarta', 'urban', 10_680_000, -6.2088, 106.8456],
                ['Jawa Barat', 'suburban', 49_900_000, -6.9175, 107.6191],
                ['Nusa Tenggara Timur', 'rural', 5_500_000, -10.1772, 123.6070],
                ['Papua', 'remote', 4_300_000, -4.2699, 138.0804],
                ['Kalimantan Barat', 'rural', 5_500_000, -0.0263, 109.3425],
                ['Maluku', 'remote', 1_900_000, -3.6954, 128.1814],
            ],
            'MYS' => [
                ['Kuala Lumpur', 'urban', 2_000_000, 3.1390, 101.6869],
                ['Sabah', 'rural', 3_400_000, 5.9788, 116.0753],
                ['Sarawak', 'rural', 2_500_000, 1.5533, 110.3592],
                ['Kelantan', 'suburban', 1_800_000, 6.1254, 102.2381],
            ],
            'PHL' => [
                ['Metro Manila', 'urban', 13_480_000, 14.5995, 120.9842],
                ['Cebu', 'suburban', 3_300_000, 10.3157, 123.8854],
                ['Mindanao', 'rural', 26_200_000, 7.1907, 125.4553],
                ['Palawan', 'remote', 1_250_000, 9.8349, 118.7384],
                ['Eastern Visayas', 'rural', 4_500_000, 11.2443, 125.0039],
            ],
            'SGP' => [
                ['Central Region', 'urban', 920_000, 1.2897, 103.8501],
                ['Jurong West', 'urban', 260_000, 1.3404, 103.7090],
                ['Pulau Ubin', 'remote', 40, 1.4044, 103.9625],
            ],
            'THA' => [
                ['Bangkok', 'urban', 10_500_000, 13.7563, 100.5018],
                ['Chiang Mai', 'suburban', 1_800_000, 18.7883, 98.9853],
                ['Mae Hong Son', 'remote', 285_000, 19.3020, 97.9654],
                ['Ubon Ratchathani', 'rural', 1_880_000, 15.2287, 104.8564],
            ],
            'VNM' => [
                ['Hanoi', 'urban', 8_430_000, 21.0278, 105.8342],
                ['Ho Chi Minh City', 'urban', 9_390_000, 10.8231, 106.6297],
                ['Ha Giang', 'remote', 880_000, 22.8233, 104.9836],
                ['Can Tho', 'suburban', 1_250_000, 10.0452, 105.7469],
            ],
            'BRN' => [
                ['Bandar Seri Begawan', 'urban', 100_000, 4.9031, 114.9398],
                ['Temburong', 'remote', 11_000, 4.6200, 115.1400],
            ],
            'KHM' => [
                ['Phnom Penh', 'urban', 2_300_000, 11.5564, 104.9282],
                ['Siem Reap', 'suburban', 1_010_000, 13.3671, 103.8448],
                ['Ratanakiri', 'remote', 205_000, 13.7394, 107.0047],
            ],
            'LAO' => [
                ['Vientiane', 'urban', 950_000, 17.9757, 102.6331],
                ['Luang Prabang', 'suburban', 430_000, 19.8833, 102.1347],
                ['Phongsaly', 'remote', 180_000, 21.6819, 102.1090],
            ],
            'MMR' => [
                ['Yangon', 'urban', 7_400_000, 16.8409, 96.1735],
                ['Mandalay', 'urban', 1_700_000, 21.9588, 96.0891],
                ['Chin State', 'remote', 480_000, 22.0087, 93.5813],
                ['Rakhine', 'rural', 3_200_000, 20.1500, 92.9000],
            ],
            'TLS' => [
                ['Dili', 'urban', 280_000, -8.5569, 125.5603],
                ['Baucau', 'rural', 133_000, -8.4667, 126.4500],
                ['Oecusse', 'remote', 72_000, -9.2000, 124.3500],
            ],
        ];

        // iso3 => [users2015, users2024, mobileBB2024, fixedBB2024, mobileSpeed2024, fixedSpeed2024, price2024]
        $anchors = [
            'IDN' => [22.0, 72.8, 118, 5.4, 28.5, 32.1, 0.42],
            'MYS' => [71.1, 98.0, 132, 12.1, 104.5, 112.0, 0.48],
            'PHL' => [36.9, 73.5, 76, 8.1, 31.2, 94.6, 1.15],
            'SGP' => [79.0, 94.4, 158, 27.3, 118.0, 285.0, 0.88],
            'THA' => [39.3, 90.9, 112, 18.2, 61.4, 231.0, 0.62],
            'VNM' => [45.0, 85.4, 96, 22.4, 59.8, 152.3, 0.29],
            'BRN' => [71.2, 99.0, 144, 18.4, 112.0, 88.0, 1.48],
            'KHM' => [18.0, 60.2, 94, 2.1, 29.4, 34.2, 0.21],
            'LAO' => [18.2, 66.0, 62, 2.4, 24.6, 39.5, 0.52],
            'MMR' => [10.9, 58.5, 81, 1.2, 24.1, 19.8, 0.41],
            'TLS' => [13.4, 40.1, 56, 0.1, 10.2, 7.9, 2.48],
        ];

        foreach ($countries as [$name, $iso3, $iso2, $capital, $population, $lat, $lng]) {
            $country = Country::updateOrCreate(['iso3' => $iso3], compact('name', 'iso2', 'capital', 'population') + [
                'latitude' => $lat, 'longitude' => $lng,
            ]);

            foreach ($regions[$iso3] as [$rName, $type, $pop, $rLat, $rLng]) {
                $country->regions()->updateOrCreate(['name' => $rName], [
                    'area_type' => $type, 'population' => $pop, 'latitude' => $rLat, 'longitude' => $rLng,
                ]);
            }

            [$u0, $u1, $mb, $fb, $ms, $fs, $price] = $anchors[$iso3];
            foreach (range(2015, 2024) as $year) {
                $t = ($year - 2015) / 9;
                $ease = $t ** 0.82;
                $wiggle = sin(crc32($iso3.$year)) * 0.6;

                Indicator::updateOrCreate(['country_id' => $country->id, 'year' => $year], [
                    'internet_users_pct' => round(min(100, $u0 + ($u1 - $u0) * $ease + ($year < 2024 ? $wiggle : 0)), 2),
                    'mobile_broadband_per100' => round($mb * (0.38 + 0.62 * $ease), 2),
                    'fixed_broadband_per100' => round($fb * (0.30 + 0.70 * $ease), 2),
                    'avg_mobile_speed_mbps' => round($ms * (0.12 + 0.88 * $t ** 1.35), 2),
                    'avg_fixed_speed_mbps' => round($fs * (0.10 + 0.90 * $t ** 1.3), 2),
                    'data_price_usd_gb' => round($price * (3.2 - 2.2 * $ease), 2),
                    'data_source' => 'World Bank WDI · ITU · Ookla (ilustratif, dibulatkan)',
                ]);
            }
        }
    }
}
