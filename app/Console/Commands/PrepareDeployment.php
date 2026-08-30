<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareDeployment extends Command
{
    protected $signature = 'lms:prepare-deployment
                            {--copy : Salin source SIAKAD jika hosting tidak mendukung symlink PHP}';

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

        if ($this->option('copy')) {
            return $this->copySiakad($target, $link);
        }

        if (file_exists($link)) {
            $this->error('public/siakad sudah ada tetapi bukan symlink. Jalankan kembali dengan opsi --copy untuk memperbaruinya.');

            return self::FAILURE;
        }

        if (! function_exists('symlink')) {
            $this->warn('Fungsi symlink() dinonaktifkan oleh hosting.');
            $this->line('Jalankan: php artisan lms:prepare-deployment --copy');

            return self::FAILURE;
        }

        if (! @\symlink($target, $link)) {
            $this->error('Hosting menolak symlink. Jalankan kembali dengan opsi --copy.');

            return self::FAILURE;
        }

        $this->info('URL /siakad diarahkan ke '.$target);

        return self::SUCCESS;
    }

    private function copySiakad(string $source, string $destination): int
    {
        if (file_exists($destination) && ! is_dir($destination)) {
            $this->error('public/siakad sudah ada tetapi bukan folder; tidak diubah.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($destination, 0755, true);

        if (! File::copyDirectory($source, $destination)) {
            $this->error('Source SIAKAD gagal disalin ke public/siakad.');

            return self::FAILURE;
        }

        $localConfig = $destination.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'local.php';
        if (is_file($localConfig)) {
            @chmod($localConfig, 0600);
        }

        $this->info('Source SIAKAD berhasil diperbarui di public/siakad (mode copy).');
        $this->warn('Jalankan perintah ini kembali setelah setiap git pull.');

        return self::SUCCESS;
    }
}
