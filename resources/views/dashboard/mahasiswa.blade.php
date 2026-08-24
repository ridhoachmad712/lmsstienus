@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-pretitle', $greeting . ',')
@section('page-title', auth()->user()->name)

@section('content')
<div class="d-none d-md-block">@include('partials.welcome-banner')</div>

{{-- Alert KRS dibuka --}}
@if (! empty($krsOpen) && in_array($academic['krs_status'], ['belum', 'draft'], true))
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="ti ti-clipboard-list me-2"></i>
        <div class="flex-fill">Pengisian <strong>KRS {{ $academic['periode_label'] }}</strong> sedang dibuka. Susun rencana studi Anda dan ajukan ke dosen wali.</div>
        <a href="{{ route('krs.index') }}" class="btn btn-sm btn-info">{{ $academic['krs_status'] === 'draft' ? 'Lanjutkan KRS' : 'Isi KRS' }}</a>
    </div>
@endif

{{-- Di HP, mata kuliah adalah pintu masuk utama. --}}
<section class="d-md-none mb-3" aria-labelledby="mobile-courses-title">
    <div class="d-flex align-items-end mb-2">
        <h2 class="h2 mb-0" id="mobile-courses-title">Mata Kuliah Saya</h2>
        <a href="{{ route('courses.index') }}" class="ms-auto small fw-bold text-decoration-none">Lihat semua</a>
    </div>
    @if ($courses->isEmpty())
        <div class="card"><div class="card-body text-center py-4">
            <span class="avatar avatar-lg bg-primary-lt mb-2"><i class="ti ti-books fs-1"></i></span>
            <div class="fw-bold">Belum ada mata kuliah</div>
            <div class="text-secondary small mb-3">Mata kuliah akan muncul setelah KRS disetujui.</div>
            <a href="{{ route('krs.index') }}" class="btn btn-primary w-100"><i class="ti ti-clipboard-list me-1"></i>Buka KRS</a>
        </div></div>
    @else
        <div class="card overflow-hidden">
            <div class="list-group list-group-flush">
                @foreach ($courses as $course)
                    <a href="{{ route('courses.show', $course) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 px-3">
                        <span class="avatar avatar-sm bg-{{ $course->color() }}-lt flex-shrink-0"><i class="ti ti-book-2"></i></span>
                        <div class="min-w-0 flex-fill">
                            <div class="fw-bold text-truncate">{{ $course->name }}</div>
                            <div class="text-secondary small text-truncate">{{ $course->lecturer->name }}</div>
                        </div>
                        <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</section>

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

{{-- Alert EDOM dibuka --}}
@if (! empty($edomOpen) && $edomPending > 0)
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="ti ti-star me-2"></i>
        <div class="flex-fill">Evaluasi Dosen (EDOM) sedang dibuka — <strong>{{ $edomPending }}</strong> kelas belum Anda evaluasi.</div>
        <a href="{{ route('edom.index') }}" class="btn btn-sm btn-warning">Isi EDOM</a>
    </div>
@endif

{{-- Alert kehadiran rendah (digabung jadi satu agar tidak membanjiri) --}}
@if (count($lowAttendance) > 0)
    <div class="alert alert-warning" role="alert">
        <div class="d-flex align-items-center">
            <i class="ti ti-alert-triangle me-2 fs-3"></i>
            <div>Kehadiran di bawah 75% pada <strong>{{ count($lowAttendance) }}</strong> kelas — perhatikan agar tidak mengganggu penilaian:</div>
        </div>
        <ul class="mt-2 mb-0">
            @foreach ($lowAttendance as $low)
                <li>{{ $low['course']->name }} — <strong>{{ $low['percent'] }}%</strong></li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ===================== RINGKASAN AKADEMIK ===================== --}}
<div class="row row-cards mb-3">
    @php($ak = [
        ['label' => 'IPK', 'value' => number_format($academic['ipk'], 2), 'sub' => 'Indeks Prestasi Kumulatif', 'icon' => 'ti-award', 'color' => 'blue', 'route' => 'transkrip.mine'],
        ['label' => 'IPS Terakhir', 'value' => is_null($academic['ips_terakhir']) ? '—' : number_format($academic['ips_terakhir'], 2), 'sub' => $academic['ips_label'] ?? 'Belum ada nilai', 'icon' => 'ti-chart-line', 'color' => 'azure', 'route' => 'transkrip.mine'],
        ['label' => 'SKS Kumulatif', 'value' => $academic['sks_kumulatif'], 'sub' => 'Total SKS lulus', 'icon' => 'ti-stack-2', 'color' => 'green', 'route' => 'transkrip.mine'],
    ])
    @foreach ($ak as $c)
        <div class="col-6 col-lg-3">
            <a href="{{ route($c['route']) }}" class="card card-sm card-link">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-{{ $c['color'] }} text-white avatar"><i class="ti {{ $c['icon'] }} fs-2"></i></span></div>
                        <div class="col">
                            <div class="h1 m-0">{{ $c['value'] }}</div>
                            <div class="text-secondary">{{ $c['label'] }}</div>
                            <div class="text-secondary small text-truncate">{{ $c['sub'] }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
    <div class="col-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-purple text-white avatar"><i class="ti ti-calendar-stats fs-2"></i></span></div>
                    <div class="col">
                        <div class="h1 m-0">{{ $academic['semester_ke'] ? 'Smt '.$academic['semester_ke'] : '—' }}</div>
                        <div class="text-secondary">Status</div>
                        <span class="badge bg-{{ $academic['status_color'] }}-lt text-capitalize">{{ $academic['status'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===================== KRS PERIODE INI ===================== --}}
@php($krsMap = [
    'belum' => ['Belum mengisi', 'secondary'],
    'draft' => ['Rencana (draft) — belum diajukan', 'secondary'],
    'diajukan' => ['Menunggu persetujuan dosen wali', 'yellow'],
    'disetujui' => ['Disetujui', 'green'],
])
@php([$krsLabel, $krsColor] = $krsMap[$academic['krs_status']] ?? ['—', 'secondary'])
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <span class="avatar avatar-lg rounded bg-{{ $krsColor }}-lt"><i class="ti ti-clipboard-list icon-lg"></i></span>
            </div>
            <div class="col">
                <div class="text-secondary small">KRS {{ $academic['periode_label'] }}</div>
                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                    <span class="badge bg-{{ $krsColor }}-lt">{{ $krsLabel }}</span>
                    <span class="text-secondary small">{{ $academic['sks_krs'] }} SKS diambil</span>
                </div>
            </div>
            <div class="col-auto">
                <a href="{{ route('krs.index') }}" class="btn"><i class="ti ti-clipboard-list me-1"></i>Buka KRS</a>
            </div>
        </div>
    </div>
</div>

{{-- ===================== AGENDA AKADEMIK ===================== --}}
@if ($agenda->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header py-2">
            <h3 class="card-title"><i class="ti ti-calendar-event me-1"></i>Agenda Akademik</h3>
            <a href="{{ route('academic.calendar') }}" class="btn btn-sm ms-auto">Lihat semua</a>
        </div>
        <div class="list-group list-group-flush">
            @foreach ($agenda as $e)
                <div class="list-group-item d-flex align-items-center">
                    <span class="avatar bg-{{ $e->typeColor() }}-lt me-2"><i class="ti {{ $e->typeIcon() }}"></i></span>
                    <div class="me-auto">
                        <div class="fw-bold">{{ $e->title }} @if ($e->isOngoing())<span class="badge bg-green-lt ms-1">berlangsung</span>@endif</div>
                        <div class="text-secondary small">{{ $e->typeLabel() }}</div>
                    </div>
                    <span class="text-secondary small text-nowrap">{{ $e->dateRange() }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ===================== PERKULIAHAN (e-Learning) ===================== --}}
<h3 class="text-secondary text-uppercase fs-5 mb-2"><i class="ti ti-device-laptop me-1"></i>Perkuliahan</h3>
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
        <section class="d-md-none" aria-labelledby="mobile-meetings-title">
            <div class="d-flex align-items-end mb-2">
                <h2 class="h2 mb-0" id="mobile-meetings-title">Pertemuan Berikutnya</h2>
                <a href="{{ route('calendar') }}" class="ms-auto small fw-bold text-decoration-none">Lihat kalender</a>
            </div>
            @if ($upcomingMeetings->isEmpty())
                <div class="card"><div class="card-body py-3"><x-empty-state icon="ti-calendar-off" title="Tidak ada jadwal mendatang" /></div></div>
            @else
                <div class="card overflow-hidden">
                    <div class="list-group list-group-flush">
                        @foreach ($upcomingMeetings as $m)
                            <a href="{{ route('courses.show', $m->course) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 px-3">
                                <time datetime="{{ $m->date->format('Y-m-d') }}" class="text-center flex-shrink-0" style="width:2.5rem">
                                    <span class="d-block fw-bold fs-3 lh-1">{{ $m->date->format('d') }}</span>
                                    <span class="d-block text-secondary text-uppercase" style="font-size:.68rem">{{ $m->date->translatedFormat('M') }}</span>
                                </time>
                                <div class="min-w-0 flex-fill">
                                    <div class="fw-semibold text-truncate">{{ $m->topic }}</div>
                                    <div class="small text-secondary text-truncate">{{ $m->course->name }} · P{{ $m->number }}</div>
                                </div>
                                <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <div class="card d-none d-md-block">
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
                                <div class="text-secondary small">{{ $course->lecturer->name }}</div></div>
                            <i class="ti ti-chevron-right ms-auto text-secondary"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
