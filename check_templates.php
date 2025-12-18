<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Survey Templates & Questions ===\n\n";

$templates = App\Models\SurveyTemplate::with('questions')->get();

foreach ($templates as $t) {
    echo "Template ID: {$t->id}\n";
    echo "Nama: {$t->nama}\n";
    echo "Versi: {$t->versi}\n";
    echo "Active: " . ($t->is_active ? 'Yes' : 'No') . "\n";
    echo "Total Questions: {$t->questions->count()}\n";

    $questionsWithUnsur = $t->questions->whereNotNull('kode_unsur');
    echo "Questions with kode_unsur (U1-U9): {$questionsWithUnsur->count()}\n";

    if ($questionsWithUnsur->count() > 0) {
        echo "  Kode Unsur: ";
        echo $questionsWithUnsur->pluck('kode_unsur')->implode(', ');
        echo "\n";
    }

    echo "---\n\n";
}

echo "\n=== Important for Statistics ===\n\n";
echo "Untuk perhitungan IKM, template harus punya 9 pertanyaan dengan kode_unsur U1-U9.\n";
echo "Template baru yang Anda buat sudah punya kode unsur?\n";
