@extends('layouts.app')

@section('title', 'SIAKAD')
@section('page-pretitle', 'Sistem Informasi Akademik')
@section('page-title', 'Beranda SIAKAD')

@section('content')
@php
    $menus = $user->isMahasiswa() ? [
        ['krs.index', 'ti-clipboard-list', 'KRS', 'Susun dan ajukan rencana studi', 'blue'],
        ['schedule.index', 'ti-calendar-time', 'Jadwal Kuliah', 'Jadwal resmi perkuliahan', 'azure'],
        ['transkrip.mine', 'ti-certificate', 'KHS & Transkrip', 'Hasil dan riwayat akademik', 'green'],
        ['edom.index', 'ti-star', 'Evaluasi Dosen', 'Isi evaluasi perkuliahan', 'yellow'],
        ['academic.calendar', 'ti-calendar-event', 'Kalender Akademik', 'Agenda akademik kampus', 'purple'],
        ['profile.edit', 'ti-id-badge-2', 'Biodata', 'Data pribadi mahasiswa', 'cyan'],
    ] : ($user->isDosen() ? [
        ['perwalian.index', 'ti-users-group', 'Perwalian', 'Tinjau dan setujui KRS mahasiswa', 'blue'],
        ['schedule.index', 'ti-calendar-time', 'Jadwal Mengajar', 'Jadwal resmi perkuliahan', 'azure'],
        ['academic.calendar', 'ti-calendar-event', 'Kalender Akademik', 'Agenda akademik kampus', 'purple'],
        ['profile.edit', 'ti-id-badge-2', 'Profil Dosen', 'Biodata dan akun', 'cyan'],
    ] : [
        ['admin.dashboard', 'ti-layout-dashboard', 'Dashboard Akademik', 'Ringkasan data akademik', 'blue'],
        ['admin.students.index', 'ti-users', 'Mahasiswa', 'Kelola data mahasiswa', 'green'],
        ['admin.kurikulum.index', 'ti-notebook', 'Kurikulum', 'Kurikulum dan mata kuliah', 'purple'],
        ['admin.academic.index', 'ti-chart-bar', 'Rekap Akademik', 'IPS, IPK, dan progres studi', 'azure'],
        ['admin.edom.index', 'ti-star', 'Rekap EDOM', 'Evaluasi dosen oleh mahasiswa', 'yellow'],
        ['academic.calendar', 'ti-calendar-event', 'Kalender Akademik', 'Agenda akademik kampus', 'cyan'],
    ]);
@endphp

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
