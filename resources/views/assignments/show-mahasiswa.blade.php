@extends('layouts.app')

@section('title', $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Tugas')
@section('page-title', $assignment->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Kelas Saya</a></li>
    <li class="breadcrumb-item"><a href="{{ route('courses.show', $assignment->course) }}">{{ $assignment->course->name }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('assignments.index', $assignment->course) }}">Tugas & Kuis</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($assignment->title, 24) }}</li>
@endsection

@section('content')
@php($course = $assignment->course)
@include('courses._subnav')

<div class="row row-cards">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex mb-3">
                    <div>
                        <span class="text-secondary">Deadline</span>
                        <div class="fw-bold">{{ $assignment->deadline?->translatedFormat('d M Y H:i') ?? 'Tanpa deadline' }}</div>
                    </div>
                    @if ($assignment->isPastDeadline())
                        <span class="badge bg-red-lt ms-auto align-self-start">Deadline terlewat</span>
                    @endif
                </div>
                @if ($assignment->description)
                    <hr>
                    <div style="white-space:pre-line">{{ $assignment->description }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Pengumpulan Anda</h3></div>
            <div class="card-body">
                @php($mode = $assignment->submission_mode)
                @if ($submission)
                    <div class="mb-3">
                        <span class="badge bg-{{ $submission->isLate() ? 'red' : 'green' }}-lt">{{ $submission->isLate() ? 'Terlambat' : 'Tepat waktu' }}</span>
                        <span class="text-secondary small ms-1">{{ $submission->submitted_at?->translatedFormat('d M Y H:i') }}</span>
                    </div>
                @endif

                @if ($submission && $submission->isGraded())
                    {{-- Sudah dinilai: tampilkan jawaban terkirim (baca saja) + nilai --}}
                    @if ($assignment->allowsText() && $submission->answer_text)
                        <div class="mb-3"><span class="text-secondary">Jawaban teks Anda</span>
                            <div class="border rounded p-2 mt-1" style="white-space:pre-line">{{ $submission->answer_text }}</div>
                        </div>
                    @endif
                    @if ($submission->file_path)
                        <a href="{{ route('submissions.download', $submission) }}" class="btn btn-sm mb-3"><i class="ti ti-download me-1"></i>Unduh berkas saya</a>
                    @endif
                    <hr>
                    <div class="mb-2"><span class="text-secondary">Nilai</span>
                        <div class="h1 mb-0">{{ \App\Support\Grades::num($submission->score) }} <small class="text-secondary fs-4">/ {{ $assignment->max_score }}</small></div>
                    </div>
                    @if ($submission->feedback)
                        <div class="mt-2"><span class="text-secondary">Feedback dosen</span>
                            <div class="alert alert-info mt-1" style="white-space:pre-line">{{ $submission->feedback }}</div>
                        </div>
                    @endif
                @else
                    {{-- Belum dinilai (baru / sudah kumpul): form sesuai bentuk jawaban --}}
                    @if (! $submission && $assignment->isPastDeadline())
                        <div class="alert alert-warning mb-3">Deadline sudah lewat — pengumpulan akan ditandai <strong>terlambat</strong>.</div>
                    @elseif ($submission)
                        <div class="text-secondary mb-3">Menunggu penilaian dosen. Anda masih bisa memperbarui jawaban.</div>
                    @endif

                    <form method="POST" action="{{ route('submissions.store', $assignment) }}" enctype="multipart/form-data" data-warn-unsaved>
                        @csrf

                        @if ($assignment->allowsText())
                            <div class="mb-3">
                                <label class="form-label @if ($mode === 'text') required @endif">Jawaban Anda</label>
                                <textarea name="answer_text" rows="8"
                                          class="form-control @error('answer_text') is-invalid @enderror"
                                          placeholder="Tulis jawaban Anda di sini…"
                                          @if ($mode === 'text') required @endif>{{ old('answer_text', $submission->answer_text ?? '') }}</textarea>
                                @error('answer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        @if ($assignment->allowsFile())
                            <div class="mb-3">
                                <label class="form-label @if ($mode === 'file' && ! $submission) required @endif">
                                    {{ $submission && $submission->file_path ? 'Ganti berkas' : 'Unggah berkas' }}
                                </label>
                                @if ($submission && $submission->file_path)
                                    <div class="mb-1"><a href="{{ route('submissions.download', $submission) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-download me-1"></i>Berkas saat ini</a></div>
                                @endif
                                <input type="file" name="file" accept=".pdf,.doc,.docx,.zip,.ppt,.pptx,.xls,.xlsx"
                                       class="form-control @error('file') is-invalid @enderror"
                                       @if ($mode === 'file' && ! $submission) required @endif>
                                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="form-hint">PDF/Word/PPT/Excel/ZIP, maks 20 MB.@if ($submission) Kosongkan bila tidak ingin mengganti berkas.@endif</small>
                            </div>
                        @endif

                        @if ($mode === 'both')
                            <small class="form-hint mb-2 d-block">Isi jawaban teks atau unggah berkas — minimal salah satu.</small>
                        @endif

                        <button class="btn btn-primary w-100">
                            <i class="ti ti-{{ $submission ? 'refresh' : 'upload' }} me-1"></i>{{ $submission ? 'Perbarui Jawaban' : 'Kumpulkan Tugas' }}
                        </button>
                        <small class="form-hint d-block mt-1 text-center">Bisa diperbarui selama belum dinilai dosen.</small>
                    </form>

                    @if ($submission)
                        <form method="POST" action="{{ route('submissions.destroy', $submission) }}" class="mt-2"
                              data-confirm="Hapus pengumpulan Anda? Jawaban dan berkas akan dihapus, dan Anda bisa mengumpulkan ulang.">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger w-100"><i class="ti ti-trash me-1"></i>Hapus Pengumpulan</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
