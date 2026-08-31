@extends('layouts.guest')

@section('title', 'Pilih Sistem')

@section('content')
<div class="text-center mb-4">
    <h1 class="mb-1">Layanan Akademik</h1>
    <div class="text-secondary">Pilih aplikasi yang ingin Anda buka.</div>
</div>

<div class="row g-3">
    <div class="col-12">
        <a href="{{ url('/siakad') }}" class="card card-link card-link-pop text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <span class="avatar avatar-xl bg-blue-lt"><i class="ti ti-building-bank fs-1"></i></span>
                <div class="flex-fill">
                    <h2 class="mb-1">SIAKAD</h2>
                    <div class="text-secondary">KRS, jadwal, nilai, KHS, transkrip, dan administrasi akademik</div>
                    <span class="badge bg-blue-lt mt-2">Buka SIAKAD</span>
                </div>
                <i class="ti ti-chevron-right fs-2"></i>
            </div>
        </a>
    </div>
    <div class="col-12">
        <a href="{{ route('portal.lms') }}" class="card card-link card-link-pop text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <span class="avatar avatar-xl bg-green-lt"><i class="ti ti-device-laptop fs-1"></i></span>
                <div class="flex-fill">
                    <h2 class="mb-1">LMS</h2>
                    <div class="text-secondary">Kelas, materi, tugas, kuis, diskusi, presensi, dan penilaian pembelajaran</div>
                    <span class="badge bg-green-lt mt-2">Buka LMS</span>
                </div>
                <i class="ti ti-chevron-right fs-2"></i>
            </div>
        </a>
    </div>
</div>

@endsection
