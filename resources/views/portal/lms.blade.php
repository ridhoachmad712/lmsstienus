@extends('layouts.app')

@section('title', 'LMS')
@section('page-pretitle', 'Learning Management System')
@section('page-title', 'Beranda LMS')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div class="text-secondary">
        <i class="ti ti-filter me-1"></i>{{ $scopeLabel }}
        <span class="mx-1">·</span>Periode {{ $activePeriods->implode(', ') }}
    </div>
    <a href="{{ route('admin.courses.index') }}" class="btn btn-primary"><i class="ti ti-school me-1"></i>Pengawasan Kelas</a>
</div>

<div class="row row-cards mb-3">
    @foreach($statCards as [$label, $value, $sub, $icon, $color, $route])
        <div class="col-6 col-lg-3">
            <a href="{{ route($route) }}" class="card card-sm card-link h-100 text-decoration-none">
                <div class="card-body">
                    <div class="row align-items-center g-2">
                        <div class="col-auto"><span class="avatar bg-{{ $color }}-lt"><i class="ti {{ $icon }} fs-2"></i></span></div>
                        <div class="col overflow-hidden">
                            <div class="h2 mb-0">{{ $value }}</div>
                            <div class="fw-medium">{{ $label }}</div>
                            <div class="text-secondary small text-truncate">{{ $sub }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

@if($teaching)
    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div><i class="ti ti-user-star me-1"></i>Anda juga mengampu <strong>{{ $teaching['courses'] }}</strong> kelas@if($teaching['ungraded'] > 0), dengan <strong>{{ $teaching['ungraded'] }}</strong> pengumpulan belum dinilai@endif.</div>
        <a href="{{ route('dashboard.dosen') }}" class="btn btn-sm btn-info">Buka Dashboard Mengajar</a>
    </div>
@endif

<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-progress me-2"></i>Progres Pertemuan</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <div><div class="h1 mb-0">{{ $meetingProgress }}%</div><div class="text-secondary">Realisasi semester aktif</div></div>
                    <div class="text-end"><strong>{{ $meetingCount }}</strong><div class="text-secondary small">dari target {{ $meetingTarget }}</div></div>
                </div>
                <div class="progress progress-lg"><div class="progress-bar bg-{{ $meetingProgress < 50 ? 'orange' : 'green' }}" style="width: {{ $meetingProgress }}%" role="progressbar" aria-valuenow="{{ $meetingProgress }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                <div class="text-secondary small mt-3">Target dihitung menggunakan acuan 16 pertemuan untuk setiap kelas aktif.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-alert-triangle me-2"></i>Kelas Perlu Perhatian</h3>
                <div class="card-actions"><span class="badge bg-{{ $attentionCount === 0 ? 'green' : 'orange' }}-lt">{{ $attentionCount }} kelas</span></div>
            </div>
            @if($attentionCourses->isEmpty())
                <div class="card-body text-center py-5">
                    <span class="avatar avatar-lg bg-green-lt mb-3"><i class="ti ti-circle-check fs-1"></i></span>
                    <div class="fw-bold">Tidak ada masalah utama</div>
                    <div class="text-secondary">Semua kelas sudah memiliki pertemuan dan tidak ada pengumpulan tertunda.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Kelas</th><th>Dosen</th><th class="text-center">Pertemuan</th><th class="text-center">Belum dinilai</th><th></th></tr></thead>
                        <tbody>
                        @foreach($attentionCourses as $course)
                            <tr>
                                <td><div class="fw-bold">{{ $course->name }}</div><div class="text-secondary small">{{ $course->code }}{{ $course->class_name ? ' · '.$course->class_name : '' }}</div></td>
                                <td>{{ $course->lecturer?->name ?? '—' }}</td>
                                <td class="text-center"><span class="badge bg-{{ $course->meetings_count ? 'azure' : 'red' }}-lt">{{ $course->meetings_count }}</span></td>
                                <td class="text-center"><span class="badge bg-{{ $course->ungraded_count ? 'orange' : 'green' }}-lt">{{ $course->ungraded_count }}</span></td>
                                <td class="text-end"><a href="{{ route('courses.show', $course) }}" class="btn btn-sm btn-icon" title="Buka kelas"><i class="ti ti-chevron-right"></i></a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
