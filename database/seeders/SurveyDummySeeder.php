<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Survey;
use Faker\Factory as Faker;

class SurveyDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $bidangList = [
            'Bagian Tata Usaha',
            'Bidang Bimbingan Masyarakat Islam',
            'Bimas Katolik',
            'Bimas Kristen',
            'Bimas Hindu',
            'Bimas Buddha',
        ];

        $pertanyaan = [
            "Bagaimana pendapat Saudara tentang kesesuaian persyaratan pelayanan dengan jenis pelayanannya?",
            "Bagaimana pemahaman Saudara tentang kemudahan prosedur pelayanan di unit ini?",
            "Bagaimana pendapat Saudara tentang kecepatan waktu dalam memberikan pelayanan?",
            "Bagaimana pendapat Saudara tentang kewajaran biaya/tarif dalam pelayanan?",
            "Bagaimana pendapat Saudara tentang kesesuaian produk pelayanan antara yang tercantum dalam standar pelayanan dengan hasil yang diberikan?",
            "Bagaimana pendapat Saudara tentang kompetensi/kemampuan petugas dalam pelayanan?",
            "Bagaimana pendapat Saudara tentang perilaku petugas dalam pelayanan terkait kesopanan dan keramahan?",
            "Bagaimana pendapat Saudara tentang kualitas sarana dan prasarana?",
            "Bagaimana pendapat Saudara tentang penanganan pengaduan pengguna layanan?",
            "Kritik / Saran (isian bebas)"
        ];

        $jawabanSkala = [
            1 => 'Kurang',
            2 => 'Cukup',
            3 => 'Baik',
            4 => 'Sangat Baik'
        ];

        for ($i = 1; $i <= 100; $i++) {
            $jawabanArray = [];

            foreach ($pertanyaan as $q) {
                if ($q === "Kritik / Saran (isian bebas)") {
                    $jawabanArray[$q] = ['jawaban' => null, 'nilai' => 0];
                } else {
                    $nilai = rand(1, 4);
                    $jawabanArray[$q] = [
                        'jawaban' => $jawabanSkala[$nilai],
                        'nilai' => $nilai
                    ];
                }
            }

            Survey::create([
                'nomor_antrian'  => str_pad($i, 3, '0', STR_PAD_LEFT),
                'nama_responden' => $faker->name,
                'no_hp_wa'       => '0812' . $faker->numerify('#######'),
                'usia'           => $faker->numberBetween(18, 60),
                'jenis_kelamin'  => $faker->randomElement(['Laki-laki', 'Perempuan']),
                'pendidikan'     => $faker->randomElement(['SMA', 'D3', 'S1', 'S2']),
                'pekerjaan'      => $faker->randomElement(['Pegawai Negeri', 'Guru', 'Wiraswasta', 'Mahasiswa']),
                'bidang'         => $faker->randomElement($bidangList),
                'tanggal'        => $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
                'jawaban'        => json_encode($jawabanArray),
                'saran'          => $faker->sentence,
            ]);
        }
    }
}
