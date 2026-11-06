<?php

namespace App\Console\Commands;

use App\Support\PelanggaranGuru;
use Illuminate\Console\Command;

class PindaiPelanggaranGuru extends Command
{
    protected $signature = 'pelanggaran:pindai';

    protected $description = 'Catat pelanggaran guru yang tidak mengisi jurnal lebih dari batas jam setelah jadwal selesai';

    public function handle(): int
    {
        $baru = PelanggaranGuru::pindai(true);
        $this->info("Pelanggaran baru tercatat: {$baru}");
        return self::SUCCESS;
    }
}
