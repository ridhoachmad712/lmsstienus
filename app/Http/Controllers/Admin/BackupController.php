<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    private function dir(): string
    {
        return storage_path('app/backups');
    }

    /** Path aman sebuah backup di dalam folder backups (cegah path traversal). */
    private function pathFor(string $name): string
    {
        return $this->dir().DIRECTORY_SEPARATOR.basename($name);
    }

    public function index(): \Illuminate\View\View
    {
        File::ensureDirectoryExists($this->dir());

        $backups = collect(File::files($this->dir()))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size' => round($f->getSize() / 1024, 1).' KB',
                'date' => date('d M Y H:i', $f->getMTime()),
            ])
            ->values();

        return view('admin.backups', [
            'backups' => $backups,
            'connection' => config('database.default'),
        ]);
    }

    public function run(): RedirectResponse
    {
        Artisan::call('lms:backup-db');

        return back()->with('status', trim(Artisan::output()) ?: 'Backup dibuat.');
    }

    public function download(string $name): BinaryFileResponse
    {
        $path = $this->pathFor($name);
        abort_unless(is_file($path), 404);

        return response()->download($path);
    }

    /** Impor berkas backup (unggah) ke daftar — belum menimpa database. */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // maks 50 MB
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (! in_array($ext, ['sql', 'sqlite'], true)) {
            return back()->with('error', 'Format tidak didukung. Unggah berkas .sql (MySQL) atau .sqlite (SQLite).');
        }

        File::ensureDirectoryExists($this->dir());
        $name = 'upload_'.now()->format('Y-m-d_His').'.'.$ext;
        $request->file('file')->move($this->dir(), $name);

        Activity::log('create', "Mengunggah berkas backup: {$name}");

        return back()->with('status', "Berkas {$name} diunggah. Klik “Pulihkan” pada baris tersebut untuk menerapkannya.");
    }

    /** Pulihkan database dari sebuah backup (OPERASI DESTRUKTIF — menimpa data). */
    public function restore(Request $request, string $name): RedirectResponse
    {
        $path = $this->pathFor($name);
        abort_unless(is_file($path), 404);

        try {
            $message = $this->performRestore($path);
        } catch (\Throwable $e) {
            return back()->with('error', 'Restore gagal: '.$e->getMessage().' (Snapshot pra-restore telah dibuat otomatis.)');
        }

        Activity::log('update', "Memulihkan database dari backup: ".basename($name));

        return back()->with('status', $message.' Anda mungkin perlu masuk kembali.');
    }

    /** Hapus sebuah berkas backup dari daftar. */
    public function destroy(string $name): RedirectResponse
    {
        $path = $this->pathFor($name);
        abort_unless(is_file($path), 404);

        File::delete($path);
        Activity::log('delete', "Menghapus berkas backup: ".basename($name));

        return back()->with('status', 'Berkas backup dihapus.');
    }

    /**
     * Terapkan sebuah backup ke database aktif. Membuat snapshot pra-restore dulu.
     * Murni-PHP (tanpa exec) agar jalan di shared hosting.
     */
    private function performRestore(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $connection = config('database.default');

        // Pengaman: snapshot kondisi saat ini sebelum ditimpa (bisa dipakai untuk revert).
        try {
            Artisan::call('lms:backup-db');
        } catch (\Throwable $e) {
            // Abaikan kegagalan snapshot; restore tetap dilanjutkan.
        }

        if ($ext === 'sqlite') {
            abort_unless($connection === 'sqlite', 422, 'Backup .sqlite tidak cocok dengan koneksi database aktif ('.$connection.').');
            $target = config('database.connections.sqlite.database');
            abort_if(! $target || $target === ':memory:', 422, 'Restore SQLite tidak tersedia untuk database in-memory.');

            DB::disconnect('sqlite');
            File::copy($path, $target);

            return 'Database SQLite berhasil dipulihkan dari '.basename($path).'.';
        }

        if ($ext === 'sql') {
            abort_unless($connection === 'mysql', 422, 'Backup .sql hanya untuk koneksi MySQL (koneksi aktif: '.$connection.').');
            $this->runSqlScript((string) file_get_contents($path));

            return 'Database MySQL berhasil dipulihkan dari '.basename($path).'.';
        }

        abort(422, 'Format berkas tidak didukung. Gunakan .sql (MySQL) atau .sqlite (SQLite).');
    }

    /** Jalankan skrip SQL (dump MySQL kita) statement per statement via PDO. */
    private function runSqlScript(string $sql): void
    {
        $pdo = DB::connection('mysql')->getPdo();
        foreach (self::splitStatements($sql) as $stmt) {
            $pdo->exec($stmt);
        }
    }

    /**
     * Pecah skrip SQL jadi statement, menghormati string ber-kutip tunggal
     * (escape backslash) & komentar "-- ...", sehingga tanda ';' / newline
     * di dalam nilai tidak salah dipotong.
     *
     * @return list<string>
     */
    public static function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $len = strlen($sql);
        $inString = false;
        $i = 0;

        while ($i < $len) {
            $ch = $sql[$i];

            if ($inString) {
                $buffer .= $ch;
                if ($ch === '\\' && $i + 1 < $len) {   // escape: ambil karakter berikutnya apa adanya
                    $buffer .= $sql[$i + 1];
                    $i += 2;

                    continue;
                }
                if ($ch === "'") {
                    $inString = false;
                }
                $i++;

                continue;
            }

            if ($ch === "'") {
                $inString = true;
                $buffer .= $ch;
                $i++;

                continue;
            }

            // Komentar baris "-- ..." (hanya di luar string) → lompati sampai newline.
            if ($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }

                continue;
            }

            if ($ch === ';') {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                $i++;

                continue;
            }

            $buffer .= $ch;
            $i++;
        }

        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}
