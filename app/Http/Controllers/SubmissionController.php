<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    use ChecksCourseAccess;

    /** Mahasiswa submit berkas tugas. */
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        $assignment->load('course');
        $this->ensureCourseAccess($request, $assignment->course);

        $user = $request->user();
        abort_if($user->isDosen(), 403);
        abort_if($assignment->isQuiz(), 404);

        $existing = $assignment->submissionFor($user);
        // Boleh ganti selama belum dinilai. Kalau sudah dinilai, tidak boleh.
        abort_if($existing && $existing->isGraded(), 403, 'Tugas sudah dinilai, tidak bisa diubah.');

        // Aturan validasi mengikuti bentuk jawaban yang diatur dosen.
        $rules = [];
        if ($assignment->allowsFile()) {
            $rules['file'] = [
                $assignment->submission_mode === Assignment::SUBMISSION_FILE ? 'required' : 'nullable',
                'file', 'max:20480', 'mimes:pdf,doc,docx,zip,ppt,pptx,xls,xlsx',
            ];
        }
        if ($assignment->allowsText()) {
            $rules['answer_text'] = [
                $assignment->submission_mode === Assignment::SUBMISSION_TEXT ? 'required' : 'nullable',
                'string', 'max:20000',
            ];
        }

        $request->validate($rules, [
            'file.mimes' => 'Berkas harus PDF, Word, PowerPoint, Excel, atau ZIP.',
            'file.max' => 'Ukuran maksimal 20 MB.',
            'answer_text.required' => 'Jawaban tidak boleh kosong.',
        ]);

        // Mode "keduanya": minimal salah satu (teks atau berkas) harus diisi.
        if ($assignment->submission_mode === Assignment::SUBMISSION_BOTH
            && ! $request->filled('answer_text') && ! $request->hasFile('file')) {
            throw ValidationException::withMessages([
                'answer_text' => 'Isi jawaban teks atau unggah berkas (minimal salah satu).',
            ]);
        }

        $status = $assignment->isPastDeadline() ? 'late' : 'ontime';
        $payload = ['status' => $status, 'submitted_at' => now()];

        if ($assignment->allowsText()) {
            $payload['answer_text'] = $request->input('answer_text');
        }

        if ($assignment->allowsFile() && $request->hasFile('file')) {
            if ($existing && $existing->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $payload['file_path'] = $request->file('file')->store('submissions/'.$assignment->id, 'public');
        }

        if ($existing) {
            $existing->update($payload);

            return back()->with('status', 'Pengumpulan berhasil diperbarui.');
        }

        $payload['user_id'] = $user->id;
        $assignment->submissions()->create($payload);

        return back()->with('status', 'Tugas berhasil dikumpulkan.');
    }

    /** Dosen membuka kembali pengumpulan agar mahasiswa bisa kumpul ulang. */
    public function reopen(Request $request, Submission $submission): RedirectResponse
    {
        $submission->load('assignment.course');
        $this->ensureCourseOwner($request, $submission->assignment->course);

        if ($submission->file_path) {
            Storage::disk('public')->delete($submission->file_path);
        }
        $submission->delete();

        return back()->with('status', 'Pengumpulan dibuka kembali — mahasiswa dapat mengumpulkan ulang.');
    }

    /** Dosen unduh semua berkas pengumpulan sebagai ZIP. */
    public function downloadAll(Request $request, Assignment $assignment)
    {
        $assignment->load('course');
        $this->ensureCourseOwner($request, $assignment->course);

        $subs = $assignment->submissions()->with('student')->whereNotNull('file_path')->get();
        abort_if($subs->isEmpty(), 404, 'Belum ada berkas untuk diunduh.');

        $disk = Storage::disk('public');
        $tmp = storage_path('app/zip-'.uniqid().'.zip');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($subs as $sub) {
            if (! $sub->file_path || ! $disk->exists($sub->file_path)) {
                continue;
            }
            $ext = pathinfo($sub->file_path, PATHINFO_EXTENSION);
            $name = Str::slug(($sub->student->nim_nip ?: 'mhs').'-'.$sub->student->name).($ext ? '.'.$ext : '');
            $zip->addFile($disk->path($sub->file_path), $name);
        }
        $zip->close();

        return response()->download($tmp, 'tugas-'.Str::slug($assignment->title).'.zip')->deleteFileAfterSend(true);
    }

    /** Dosen input nilai + feedback. */
    public function grade(Request $request, Submission $submission): RedirectResponse
    {
        $submission->load('assignment.course');
        $this->ensureCourseOwner($request, $submission->assignment->course);
        $assignment = $submission->assignment;

        if ($assignment->hasRubric()) {
            // Nilai dihitung dari rubrik: jumlah poin tiap kriteria (di-clamp ke maks-nya).
            $request->validate([
                'rubric' => ['required', 'array'],
                'feedback' => ['nullable', 'string'],
            ]);

            $input = (array) $request->input('rubric', []);
            $total = 0;
            foreach ($assignment->rubricCriteria as $crit) {
                $pts = max(0, min((float) ($input[$crit->id] ?? 0), (float) $crit->max_points));
                $total += $pts;
                \App\Models\RubricScore::updateOrCreate(
                    ['submission_id' => $submission->id, 'rubric_criterion_id' => $crit->id],
                    ['points' => $pts],
                );
            }

            $submission->update([
                'score' => min($total, (float) $assignment->max_score),
                'feedback' => $request->input('feedback'),
            ]);
            $data = ['score' => $submission->score];
        } else {
            $data = $request->validate([
                'score' => ['required', 'numeric', 'min:0', 'max:'.$assignment->max_score],
                'feedback' => ['nullable', 'string'],
            ]);

            $submission->update($data);
        }

        Notifier::toUser(
            $submission->user_id,
            'grade',
            'Nilai tugas tersedia',
            $submission->assignment->title.' telah dinilai: '.rtrim(rtrim((string) $data['score'], '0'), '.'),
            route('assignments.show', $submission->assignment),
        );

        return back()->with('status', 'Nilai tersimpan.');
    }

    public function download(Request $request, Submission $submission): StreamedResponse
    {
        $submission->load('assignment.course');
        // Dosen pemilik atau mahasiswa pemilik submission
        $user = $request->user();
        $isOwnerDosen = $user->isDosen() && $submission->assignment->course->user_id === $user->id;
        $isOwnerStudent = $submission->user_id === $user->id;
        abort_unless($isOwnerDosen || $isOwnerStudent, 403);

        $disk = Storage::disk('public');
        abort_unless($submission->file_path && $disk->exists($submission->file_path), 404);

        $ext = pathinfo($submission->file_path, PATHINFO_EXTENSION);
        $name = Str::slug($submission->assignment->title.'-'.$submission->student->name).($ext ? '.'.$ext : '');

        return $disk->download($submission->file_path, $name);
    }
}
