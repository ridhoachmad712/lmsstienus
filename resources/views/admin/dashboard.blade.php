@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-pretitle', 'Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
@php($isAdmin = auth()->user()->isAdmin())

{{-- ===================== STATISTIK ===================== --}}
@php($cards = [
    ['label' => 'Dosen', 'value' => $stats['dosen'], 'icon' => 'ti-user-star', 'color' => 'blue', 'route' => $isAdmin ? 'admin.staff.index' : null],
    ['label' => 'Mahasiswa', 'value' => $stats['mahasiswa'], 'icon' => 'ti-users', 'color' => 'green', 'route' => 'admin.students.index'],
    ['label' => 'Kelas aktif', 'value' => $stats['active_courses'], 'sub' => 'dari '.$stats['courses'].' kelas', 'icon' => 'ti-school', 'color' => 'azure', 'route' => 'admin.courses.index'],
    ['label' => 'Semester aktif', 'value' => count($activeKeys), 'sub' => collect($activeKeys)->map(fn ($k) => \App\Models\Semester::keyLabel($k))->implode(', '), 'icon' => 'ti-calendar-stats', 'color' => 'purple', 'route' => $isAdmin ? 'admin.semesters.index' : null],
])
<div class="row row-cards mb-3">
    @foreach ($cards as $c)
        <div class="col-6 col-lg-3">
            @if ($c['route'])<a href="{{ route($c['route']) }}" class="card card-sm card-link">@else<div class="card card-sm">@endif
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-{{ $c['color'] }} text-white avatar"><i class="ti {{ $c['icon'] }} fs-2"></i></span></div>
                        <div class="col">
                            <div class="h1 m-0">{{ $c['value'] }}</div>
                            <div class="text-secondary">{{ $c['label'] }}</div>
                            @if (! empty($c['sub']))<div class="text-secondary small text-truncate">{{ $c['sub'] }}</div>@endif
                        </div>
                    </div>
                </div>
            @if ($c['route'])</a>@else</div>@endif
        </div>
    @endforeach
</div>

{{-- ===================== STATUS KRS ===================== --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <span class="avatar avatar-lg rounded bg-{{ $krs['open'] ? 'green' : 'secondary' }}-lt"><i class="ti ti-clipboard-list icon-lg"></i></span>
            </div>
            <div class="col-md">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="fw-bold">Pengisian KRS</span>
                    <span class="badge bg-{{ $krs['open'] ? 'green' : 'red' }}-lt">
                        <i class="ti ti-{{ $krs['open'] ? 'lock-open' : 'lock' }} me-1"></i>{{ $krs['open'] ? 'DIBUKA' : 'DITUTUP' }}
                    </span>
                    <span class="text-secondary small">Periode {{ $krs['period'] }} · maks {{ $krs['max_sks'] }} SKS</span>
                </div>
                <div class="mt-1">
                    @if ($krs['pending'] > 0)
                        <span class="text-orange"><i class="ti ti-clock-hour-4 me-1"></i><strong>{{ $krs['pending'] }}</strong> mahasiswa menunggu persetujuan dosen wali</span>
                    @else
                        <span class="text-secondary small">Tidak ada pengajuan KRS yang menunggu.</span>
                    @endif
                </div>
            </div>
            <div class="col-md-auto">
                <div class="btn-list justify-content-end">
                    @if ($isAdmin)
                        <form method="POST" action="{{ route('admin.semesters.krs') }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="krs_max_sks" value="{{ $krs['max_sks'] }}">
                            @if ($krs['open'])
                                <button class="btn btn-outline-danger" onclick="return confirm('Tutup pengisian KRS?')"><i class="ti ti-lock me-1"></i>Tutup KRS</button>
                            @else
                                <input type="hidden" name="krs_open" value="1">
                                <button class="btn btn-success" onclick="return confirm('Buka pengisian KRS untuk periode {{ $krs['period'] }}?')"><i class="ti ti-lock-open me-1"></i>Buka KRS</button>
                            @endif
                        </form>
                        <a href="{{ route('admin.semesters.index') }}" class="btn btn-icon" title="Atur di Kelola Semester" data-bs-toggle="tooltip"><i class="ti ti-settings"></i></a>
                    @else
                        <span class="text-secondary small">Buka/tutup KRS diatur oleh admin.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@unless ($isAdmin)
    <div class="alert alert-info">Anda login sebagai <strong>Kaprodi{{ $prodi ? ' '.$prodi->name : '' }}</strong>. Pengelolaan terbatas pada lingkup program studi Anda.</div>
@endunless

{{-- ===================== MENU AKADEMIK ===================== --}}
@php($akademik = [
    ['admin.academic.index', 'ti-chart-bar', 'Rekap Akademik', 'IPK/IPS & deteksi bermasalah'],
    ['admin.courses.index', 'ti-school', 'Pengawasan Kelas', 'Pantau kelas & progresnya'],
    ['admin.students.index', 'ti-users', 'Mahasiswa', 'Kelola & impor akun mahasiswa'],
    ['admin.kurikulum.index', 'ti-notebook', 'Kurikulum', 'Versi kurikulum per prodi'],
    ['admin.matakuliah.index', 'ti-book', 'Mata Kuliah', 'Katalog MK, semester & prasyarat'],
    ['academic.calendar', 'ti-calendar-event', 'Kalender Akademik', 'Agenda KRS/UTS/UAS/libur'],
])
@if ($isAdmin)
    @php($akademik[] = ['admin.semesters.index', 'ti-calendar-stats', 'Kelola Semester', 'Semester aktif & pengisian KRS'])
@endif

<h3 class="text-secondary text-uppercase fs-5 mb-2"><i class="ti ti-books me-1"></i>Akademik</h3>
<div class="row row-cards mb-3">
    @foreach ($akademik as [$route, $icon, $title, $desc])
        <div class="col-md-6 col-lg-4">
            <a href="{{ route($route) }}" class="card card-link card-sm">
                <div class="card-body d-flex align-items-center">
                    <span class="avatar bg-blue-lt me-3"><i class="ti {{ $icon }} fs-2"></i></span>
                    <div>
                        <div class="fw-bold">{{ $title }}</div>
                        <div class="text-secondary small">{{ $desc }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

{{-- ===================== MENU SISTEM (admin) ===================== --}}
@if ($isAdmin)
    @php($sistem = [
        ['admin.staff.index', 'ti-user-star', 'Dosen & Kaprodi', 'Kelola akun staf + prodi'],
        ['admin.settings.edit', 'ti-palette', 'Tampilan', 'Branding & tema aplikasi'],
        ['admin.gradeScale.edit', 'ti-award', 'Skala Nilai', 'Ambang konversi huruf'],
        ['admin.ai.edit', 'ti-sparkles', 'Integrasi AI', 'Kunci & model AI'],
        ['admin.activity.index', 'ti-history', 'Riwayat Aktivitas', 'Log tindakan pengguna'],
        ['admin.backups.index', 'ti-database', 'Backup', 'Cadangan basis data'],
    ])
    <h3 class="text-secondary text-uppercase fs-5 mb-2"><i class="ti ti-settings me-1"></i>Sistem</h3>
    <div class="row row-cards">
        @foreach ($sistem as [$route, $icon, $title, $desc])
            <div class="col-md-6 col-lg-4">
                <a href="{{ route($route) }}" class="card card-link card-sm">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar bg-purple-lt me-3"><i class="ti {{ $icon }} fs-2"></i></span>
                        <div>
                            <div class="fw-bold">{{ $title }}</div>
                            <div class="text-secondary small">{{ $desc }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
