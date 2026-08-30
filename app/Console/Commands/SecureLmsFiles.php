<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SecureLmsFiles extends Command
{
    protected $signature = 'lms:secure-files {--delete-source : Hapus salinan publik setelah isinya terverifikasi}';

    protected $description = 'Salin materi dan pengumpulan lama dari disk publik ke disk privat';

    public function handle(): int
    {
        $paths = Material::whereNotNull('path')->pluck('path')
            ->merge(Submission::whereNotNull('file_path')->pluck('file_path'))
            ->filter()->unique();
        $copied = $missing = $deleted = 0;

        foreach ($paths as $path) {
            $path = (string) $path;
            if (! Storage::disk('local')->exists($path)) {
                if (! Storage::disk('public')->exists($path)) {
                    $this->warn("Tidak ditemukan: {$path}");
                    $missing++;

                    continue;
                }
                $stream = Storage::disk('public')->readStream($path);
                if ($stream === false || ! Storage::disk('local')->writeStream($path, $stream)) {
                    is_resource($stream) && fclose($stream);
                    $this->error("Gagal menyalin: {$path}");

                    return self::FAILURE;
                }
                is_resource($stream) && fclose($stream);
                $copied++;
            }

            if (Storage::disk('public')->exists($path)
                && Storage::disk('local')->size($path) !== Storage::disk('public')->size($path)) {
                $this->error("Verifikasi ukuran gagal: {$path}");

                return self::FAILURE;
            }

            if ($this->option('delete-source') && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deleted++;
            }
        }

        $this->info("Selesai: {$copied} disalin, {$deleted} salinan publik dihapus, {$missing} tidak ditemukan.");

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
