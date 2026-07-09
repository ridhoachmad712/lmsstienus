<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RecalcAcademicCache extends Command
{
    protected $signature = 'lms:recalc-ipk';

    protected $description = 'Hitung ulang cache akademik (IPK/SKS/IPS) semua mahasiswa';

    public function handle(): int
    {
        $count = 0;
        User::where('role', User::ROLE_MAHASISWA)->cursor()->each(function (User $u) use (&$count) {
            $u->refreshAcademicCache();
            $count++;
        });

        $this->info("Cache akademik diperbarui untuk {$count} mahasiswa.");

        return self::SUCCESS;
    }
}
