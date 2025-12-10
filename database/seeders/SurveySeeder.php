<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        // Skala nilai
        $skala = [
            'Sangat sesuai' => 4, 'Sesuai' => 3, 'Kurang sesuai' => 2, 'Tidak sesuai' => 1,
            'Sangat mudah' => 4, 'Mudah' => 3, 'Kurang mudah' => 2, 'Tidak mudah' => 1,
            'Sangat cepat' => 4, 'Cepat' => 3, 'Kurang cepat' => 2, 'Tidak cepat' => 1,
            'Gratis' => 4, 'Murah' => 3, 'Cukup mahal' => 2, 'Mahal' => 1,
            'Sangat kompeten' => 4, 'Kompeten' => 3, 'Kurang kompeten' => 2, 'Tidak kompeten' => 1,
            'Sangat sopan dan ramah' => 4, 'Sopan dan ramah' => 3, 'Kurang sopan' => 2, 'Tidak sopan' => 1,
            'Sangat Baik' => 4, 'Baik' => 3, 'Cukup' => 2, 'Kurang' => 1,
            'Dikelola dengan baik' => 4, 'Cukup baik' => 3, 'Kurang baik' => 2, 'Tidak baik' => 1,
        ];

        // Pertanyaan
        $pertanyaan = [
            "Bagaimana pendapat Saudara tentang kesesuaian persyaratan pelayanan dengan jenis pelayanannya?",
            "Bagaimana pemahaman Saudara tentang kemudahan prosedur pelayanan di unit ini?",
            "Bagaimana pendapat Saudara tentang kecepatan waktu dalam memberikan pelayanan?",
            "Bagaimana pendapat Saudara tentang kewajaran biaya\\/tarif dalam pelayanan?",
            "Bagaimana pendapat Saudara tentang kesesuaian produk pelayanan antara yang tercantum dalam standar pelayanan dengan hasil yang diberikan?",
            "Bagaimana pendapat Saudara tentang kompetensi\\/kemampuan petugas dalam pelayanan?",
            "Bagaimana pendapat Saudara tentang perilaku petugas dalam pelayanan terkait kesopanan dan keramahan?",
            "Bagaimana pendapat Saudara tentang kualitas sarana dan prasarana?",
            "Bagaimana pendapat Saudara tentang penanganan pengaduan pengguna layanan?",
            "Kritik \\/ Saran (isian bebas)"
        ];

        // Bidang layanan contoh
        $listBidang = [
            "Bagian Tata Usaha",
            "Pelayanan Umum",
            "Pelayanan Publik",
            "Bidang Data & Informasi",
            "Bidang Fasilitasi",
        ];

        // Pilihan jawaban berdasarkan kategori (biar lebih realistis)
        $kategoriJawaban = [
            0 => ["Sangat sesuai", "Sesuai", "Kurang sesuai", "Tidak sesuai"],
            1 => ["Sangat mudah", "Mudah", "Kurang mudah", "Tidak mudah"],
            2 => ["Sangat cepat", "Cepat", "Kurang cepat", "Tidak cepat"],
            3 => ["Gratis", "Murah", "Cukup mahal", "Mahal"],
            4 => ["Sangat sesuai", "Sesuai", "Kurang sesuai", "Tidak sesuai"],
            5 => ["Sangat kompeten", "Kompeten", "Kurang kompeten", "Tidak kompeten"],
            6 => ["Sangat sopan dan ramah", "Sopan dan ramah", "Kurang sopan", "Tidak sopan"],
            7 => ["Sangat Baik", "Baik", "Cukup", "Kurang"],
            8 => ["Dikelola dengan baik", "Cukup baik", "Kurang baik", "Tidak baik"],
        ];

        for ($i = 1; $i <= 100; $i++) {

            // Generate jawaban acak
            $jawabanArray = [];
            foreach ($pertanyaan as $index => $tanya) {

                if ($index === 9) { 
                    // pertanyaan bebas
                    $jawabanArray[$tanya] = [
                        "jawaban" => null,
                        "nilai" => 0
                    ];
                } else {
                    $opsi = $kategoriJawaban[$index];
                    $pilih = $opsi[array_rand($opsi)];

                    $jawabanArray[$tanya] = [
                        "jawaban" => $pilih,
                        "nilai" => $skala[$pilih] ?? 0
                    ];
                }
            }

            // Encode JSON dengan ESCAPE seperti phpMyAdmin
            $jawabanFinal = json_encode($jawabanArray);
            $jawabanFinal = '"' . addslashes($jawabanFinal) . '"';

            DB::table('surveys')->insert([
                "antrian_id"     => $i,
                "nomor_antrian"  => str_pad($i, 3, '0', STR_PAD_LEFT),
                "nama_responden" => Str::random(10),
                "no_hp_wa"       => "08" . rand(100000000, 999999999),
                "usia"           => rand(17, 60),
                "jenis_kelamin"  => rand(0, 1) ? "Laki-laki" : "Perempuan",
                "pendidikan"     => "Lainnya",
                "pekerjaan"      => "Pelajar/Mahasiswa",
                "bidang"         => $listBidang[array_rand($listBidang)],
                "tanggal"        => "2025-12-05",
                "jawaban"        => $jawabanFinal,
                "saran"          => null,
                "created_at"     => now(),
                "updated_at"     => now(),
            ]);
        }
    }
}
