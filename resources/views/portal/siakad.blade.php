@extends('layouts.app')

@section('title', 'SIAKAD')
@section('page-pretitle', 'Sistem Informasi Akademik')
@section('page-title', 'Beranda SIAKAD')

@section('content')
@php
    $menus = match ($dashboardType) {
        'student' => [
            ['krs.index', 'ti-clipboard-list', 'KRS', 'Susun dan ajukan rencana studi', 'blue'],
            ['schedule.index', 'ti-calendar-time', 'Jadwal Kuliah', 'Jadwal resmi perkuliahan', 'azure'],
            ['transkrip.mine', 'ti-certificate', 'KHS & Transkrip', 'Hasil dan riwayat akademik', 'green'],
            ['edom.index', 'ti-star', 'Evaluasi Dosen', 'Isi evaluasi perkuliahan', 'yellow'],
            ['academic.calendar', 'ti-calendar-event', 'Kalender Akademik', 'Agenda akademik kampus', 'purple'],
            ['profile.edit', 'ti-id-badge-2', 'Biodata', 'Data pribadi mahasiswa', 'cyan'],
        ],
        'lecturer' => [
            ['perwalian.index', 'ti-users-group', 'Perwalian', 'Tinjau dan setujui KRS mahasiswa', 'blue'],
            ['schedule.index', 'ti-calendar-time', 'Jadwal Mengajar', 'Jadwal resmi perkuliahan', 'azure'],
            ['academic.calendar', 'ti-calendar-event', 'Kalender Akademik', 'Agenda akademik kampus', 'purple'],
            ['profile.edit', 'ti-id-badge-2', 'Profil Dosen', 'Biodata dan akun', 'cyan'],
        ],
        default => [
            ['admin.dashboard', 'ti-layout-dashboard', 'Pengelolaan Akademik', 'Data master dan konfigurasi akademik', 'blue'],
            ['admin.students.index', 'ti-users', 'Mahasiswa', 'Kelola data dan kelengkapan mahasiswa', 'green'],
            ['admin.kurikulum.index', 'ti-notebook', 'Kurikulum', 'Kurikulum dan mata kuliah', 'purple'],
            ['admin.academic.index', 'ti-chart-bar', 'Rekap Akademik', 'IPS, IPK, dan progres studi', 'azure'],
            ['admin.edom.index', 'ti-star', 'Rekap EDOM', 'Evaluasi dosen oleh mahasiswa', 'yellow'],
            ['academic.calendar', 'ti-calendar-event', 'Kalender Akademik', 'Agenda akademik kampus', 'cyan'],
        ],
    };
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div class="text-secondary">
        <i class="ti ti-calendar-stats me-1"></i>Periode aktif:
        <strong>{{ $activePeriods->implode(', ') }}</strong>
        @if($dashboardType === 'staff')
            <span class="mx-1">·</span>{{ $scopeLabel }}
        @endif
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-{{ $krsOpen ? 'green' : 'secondary' }}-lt"><i class="ti ti-{{ $krsOpen ? 'lock-open' : 'lock' }} me-1"></i>KRS {{ $krsOpen ? 'dibuka' : 'ditutup' }}</span>
        <span class="badge bg-{{ $edomOpen ? 'green' : 'secondary' }}-lt"><i class="ti ti-star me-1"></i>EDOM {{ $edomOpen ? 'dibuka' : 'ditutup' }}</span>
    </div>
</div>

<div class="row row-cards mb-3">
    @foreach($statCards as [$label, $value, $sub, $icon, $color, $route])
        <div class="col-6 col-lg-3">
            <a href="{{ route($route) }}" class="card card-sm card-link h-100 text-decoration-none">
                <div class="card-body">
                    <div class="row align-items-center g-2">
                        <div class="col-auto"><span class="avatar bg-{{ $color }}-lt"><i class="ti {{ $icon }} fs-2"></i></span></div>
                        <div class="col overflow-hidden">
                            <div class="h2 mb-0 text-truncate">{{ $value }}</div>
                            <div class="fw-medium">{{ $label }}</div>
                            <div class="text-secondary small text-truncate">{{ $sub }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

@if($dashboardType === 'student')
    <div class="row row-cards mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-clipboard-list me-2"></i>Status Rencana Studi</h3></div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-{{ $academic['krs_status'] === 'disetujui' ? 'green' : ($academic['krs_status'] === 'diajukan' ? 'yellow' : 'secondary') }}-lt">{{ strtoupper($academic['krs_status']) }}</span>
                                <strong>{{ $academic['sks_krs'] }} dari {{ $quota }} SKS</strong>
                            </div>
                            <div class="text-secondary">
                                @if($academic['krs_status'] === 'diajukan')
                                    KRS sedang menunggu persetujuan dosen wali.
                                @elseif($academic['krs_status'] === 'disetujui')
                                    KRS telah disetujui dan menjadi dasar akses kelas LMS.
                                @elseif($krsOpen)
                                    Periode KRS dibuka. Lengkapi pilihan kelas lalu ajukan ke dosen wali.
                                @else
                                    Belum ada KRS yang disetujui pada periode ini.
                                @endif
                            </div>
                            <div class="small mt-2">
                                <span class="badge bg-{{ $academic['status_color'] }}-lt">Status {{ ucfirst($academic['status']) }}</span>
                                @if($academic['semester_ke'])<span class="text-secondary ms-1">Semester {{ $academic['semester_ke'] }}</span>@endif
                            </div>
                        </div>
                        <a href="{{ route('krs.index') }}" class="btn btn-primary"><i class="ti ti-arrow-right me-1"></i>Buka KRS</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-user-star me-2"></i>Dosen Wali</h3></div>
                <div class="card-body">
                    @if($user->advisor)
                        <div class="fw-bold">{{ $user->advisor->name }}</div>
                        <div class="text-secondary">{{ $user->advisor->nim_nip ?: 'NIP belum tersedia' }}</div>
                    @else
                        <div class="text-danger"><i class="ti ti-alert-triangle me-1"></i>Belum ditetapkan</div>
                        <div class="text-secondary small mt-1">Hubungi admin atau kaprodi sebelum mengajukan KRS.</div>
                    @endif
                    @if($edomOpen && $edomPending > 0)
                        <div class="alert alert-warning py-2 mt-3 mb-0"><strong>{{ $edomPending }}</strong> evaluasi dosen belum diisi. <a href="{{ route('edom.index') }}">Isi EDOM</a></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@elseif($dashboardType === 'lecturer')
    @if($pendingAdvisees > 0 || $atRiskAdvisees > 0)
        <div class="card mb-4 border-warning">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="fw-bold"><i class="ti ti-bell-ringing text-warning me-1"></i>Tindakan perwalian diperlukan</div>
                    <div class="text-secondary mt-1">
                        @if($pendingAdvisees > 0)<strong>{{ $pendingAdvisees }}</strong> mahasiswa menunggu keputusan KRS.@endif
                        @if($atRiskAdvisees > 0)<strong class="ms-1">{{ $atRiskAdvisees }}</strong> mahasiswa memiliki IPK di bawah 2,75.@endif
                    </div>
                </div>
                <a href="{{ route('perwalian.index') }}" class="btn btn-warning"><i class="ti ti-users-group me-1"></i>Tinjau Perwalian</a>
            </div>
        </div>
    @else
        <div class="alert alert-success mb-4"><i class="ti ti-circle-check me-1"></i>Tidak ada pengajuan KRS atau mahasiswa berisiko yang perlu ditindaklanjuti.</div>
    @endif
@else
    <div class="row row-cards mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-alert-circle me-2"></i>Pusat Tindakan Akademik</h3></div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.academic.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <span class="avatar bg-{{ $pendingKrs ? 'orange' : 'green' }}-lt me-3"><i class="ti ti-clipboard-check"></i></span>
                        <div class="flex-fill"><div class="fw-bold">KRS menunggu persetujuan</div><div class="text-secondary small">{{ $pendingKrs ? $pendingKrs.' mahasiswa belum memperoleh keputusan wali' : 'Seluruh pengajuan telah ditangani' }}</div></div>
                        <span class="badge bg-{{ $pendingKrs ? 'orange' : 'green' }}-lt">{{ $pendingKrs }}</span>
                    </a>
                    <a href="{{ route('admin.courses.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <span class="avatar bg-{{ ($coursesWithoutSchedule + $coursesWithoutSubject) ? 'red' : 'green' }}-lt me-3"><i class="ti ti-school"></i></span>
                        <div class="flex-fill"><div class="fw-bold">Kesiapan kelas semester aktif</div><div class="text-secondary small">{{ $coursesWithoutSchedule }} tanpa jadwal · {{ $coursesWithoutSubject }} tanpa mata kuliah</div></div>
                        <i class="ti ti-chevron-right text-secondary"></i>
                    </a>
                    <a href="{{ route('admin.students.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <span class="avatar bg-{{ $incompleteStudents ? 'yellow' : 'green' }}-lt me-3"><i class="ti ti-user-exclamation"></i></span>
                        <div class="flex-fill"><div class="fw-bold">Kelengkapan data mahasiswa</div><div class="text-secondary small">{{ $incompleteStudents ? $incompleteStudents.' mahasiswa belum memiliki prodi, kurikulum, atau dosen wali lengkap' : 'Data pokok mahasiswa sudah lengkap' }}</div></div>
                        <i class="ti ti-chevron-right text-secondary"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-info-circle me-2"></i>Status Layanan</h3></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3"><span>Pengisian KRS</span><span class="badge bg-{{ $krsOpen ? 'green' : 'secondary' }}-lt">{{ $krsOpen ? 'DIBUKA' : 'DITUTUP' }}</span></div>
                    <div class="d-flex justify-content-between align-items-center mb-3"><span>Pengisian EDOM</span><span class="badge bg-{{ $edomOpen ? 'green' : 'secondary' }}-lt">{{ $edomOpen ? 'DIBUKA' : 'DITUTUP' }}</span></div>
                    <div class="d-flex justify-content-between align-items-center"><span>Lingkup data</span><strong>{{ $scopeLabel }}</strong></div>
                    @if($user->isAdmin())
                        <a href="{{ route('admin.semesters.index') }}" class="btn btn-outline-primary w-100 mt-4"><i class="ti ti-settings me-1"></i>Atur Semester & KRS</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<h3 class="text-secondary text-uppercase fs-5 mb-2"><i class="ti ti-apps me-1"></i>Akses Cepat</h3>
<div class="row row-cards">
    @foreach($menus as [$route, $icon, $label, $description, $color])
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route($route) }}" class="card card-link card-link-pop h-100 text-decoration-none">
                <div class="card-body d-flex gap-3">
                    <span class="avatar bg-{{ $color }}-lt"><i class="ti {{ $icon }} fs-2"></i></span>
                    <div><h3 class="card-title mb-1">{{ $label }}</h3><div class="text-secondary">{{ $description }}</div></div>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
