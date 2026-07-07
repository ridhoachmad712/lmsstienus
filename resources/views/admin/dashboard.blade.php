@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-pretitle', 'Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
<div class="row row-cards mb-3">
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0">{{ $stats['dosen'] }}</div><div class="text-secondary">Dosen</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0">{{ $stats['mahasiswa'] }}</div><div class="text-secondary">Mahasiswa</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0">{{ $stats['active_courses'] }}<small class="text-secondary fs-4">/{{ $stats['courses'] }}</small></div>
            <div class="text-secondary">Kelas aktif</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h3 m-0">{{ count($activeKeys) }} semester</div>
            <div class="text-secondary small">{{ collect($activeKeys)->map(fn ($k) => \App\Models\Semester::keyLabel($k))->implode(', ') }}</div>
        </div></div>
    </div>
</div>

@php($isAdmin = auth()->user()->isAdmin())
@unless ($isAdmin)
    <div class="alert alert-info">Anda login sebagai <strong>Kaprodi{{ $prodi ? ' '.$prodi->name : '' }}</strong>. Pengelolaan terbatas pada lingkup program studi Anda.</div>
@endunless

<div class="row row-cards">
    @php($menu = [
        ['admin.students.index', 'ti-users', 'Mahasiswa', 'Kelola & impor akun mahasiswa'],
        ['admin.matakuliah.index', 'ti-book', 'Mata Kuliah', 'Katalog MK & kelas paralel'],
    ])
    @if ($isAdmin)
        @php($menu = array_merge($menu, [
            ['admin.staff.index', 'ti-user-star', 'Dosen & Kaprodi', 'Kelola akun staf + prodi'],
            ['admin.semesters.index', 'ti-calendar-stats', 'Kelola Semester', 'Atur semester aktif'],
            ['admin.settings.edit', 'ti-palette', 'Tampilan', 'Branding & tema aplikasi'],
            ['admin.gradeScale.edit', 'ti-award', 'Skala Nilai', 'Ambang konversi huruf'],
            ['admin.ai.edit', 'ti-sparkles', 'Integrasi AI', 'Kunci & model AI'],
            ['admin.activity.index', 'ti-history', 'Riwayat Aktivitas', 'Log tindakan pengguna'],
            ['admin.backups.index', 'ti-database', 'Backup', 'Cadangan basis data'],
        ]))
    @endif
    @foreach ($menu as [$route, $icon, $title, $desc])
        <div class="col-md-6 col-lg-4">
            <a href="{{ route($route) }}" class="card card-link card-sm">
                <div class="card-body d-flex align-items-center">
                    <span class="avatar bg-primary-lt me-3"><i class="ti {{ $icon }} fs-2"></i></span>
                    <div>
                        <div class="fw-bold">{{ $title }}</div>
                        <div class="text-secondary small">{{ $desc }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
