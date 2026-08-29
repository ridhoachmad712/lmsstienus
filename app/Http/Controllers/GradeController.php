<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Course;
use App\Models\GradeComponent;
use App\Models\GradeScore;
use App\Services\Activity;
use App\Services\GradeCalculator;
use App\Services\SiakadGradeSyncService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class GradeController extends Controller
{
    use ChecksCourseAccess;

    public function index(
        Request $request,
        Course $course,
        GradeCalculator $calc,
        SiakadGradeSyncService $siakadSync,
    ): View|RedirectResponse
    {
        $this->ensureCourseAccess($request, $course);
        $user = $request->user();

        if ($user->isMahasiswa()) {
            // Gerbang EDOM: wajib evaluasi kelas ini dulu sebelum melihat nilainya.
            if (EvaluationController::mustEvaluateCourse($user, $course)) {
                return redirect()->route('edom.index')
                    ->with('error', 'Isi EDOM untuk '.$course->name.' dulu untuk membuka nilai.');
            }

            $data = $calc->forStudent($course, $user);

            return view('grades.mahasiswa', [
                'course' => $course,
                'components' => $data['components'],
                'row' => $data['row'],
            ]);
        }

        // 7 komponen standar dibuat otomatis saat kelas belum punya komponen
        // (bobot 0 dulu; dosen tinggal atur bobot & hapus yang tak perlu).
        if (! $course->isCompleted() && $course->gradeComponents()->doesntExist()) {
            $this->seedStandardComponents($course);
        }

        $data = $calc->forCourse($course);

        // Peringatan alur (#2): tugas dinilai tapi tidak ditautkan ke komponen apa pun
        $unlinkedGraded = $course->assignments()
            ->whereNull('grade_component_id')
            ->whereHas('submissions', fn ($q) => $q->whereNotNull('score'))
            ->count();

        return view('grades.dosen', [
            'course' => $course,
            'components' => $data['components'],
            'rows' => $data['rows'],
            'summary' => $data['summary'],
            'autoComponentIds' => $data['autoComponentIds'],
            'unlinkedGraded' => $unlinkedGraded,
            'gradeSyncs' => $course->gradeSyncs()->with('finalizer')->get()->keyBy('user_id'),
            'syncOverview' => $siakadSync->overview($course),
        ]);
    }

    /** Buat 7 komponen standar dengan bobot 0 (urut sesuai daftar tipe). */
    private function seedStandardComponents(Course $course): void
    {
        foreach (GradeComponent::TYPES as $type => $label) {
            $course->gradeComponents()->create([
                'name' => $label,
                'type' => $type,
                'weight' => 0,
            ]);
        }
    }

    /** Simpan nilai manual untuk komponen tanpa tugas online (mis. UTS/UAS kertas). */
    public function saveManual(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $componentIds = $course->gradeComponents()->pluck('id')->all();
        $studentIds = $course->students()->pluck('users.id')->all();

        // Semua komponen boleh diisi manual. Untuk komponen otomatis, nilai yang
        // diisi menjadi OVERRIDE (mengalahkan hitungan); dikosongkan = kembali otomatis.
        foreach ((array) $request->input('scores', []) as $cid => $perStudent) {
            $cid = (int) $cid;
            if (! in_array($cid, $componentIds) || ! is_array($perStudent)) {
                continue;
            }

            foreach ($perStudent as $uid => $val) {
                $uid = (int) $uid;
                if (! in_array($uid, $studentIds)) {
                    continue;
                }

                $val = trim((string) $val);
                if ($val === '') {
                    GradeScore::where('grade_component_id', $cid)->where('user_id', $uid)->delete();

                    continue;
                }

                GradeScore::updateOrCreate(
                    ['grade_component_id' => $cid, 'user_id' => $uid],
                    ['score' => max(0, min(100, (float) $val))],
                );
            }
        }

        return back()->with('status', 'Nilai manual tersimpan.');
    }

    /** Pilih jadwal resmi SIAKAD yang menjadi tujuan nilai kelas LMS. */
    public function mapSiakadSchedule(
        Request $request,
        Course $course,
        SiakadGradeSyncService $siakadSync,
    ): RedirectResponse {
        $this->ensureSyncOwner($request, $course);
        $data = $request->validate([
            'siakad_schedule_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $scheduleId = filled($data['siakad_schedule_id'] ?? null)
                ? (int) $data['siakad_schedule_id']
                : null;
            $siakadSync->assignSchedule($course, $scheduleId);
            Activity::log('grade_sync_map', $scheduleId
                ? "Memetakan kelas {$course->name} ke jadwal SIAKAD #{$scheduleId}"
                : "Menghapus pemetaan jadwal SIAKAD kelas {$course->name}");

            return back()->with('status', $scheduleId
                ? 'Jadwal resmi SIAKAD berhasil dipetakan.'
                : 'Pemetaan jadwal SIAKAD dihapus.');
        } catch (DomainException|RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Pemetaan jadwal gagal karena database SIAKAD tidak dapat dihubungi.');
        }
    }

    /** Finalisasi snapshot dan kirim/retry nilai akhir ke SIAKAD. */
    public function syncToSiakad(
        Request $request,
        Course $course,
        SiakadGradeSyncService $siakadSync,
    ): RedirectResponse {
        $this->ensureSyncOwner($request, $course);

        try {
            $result = $siakadSync->syncCourse($course, $request->user());
            Activity::log(
                'grade_sync',
                "Sinkronisasi nilai {$course->name}: {$result['synced']} berhasil, {$result['skipped']} tetap, {$result['failed']} gagal",
            );

            $message = "Sinkronisasi selesai: {$result['synced']} berhasil";
            if ($result['skipped'] > 0) {
                $message .= ", {$result['skipped']} sudah sama";
            }
            if ($result['failed'] > 0) {
                $message .= ", {$result['failed']} gagal. Periksa status tiap mahasiswa";
            }

            return back()->with($result['failed'] > 0 ? 'error' : 'status', $message.'.');
        } catch (DomainException|RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Sinkronisasi gagal karena database SIAKAD tidak dapat dihubungi.');
        }
    }

    /** Sinkronisasi tetap boleh diulang setelah kelas selesai/read-only. */
    private function ensureSyncOwner(Request $request, Course $course): void
    {
        abort_unless($request->user()->can('manage', $course), 403);
    }
}
