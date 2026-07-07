<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /** Lihat kelas: dosen pemilik atau mahasiswa terdaftar. (Admin di-bypass via Gate::before.) */
    public function view(User $user, Course $course): bool
    {
        return ($user->isDosen() && $course->user_id === $user->id)
            || ($user->isMahasiswa() && $course->students()->whereKey($user->id)->exists());
    }

    /** Kelola kelas: hanya dosen pemilik. (Admin di-bypass via Gate::before.) */
    public function manage(User $user, Course $course): bool
    {
        return $user->isDosen() && $course->user_id === $user->id;
    }
}
