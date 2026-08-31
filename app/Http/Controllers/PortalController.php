<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Prodi;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use App\Services\AcademicSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    /** Pintu masuk publik: pengguna memilih sistem sebelum login. */
    public function index(Request $request): View
    {
        return view('portal.index', [
            'user' => $request->user(),
            'siakadReady' => filled(config('services.legacy_siakad.url')),
        ]);
    }

    /** Buka SIAKAD lama yang memiliki login dan database sendiri. */
    public function siakad(Request $request): RedirectResponse
    {
        $request->session()->put('active_system', 'siakad');
        $baseUrl = config('services.legacy_siakad.url');

        if (! filled($baseUrl)) {
            return redirect()->route('portal.index')
                ->with('error', 'Alamat SIAKAD lama belum dikonfigurasi oleh administrator.');
        }

        return redirect()->away($baseUrl);
    }

    /** Masuk ke ruang kerja pembelajaran. */
    public function lms(Request $request): View|RedirectResponse
    {
        $request->session()->put('active_system', 'lms');
        $user = $request->user();

        if ($user->isStaff()) {
            return view('portal.lms', $this->staffLmsData($user, Semester::activeKeys()));
        }

        return redirect()->route($user->isMahasiswa() ? 'dashboard.mahasiswa' : 'dashboard.dosen');
    }

    /** Ringkasan operasional SIAKAD untuk mahasiswa. */
    private function studentSiakadData(User $user): array
    {
        $academic = (new AcademicSummary)->forStudent($user);
        $quota = (new KrsController)->quotaFor($user);
        $edomPending = EvaluationController::edomOpen()
            ? EvaluationController::pendingCourses($user)->count()
            : 0;

        return [
            'dashboardType' => 'student',
            'academic' => $academic,
            'quota' => $quota,
            'edomPending' => $edomPending,
            'statCards' => [
                ['SKS KRS', $academic['sks_krs'].' / '.$quota, 'Jatah semester ini', 'ti-clipboard-list', 'blue', 'krs.index'],
                ['Status KRS', ucfirst($academic['krs_status']), $academic['periode_label'], 'ti-progress-check', $academic['krs_status'] === Enrollment::STATUS_APPROVED ? 'green' : 'yellow', 'krs.index'],
                ['IPK', number_format($academic['ipk'], 2), $academic['sks_kumulatif'].' SKS lulus', 'ti-chart-line', 'green', 'transkrip.mine'],
                ['IPS terakhir', $academic['ips_terakhir'] === null ? '—' : number_format($academic['ips_terakhir'], 2), $academic['ips_label'] ?? 'Belum ada nilai final', 'ti-report-analytics', 'purple', 'transkrip.mine'],
            ],
        ];
    }

    /** Ringkasan operasional SIAKAD untuk dosen/wali. */
    private function lecturerSiakadData(User $user, array $activeKeys): array
    {
        $activeTeaching = $this->activePeriodCourses(
            $user->teachingCourses()->where('status', Course::STATUS_ACTIVE),
            $activeKeys,
        );

        $pendingAdvisees = $user->advisees()
            ->whereHas('enrollments', fn ($enrollments) => $enrollments
                ->where('status', Enrollment::STATUS_SUBMITTED)
                ->whereHas('course', fn ($courses) => $this->activePeriodCourses($courses, $activeKeys)))
            ->count();

        $atRisk = $user->advisees()
            ->whereNotNull('ipk_cache')
            ->where('ipk_cache', '<', 2.75)
            ->count();

        return [
            'dashboardType' => 'lecturer',
            'pendingAdvisees' => $pendingAdvisees,
            'atRiskAdvisees' => $atRisk,
            'statCards' => [
                ['Mahasiswa wali', $user->advisees()->count(), 'Total mahasiswa bimbingan', 'ti-users-group', 'blue', 'perwalian.index'],
                ['KRS menunggu', $pendingAdvisees, 'Perlu keputusan dosen wali', 'ti-clipboard-check', $pendingAdvisees ? 'orange' : 'green', 'perwalian.index'],
                ['Kelas mengajar', (clone $activeTeaching)->count(), 'Pada semester aktif', 'ti-school', 'azure', 'schedule.index'],
                ['Perlu perhatian', $atRisk, 'IPK di bawah 2,75', 'ti-alert-triangle', $atRisk ? 'red' : 'green', 'perwalian.index'],
            ],
        ];
    }

    /** Ringkasan operasional SIAKAD untuk admin dan kaprodi. */
    private function staffSiakadData(User $user, array $activeKeys): array
    {
        $prodiId = $this->staffProdiId($user);
        $students = User::where('role', User::ROLE_MAHASISWA)
            ->when($prodiId, fn ($query) => $query->where('prodi_id', $prodiId));
        $activeCourses = $this->activePeriodCourses(
            Course::where('status', Course::STATUS_ACTIVE)
                ->when($prodiId, fn ($query) => $query->where('prodi_id', $prodiId)),
            $activeKeys,
        );

        $pendingKrs = Enrollment::where('status', Enrollment::STATUS_SUBMITTED)
            ->whereHas('student', fn ($query) => $query
                ->where('role', User::ROLE_MAHASISWA)
                ->when($prodiId, fn ($student) => $student->where('prodi_id', $prodiId)))
            ->whereHas('course', fn ($query) => $this->activePeriodCourses($query, $activeKeys))
            ->distinct('user_id')->count('user_id');

        $incompleteStudents = (clone $students)
            ->where(fn ($query) => $query
                ->whereNull('prodi_id')
                ->orWhereNull('kurikulum_id')
                ->orWhereNull('advisor_id'))
            ->count();
        $coursesWithoutSchedule = (clone $activeCourses)->whereDoesntHave('schedules')->count();
        $coursesWithoutSubject = (clone $activeCourses)->whereNull('mata_kuliah_id')->count();

        return [
            'dashboardType' => 'staff',
            'scopeLabel' => $this->staffScopeLabel($prodiId),
            'pendingKrs' => $pendingKrs,
            'incompleteStudents' => $incompleteStudents,
            'coursesWithoutSchedule' => $coursesWithoutSchedule,
            'coursesWithoutSubject' => $coursesWithoutSubject,
            'statCards' => [
                ['Mahasiswa', (clone $students)->count(), $prodiId ? 'Lingkup program studi' : 'Seluruh kampus', 'ti-users', 'blue', 'admin.students.index'],
                ['KRS menunggu', $pendingKrs, 'Mahasiswa belum diputuskan wali', 'ti-clipboard-check', $pendingKrs ? 'orange' : 'green', 'admin.academic.index'],
                ['Kelas tanpa jadwal', $coursesWithoutSchedule, 'Kelas aktif periode berjalan', 'ti-calendar-off', $coursesWithoutSchedule ? 'red' : 'green', 'admin.courses.index'],
                ['Data belum lengkap', $incompleteStudents, 'Prodi, kurikulum, atau wali', 'ti-user-exclamation', $incompleteStudents ? 'yellow' : 'green', 'admin.students.index'],
            ],
        ];
    }

    /** Dashboard monitoring LMS untuk admin/kaprodi. */
    private function staffLmsData(User $user, array $activeKeys): array
    {
        $prodiId = $this->staffProdiId($user);
        $activeCourses = $this->activePeriodCourses(
            Course::where('status', Course::STATUS_ACTIVE)
                ->when($prodiId, fn ($query) => $query->where('prodi_id', $prodiId)),
            $activeKeys,
        );

        $courses = (clone $activeCourses)
            ->with(['lecturer', 'prodi'])
            ->withCount(['students', 'meetings'])
            ->withCount(['submissions as ungraded_count' => fn ($query) => $query
                ->whereNotNull('submissions.submitted_at')
                ->whereNull('submissions.score')])
            ->get();

        $courseIds = $courses->pluck('id');
        $meetings = (int) $courses->sum('meetings_count');
        $targetMeetings = $courses->count() * 16;
        $meetingProgress = $targetMeetings > 0
            ? min(100, (int) round(($meetings / $targetMeetings) * 100))
            : 0;
        $ungraded = $courseIds->isEmpty() ? 0 : Submission::whereNotNull('submitted_at')
            ->whereNull('score')
            ->whereHas('assignment', fn ($query) => $query->whereIn('course_id', $courseIds))
            ->count();
        $students = $courseIds->isEmpty() ? 0 : Enrollment::whereIn('course_id', $courseIds)
            ->where('status', Enrollment::STATUS_APPROVED)
            ->distinct('user_id')->count('user_id');

        $attention = $courses
            ->filter(fn ($course) => $course->meetings_count === 0 || $course->ungraded_count > 0)
            ->sortByDesc(fn ($course) => ($course->meetings_count === 0 ? 100000 : 0) + $course->ungraded_count);
        $attentionCourses = $attention->take(8)->values();

        $teaching = null;
        if ($user->isDosen()) {
            $own = $this->activePeriodCourses(
                $user->teachingCourses()->where('status', Course::STATUS_ACTIVE),
                $activeKeys,
            )->withCount(['submissions as ungraded_count' => fn ($query) => $query
                ->whereNotNull('submissions.submitted_at')->whereNull('submissions.score')])->get();
            $teaching = ['courses' => $own->count(), 'ungraded' => (int) $own->sum('ungraded_count')];
        }

        return [
            'user' => $user,
            'scopeLabel' => $this->staffScopeLabel($prodiId),
            'activePeriods' => collect($activeKeys)->map(fn ($key) => Semester::keyLabel($key)),
            'meetingProgress' => $meetingProgress,
            'meetingCount' => $meetings,
            'meetingTarget' => $targetMeetings,
            'attentionCourses' => $attentionCourses,
            'attentionCount' => $attention->count(),
            'teaching' => $teaching,
            'statCards' => [
                ['Kelas aktif', $courses->count(), 'Semester aktif', 'ti-school', 'blue', 'admin.courses.index'],
                ['Mahasiswa terdaftar', $students, 'Terdaftar di kelas LMS', 'ti-users', 'green', 'admin.courses.index'],
                ['Belum ada pertemuan', $courses->where('meetings_count', 0)->count(), 'Kelas perlu ditindaklanjuti', 'ti-calendar-off', 'orange', 'admin.courses.index'],
                ['Belum dinilai', $ungraded, 'Pengumpulan menunggu nilai', 'ti-clipboard-list', $ungraded ? 'red' : 'green', 'admin.courses.index'],
            ],
        ];
    }

    /** Terapkan satu atau beberapa semester aktif pada query kelas. */
    private function activePeriodCourses($query, array $activeKeys)
    {
        return $query->where(function ($periods) use ($activeKeys) {
            foreach ($activeKeys as $key) {
                [$year, $semester] = explode('-', $key, 2);
                $periods->orWhere(fn ($period) => $period
                    ->where('year', (int) $year)
                    ->where('semester', $semester));
            }
        });
    }

    /** Admin lintas prodi; kaprodi dibatasi pada prodi yang dipimpinnya. */
    private function staffProdiId(User $user): ?int
    {
        if ($user->isAdmin()) {
            return null;
        }

        return $user->headedProdi()->value('id') ?? $user->prodi_id;
    }

    private function staffScopeLabel(?int $prodiId): string
    {
        return $prodiId ? (Prodi::find($prodiId)?->name ?? 'Program studi') : 'Seluruh program studi';
    }
}
