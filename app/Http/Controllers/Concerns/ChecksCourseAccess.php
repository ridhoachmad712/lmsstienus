<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use Illuminate\Http\Request;

trait ChecksCourseAccess
{
    /** Dosen pemilik atau mahasiswa terdaftar (atau admin) boleh mengakses kelas. */
    protected function ensureCourseAccess(Request $request, Course $course): void
    {
        abort_unless($request->user()->can('view', $course), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    /** Hanya dosen pemilik kelas (atau admin). */
    protected function ensureCourseOwner(Request $request, Course $course): void
    {
        abort_unless($request->user()->can('manage', $course), 403);

        if (! $request->isMethodSafe() && $course->isCompleted()) {
            abort(403, 'Kelas ini sudah selesai (read-only). Buka kembali untuk mengubah.');
        }
    }
}
