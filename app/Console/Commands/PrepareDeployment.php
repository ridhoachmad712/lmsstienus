<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PrepareDeployment extends Command
{
    protected $signature = 'lms:prepare-deployment';

    protected $description = 'Siapkan URL /siakad untuk source SIAKAD lama dalam satu repository';

    public function handle(): int
    {
        $target = base_path('siakad-legacy');
        $link = public_path('siakad');
        if (! is_dir($target)) {
            $this->error('Folder siakad-legacy tidak ditemukan.');

            return self::FAILURE;
        }
        if (is_link($link)) {
            $this->info('Tautan public/siakad sudah tersedia.');

            return self::SUCCESS;
        }
        if (file_exists($link)) {
            $this->error('public/siakad sudah ada tetapi bukan symlink; tidak diubah.');

            return self::FAILURE;
        }
        if (! @symlink($target, $link)) {
            $this->error('Hosting menolak symlink. Arahkan alias /siakad ke '.$target.' melalui panel hosting.');

            return self::FAILURE;
        }
        $this->info('URL /siakad diarahkan ke '.$target);

        return self::SUCCESS;
    }
}
