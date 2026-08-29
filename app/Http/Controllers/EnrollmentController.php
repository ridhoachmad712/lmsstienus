<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    /** Mahasiswa bergabung langsung ke kelas aktif memakai kode dari dosen. */
    public function join(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'join_code' => ['required', 'string', 'max:12'],
        ], [
            'join_code.required' => 'Masukkan kode kelas dari dosen.',
        ]);

        $code = strtoupper(trim($validated['join_code']));
        $student = $request->user();

        $course = DB::transaction(function () use ($code, $student) {
            $course = Course::where('join_code', $code)->lockForUpdate()->first();

            if (! $course || $course->isCompleted()) {
                throw ValidationException::withMessages([
                    'join_code' => 'Kode kelas tidak ditemukan atau kelas sudah selesai.',
                ]);
            }

            $enrollment = Enrollment::where('course_id', $course->id)
                ->where('user_id', $student->id)
                ->first();

            if ($enrollment?->status === Enrollment::STATUS_APPROVED) {
                return $course;
            }

            if ($course->isFull()) {
                throw ValidationException::withMessages([
                    'join_code' => 'Kelas sudah mencapai batas peserta.',
                ]);
            }

            $this->enrollApproved($course, $student->id);

            return $course;
        });

        return redirect()->route('courses.show', $course)
            ->with('status', 'Berhasil bergabung ke kelas '.$course->name.'.');
    }

    /** Ganti kode gabung bila kode lama tidak boleh digunakan lagi. */
    public function regenerateJoinCode(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);
        $course->update(['join_code' => Course::generateJoinCode()]);

        return back()->with('status', 'Kode gabung kelas berhasil diperbarui.');
    }

    /** Unduh template CSV impor mahasiswa. */
    public function template(): Response
    {
        $csv = "nama,email,nim\nBudi Santoso,budi@contoh.ac.id,2109010001\nSiti Aminah,siti@contoh.ac.id,2109010002\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template-mahasiswa.csv"',
        ]);
    }

    /** Enroll satu/beberapa mahasiswa terdaftar ke kelas. */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        // Hanya mahasiswa yang boleh di-enroll
        $mahasiswaIds = User::whereIn('id', $validated['user_ids'])
            ->where('role', User::ROLE_MAHASISWA)
            ->pluck('id');

        // Penambahan langsung oleh dosen = enrollment aktif (disetujui), lewati alur KRS.
        $added = $mahasiswaIds->filter(fn ($id) => $this->enrollApproved($course, $id))->count();

        return back()->with('status', "$added mahasiswa berhasil ditambahkan ke kelas.");
    }

    /** Import mahasiswa via CSV (kolom: nama,email,nim). Akun dibuat bila belum ada. */
    public function import(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Gagal membaca berkas CSV.');
        }

        $created = 0;
        $enrolled = 0;
        $skipped = 0;
        $row = 0;

        while (($cols = fgetcsv($handle)) !== false) {
            $row++;

            // Lewati header bila baris pertama mengandung "email"
            if ($row === 1 && stripos(implode(',', $cols), 'email') !== false) {
                continue;
            }

            $name = trim($cols[0] ?? '');
            $email = strtolower(trim($cols[1] ?? ''));
            $nim = trim($cols[2] ?? '');

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $name !== '' ? $name : $email,
                    'email' => $email,
                    'nim_nip' => $nim !== '' ? $nim : null,
                    'role' => User::ROLE_MAHASISWA,
                    'password' => Hash::make($nim !== '' ? $nim : 'password'),
                ]);
                $created++;
            }

            if ($user->role !== User::ROLE_MAHASISWA) {
                $skipped++;
                continue;
            }

            if ($this->enrollApproved($course, $user->id)) {
                $enrolled++;
            }
        }

        fclose($handle);

        return back()->with('status',
            "Import selesai: $enrolled mahasiswa di-enroll ($created akun baru, $skipped baris dilewati).");
    }

    public function destroy(Request $request, Course $course, User $user): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        Enrollment::where('course_id', $course->id)->where('user_id', $user->id)->delete();

        return back()->with('status', 'Mahasiswa dikeluarkan dari kelas.');
    }

    /**
     * Jadikan enrollment mahasiswa AKTIF (disetujui) — buat baru atau promosikan draft/diajukan.
     * Mengembalikan true bila status berubah menjadi aktif (baru terdaftar), false bila sudah aktif.
     */
    private function enrollApproved(Course $course, int $userId): bool
    {
        $enrollment = Enrollment::firstOrNew(['course_id' => $course->id, 'user_id' => $userId]);
        $alreadyActive = $enrollment->exists && $enrollment->status === Enrollment::STATUS_APPROVED;

        $enrollment->status = Enrollment::STATUS_APPROVED;
        $enrollment->enrolled_at = $enrollment->enrolled_at ?? now();
        $enrollment->approved_at = now();
        $enrollment->save();

        return ! $alreadyActive;
    }

    private function authorizeOwner(Request $request, Course $course): void
    {
        abort_unless($request->user()->can('manage', $course), 403);

        if (! $request->isMethodSafe() && $course->isCompleted()) {
            abort(403, 'Kelas ini sudah selesai (read-only). Buka kembali untuk mengubah.');
        }
    }
}
