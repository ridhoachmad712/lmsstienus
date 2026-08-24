@extends('layouts.app')

@section('title', 'Tugas & Kuis')

@section('hero-actions')
    @if (auth()->user()->isDosen() && ! $course->isCompleted())
        <a href="{{ route('assignments.create', [$course, 'type' => 'tugas']) }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tugas</a>
        <a href="{{ route('assignments.create', [$course, 'type' => 'kuis']) }}" class="btn btn-outline-primary"><i class="ti ti-plus me-1"></i>Kuis</a>
    @endif
@endsection

@section('content')
@include('courses._hero')

@if ($assignments->isEmpty())
    <div class="card"><div class="card-body">
        <x-empty-state icon="ti-checklist" title="Belum ada tugas atau kuis"
            :description="auth()->user()->isDosen() ? 'Buat tugas atau kuis pertama untuk kelas ini.' : 'Belum ada yang diberikan dosen.'">
            @if (auth()->user()->isDosen() && ! $course->isCompleted())
                <div class="btn-list">
                    <a href="{{ route('assignments.create', [$course, 'type' => 'tugas']) }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Buat Tugas</a>
                    <a href="{{ route('assignments.create', [$course, 'type' => 'kuis']) }}" class="btn btn-outline-primary"><i class="ti ti-plus me-1"></i>Buat Kuis</a>
                </div>
            @endif
        </x-empty-state>
    </div></div>
@else
    @if (auth()->user()->isMahasiswa())
        @php($todoAssignments = $assignments->filter(fn($a) => !($mySubs[$a->id] ?? null)?->submitted_at))
        @php($submittedAssignments = $assignments->filter(fn($a) => ($mySubs[$a->id] ?? null)?->submitted_at && !($mySubs[$a->id] ?? null)?->isGraded()))
        @php($gradedAssignments = $assignments->filter(fn($a) => ($mySubs[$a->id] ?? null)?->isGraded()))
        <div class="d-md-none assignment-mobile-sections">
            @foreach ([
                ['Perlu dikerjakan', $todoAssignments, 'orange', 'ti-clock'],
                ['Menunggu penilaian', $submittedAssignments, 'azure', 'ti-hourglass'],
                ['Sudah dinilai', $gradedAssignments, 'green', 'ti-circle-check'],
            ] as [$sectionTitle, $items, $sectionColor, $sectionIcon])
                @if ($items->isNotEmpty())
                    <section class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ti {{ $sectionIcon }} text-{{ $sectionColor }} me-2"></i>
                            <h2 class="h3 mb-0">{{ $sectionTitle }}</h2>
                            <span class="badge bg-{{ $sectionColor }}-lt ms-auto">{{ $items->count() }}</span>
                        </div>
                        <div class="card overflow-hidden">
                            <div class="list-group list-group-flush">
                                @foreach ($items as $a)
                                    @php($sub = $mySubs[$a->id] ?? null)
                                    <a href="{{ route('assignments.show', $a) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                                        <span class="avatar bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt flex-shrink-0"><i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-file-text' }}"></i></span>
                                        <div class="min-w-0 flex-fill">
                                            <div class="d-flex align-items-center gap-1"><span class="fw-bold text-truncate">{{ $a->title }}</span>@if($a->isGroup())<i class="ti ti-users text-secondary" title="Tugas kelompok"></i>@endif</div>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="text-secondary small">{{ $a->isQuiz() ? 'Kuis' : 'Tugas' }}</span>
                                                @if ($sub?->isGraded())<span class="badge bg-green-lt">Nilai {{ \App\Support\Grades::num($sub->score) }}</span>@else<x-due :date="$a->deadline" />@endif
                                            </div>
                                        </div>
                                        <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif

    <div @class(['row row-cards', 'd-none d-md-flex' => auth()->user()->isMahasiswa()])>
        @foreach ($assignments as $a)
            @php($sub = $mySubs[$a->id] ?? null)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <span class="avatar bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt me-3"><i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-file-text' }}"></i></span>
                            <div class="flex-fill">
                                <div class="d-flex">
                                    <a href="{{ route('assignments.show', $a) }}" class="fw-bold text-reset">{{ $a->title }}</a>
                                    <span class="badge bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt ms-auto text-uppercase">{{ $a->type }}</span>
                                </div>
                                <div class="mt-1 d-flex align-items-center gap-2">
                                    <x-due :date="$a->deadline" />
                                    @if ($a->deadline)<span class="text-secondary small">{{ $a->deadline->translatedFormat('d M Y H:i') }}</span>@endif
                                </div>
                                <div class="mt-2">
                                    @if (auth()->user()->isDosen() && ! $course->isCompleted())
                                        <span class="text-secondary small"><i class="ti ti-users"></i> {{ $a->submissions_count }} pengumpulan</span>
                                    @else
                                        @if ($sub && $sub->isGraded())
                                            <span class="badge bg-green-lt">Nilai: {{ \App\Support\Grades::num($sub->score) }}</span>
                                        @elseif ($sub)
                                            <span class="badge bg-azure-lt">Sudah dikumpulkan</span>
                                        @elseif ($a->isPastDeadline())
                                            <span class="badge bg-red-lt">Terlewat</span>
                                        @else
                                            <span class="badge bg-yellow-lt">Belum dikerjakan</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('assignments.show', $a) }}" class="btn btn-sm">Buka</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
