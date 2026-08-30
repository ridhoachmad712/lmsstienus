<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DecryptBackup extends Command
{
    protected $signature = 'lms:decrypt-backup {source : Berkas .enc} {destination : Tujuan .sql/.sqlite}';

    protected $description = 'Dekripsi salinan backup offsite menggunakan BACKUP_ENCRYPTION_KEY';

    public function handle(): int
    {
        $source = (string) $this->argument('source');
        $destination = (string) $this->argument('destination');
        $key = (string) config('backup.encryption_key');
        if ($key === '' || ! is_file($source) || file_exists($destination)) {
            $this->error('Pastikan key tersedia, source ada, dan destination belum ada.');

            return self::FAILURE;
        }

        $payload = File::get($source);
        if (! str_starts_with($payload, 'LMSBK1') || strlen($payload) < 34) {
            $this->error('Format backup terenkripsi tidak valid.');

            return self::FAILURE;
        }
        $iv = substr($payload, 6, 12);
        $tag = substr($payload, 18, 16);
        $cipher = substr($payload, 34);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            $this->error('Dekripsi gagal; key salah atau berkas rusak.');

            return self::FAILURE;
        }
        File::put($destination, $plain);
        @chmod($destination, 0600);
        $this->info('Backup didekripsi ke '.$destination);

        return self::SUCCESS;
    }
}
