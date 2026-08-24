@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-pretitle', $greeting . ',')
@section('page-title', auth()->user()->name)

@section('content')
<div class="d-none d-md-block">@include('partials.welcome-banner')</div>

<div class="d-md-none mb-4">
    <div class="text-secondary small">{{ now()->translatedFormat('l, d F Y') }}</div>
    <div class="fw-bold fs-2">Pilih mata kuliah untuk mulai belajar</div>
</div>

{{-- Di HP, mata kuliah adalah pintu masuk utama. --}}
<section class="d-md-none mb-4" aria-labelledby="mobile-courses-title">
    <div class="d-flex align-items-end mb-2">
        <div>
            <div class="text-secondary small">Semester aktif</div>
            <h2 class="h3 mb-0" id="mobile-courses-title">Mata Kuliah Saya</h2>
        </div>
        <a href="{{ route('courses.index') }}" class="ms-auto small fw-bold text-decoration-none">Lihat semua</a>
    </div>
    @if ($courses->isEmpty())
        <div class="card"><div class="card-body text-center py-4">
            <span class="avatar avatar-lg bg-primary-lt mb-2"><i class="ti ti-books fs-1"></i></span>
            <div class="fw-bold">Belum ada mata kuliah</div>
            <div class="text-secondary small mb-3">Gabung menggunakan kode kelas dari dosen.</div>
            <a href="{{ route('enrollments.join.show') }}" class="btn btn-primary w-100"><i class="ti ti-key me-1"></i>Gabung Kelas</a>
        </div></div>
    @else
        @foreach ($courses as $course)
            @php($coursePending = $pending->where('course_id', $course->id)->count())
            <a href="{{ route('courses.show', $course) }}" class="card text-reset text-decoration-none mb-2">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="avatar avatar-md bg-{{ $course->color() }}-lt flex-shrink-0"><i class="ti ti-book-2 fs-2"></i></span>
                    <div class="min-w-0 flex-fill">
                        <div class="fw-bold text-truncate">{{ $course->name }}</div>
                        <div class="text-secondary small text-truncate">{{ $course->code }}@if($course->class_name) · Kelas {{ $course->class_name }}@endif</div>
                        <div class="text-secondary small text-truncate">{{ $course->lecturer->name }}</div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        @if ($coursePending > 0)<span class="badge bg-orange-lt">{{ $coursePending }} tugas</span>@else<span class="badge bg-green-lt">Selesai</span>@endif
                        <i class="ti ti-chevron-right text-secondary ms-1"></i>
                    </div>
                </div>
            </a>
        @endforeach
    @endif
</section>

{{-- Alert kehadiran rendah --}}
@foreach ($lowAttendance as $low)
    <div class="alert alert-warning" role="alert">
        <i class="ti ti-alert-triangle me-1"></i>
        Kehadiran Anda di <strong>{{ $low['course']->name }}</strong> baru <strong>{{ $low['percent'] }}%</strong> (di bawah 75%).
    </div>
@endforeach

{{-- Aksi cepat: tugas terdekat yang belum dikerjakan --}}
@if ($pending->isNotEmpty())
    @php($next = $pending->first())
    <div class="card mb-3 border-primary overflow-hidden student-priority-card" id="pengingat" style="scroll-margin-top:5rem">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3">
                <span class="avatar avatar-md bg-orange-lt flex-shrink-0"><i class="ti ti-{{ $next->isQuiz() ? 'help-circle' : 'file-text' }} fs-2"></i></span>
                <div class="min-w-0 flex-fill">
                    <div class="text-uppercase text-primary fw-bold" style="font-size:.68rem;letter-spacing:.05em">Pengingat terdekat</div>
                    <div class="fw-bold fs-3 text-truncate">{{ $next->title }}</div>
                    <div class="text-secondary small text-truncate">{{ $next->course->name }}</div>
                    <div class="mt-2"><x-due :date="$next->deadline" /></div>
                </div>
            </div>
            <a href="{{ route('assignments.index', $next->course) }}" class="btn btn-primary w-100 mt-3"><i class="ti ti-book-2 me-1"></i>Buka Tugas Mata Kuliah Ini</a>
        </div>
    </div>
@endif

<div class="row row-deck row-cards">
    {{-- Stat cards (dapat diklik ke halaman terkait) --}}
    @foreach ([
        ['Kelas Diikuti', $stats['courses'], 'ti-school', 'primary', route('courses.index')],
        ['Tugas Pending', $stats['pending'], 'ti-checklist', 'orange', $pending->isNotEmpty() ? route('assignments.show', $pending->first()) : null],
        ['Rata-rata Hadir', is_null($stats['attendance']) ? '—' : $stats['attendance'].'%', 'ti-qrcode', 'green', null],
        ['Notif Baru', $stats['unread'], 'ti-bell', 'azure', route('notifications.index')],
    ] as [$label, $value, $icon, $color, $href])
        <div class="col-6 col-lg-3 d-none d-md-block">
            @if ($href)<a href="{{ $href }}" class="card card-sm card-link card-link-pop">@else<div class="card card-sm">@endif
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-{{ $color }} text-white avatar"><i class="ti {{ $icon }} fs-2"></i></span></div>
                        <div class="col"><div class="font-weight-medium">{{ $value }}</div><div class="text-secondary">{{ $label }}</div></div>
                    </div>
                </div>
            @if ($href)</a>@else</div>@endif
        </div>
    @endforeach

    {{-- Tugas mendatang --}}
    <div class="col-lg-6 d-none d-md-block" id="tugas" style="scroll-margin-top:5rem">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <div><div class="text-secondary small">Perlu tindakan</div><h3 class="card-title">Tugas Mendatang</h3></div>
                @if ($pending->isNotEmpty())<span class="badge bg-orange-lt ms-auto">{{ $pending->count() }} belum selesai</span>@endif
            </div>
            @if ($pending->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-circle-check" title="Tidak ada tugas pending" description="Semua tugas sudah dikumpulkan." /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($pending->groupBy('course_id') as $courseAssignments)
                        @php($pendingCourse = $courseAssignments->first()->course)
                        <a href="{{ route('assignments.index', $pendingCourse) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <span class="avatar bg-{{ $pendingCourse->color() }}-lt me-2"><i class="ti ti-book-2"></i></span>
                            <div class="me-auto">
                                <div class="fw-bold">{{ $pendingCourse->name }}</div>
                                <div class="text-secondary small">{{ $courseAssignments->count() }} tugas/kuis belum selesai</div>
                            </div>
                            <i class="ti ti-chevron-right text-secondary"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Pertemuan mendatang --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><div><div class="text-secondary small">Agenda</div><h3 class="card-title">Jadwal Pertemuan</h3></div></div>
            @if ($upcomingMeetings->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-calendar-off" title="Tidak ada jadwal mendatang" /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($upcomingMeetings as $m)
                        <div class="list-group-item d-flex align-items-center">
                            <span class="avatar bg-azure-lt me-2"><i class="ti ti-calendar-event"></i></span>
                            <div class="me-auto">
                                <div class="fw-bold">Pertemuan {{ $m->number }} — {{ $m->topic }}</div>
                                <div class="text-secondary small">{{ $m->course->name }}</div>
                            </div>
                            <span class="text-secondary small text-end">{{ $m->date->translatedFormat('d M') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Nilai terbaru --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Nilai Terbaru</h3></div>
            @if ($recentGrades->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-clipboard" title="Belum ada nilai" /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($recentGrades as $sub)
                        <div class="list-group-item d-flex align-items-center">
                            <div class="me-auto">
                                <div class="fw-bold">{{ $sub->assignment->title }}</div>
                                <div class="text-secondary small">{{ $sub->assignment->course->name }}</div>
                            </div>
                            <span class="badge bg-green-lt fs-3">{{ \App\Support\Grades::num($sub->score) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Kelas saya --}}
    <div class="col-lg-6 d-none d-md-block">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kelas Saya</h3></div>
            @if ($courses->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-school" title="Belum terdaftar di kelas" /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($courses as $course)
                        <a href="{{ route('courses.show', $course) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="avatar bg-primary-lt me-2"><i class="ti ti-book"></i></span>
                            <div><div class="fw-bold">{{ $course->name }}</div>
                                <div class="text-secondary small">{{ $course->code }} · {{ $course->lecturer->name }}</div></div>
                            <i class="ti ti-chevron-right ms-auto text-secondary"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
