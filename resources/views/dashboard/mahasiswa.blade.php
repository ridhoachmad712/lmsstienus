@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-pretitle', $greeting . ',')
@section('page-title', auth()->user()->name)

@section('content')
@include('partials.welcome-banner')

{{-- Alert KRS dibuka --}}
@if (! empty($krsOpen) && in_array($academic['krs_status'], ['belum', 'draft'], true))
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="ti ti-clipboard-list me-2"></i>
        <div class="flex-fill">Pengisian <strong>KRS {{ $academic['periode_label'] }}</strong> sedang dibuka. Susun rencana studi Anda dan ajukan ke dosen wali.</div>
        <a href="{{ route('krs.index') }}" class="btn btn-sm btn-info">{{ $academic['krs_status'] === 'draft' ? 'Lanjutkan KRS' : 'Isi KRS' }}</a>
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

{{-- Alert kehadiran rendah --}}
@foreach ($lowAttendance as $low)
    <div class="alert alert-warning" role="alert">
        <i class="ti ti-alert-triangle me-1"></i>
        Kehadiran Anda di <strong>{{ $low['course']->name }}</strong> baru <strong>{{ $low['percent'] }}%</strong> (di bawah 75%).
    </div>
@endforeach

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
    {{-- Stat singkat --}}
    @foreach ([
        ['Kelas Diikuti', $stats['courses'], 'ti-school', 'primary'],
        ['Tugas Pending', $stats['pending'], 'ti-checklist', 'orange'],
        ['Rata-rata Hadir', is_null($stats['attendance']) ? '—' : $stats['attendance'].'%', 'ti-qrcode', 'teal'],
        ['Notif Baru', $stats['unread'], 'ti-bell', 'pink'],
    ] as [$label, $value, $icon, $color])
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm"><div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-{{ $color }} text-white avatar"><i class="ti {{ $icon }} fs-2"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ $value }}</div><div class="text-secondary">{{ $label }}</div></div>
                </div>
            </div></div>
        </div>
    @endforeach

    {{-- Tugas mendatang --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Tugas Mendatang</h3></div>
            @if ($pending->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-circle-check" title="Tidak ada tugas pending" description="Semua tugas sudah dikumpulkan." /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($pending->take(6) as $a)
                        <a href="{{ route('assignments.show', $a) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="avatar bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt me-2"><i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-file-text' }}"></i></span>
                            <div class="me-auto">
                                <div class="fw-bold">{{ $a->title }}</div>
                                <div class="text-secondary small">{{ $a->course->name }}</div>
                            </div>
                            <x-due :date="$a->deadline" />
                        </a>
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

    {{-- Pertemuan mendatang --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Jadwal Pertemuan</h3></div>
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
                            <span class="text-secondary small">{{ $m->date->translatedFormat('d M Y') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Kelas saya --}}
    <div class="col-lg-6">
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
