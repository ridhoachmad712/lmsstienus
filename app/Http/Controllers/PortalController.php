<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Prodi;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    /** Halaman depan publik yang hanya menautkan dua aplikasi mandiri. */
    public function index(): View
    {
        return view('portal.index');
    }

    /** Masuk ke ruang kerja pembelajaran. */
    public function lms(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return view('portal.lms', $this->staffLmsData($user, Semester::activeKeys()));
        }

        return redirect()->route($user->isMahasiswa() ? 'dashboard.mahasiswa' : 'dashboard.dosen');
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
