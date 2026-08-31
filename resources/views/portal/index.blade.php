@extends('layouts.guest')

@section('title', 'Pilih Sistem')

@section('content')
<div class="text-center mb-4">
    @if ($user)
        <div class="text-secondary">Selamat datang,</div>
        <h1 class="mb-1">{{ $user->name }}</h1>
        <span class="badge bg-primary-lt text-capitalize">{{ $user->role }}</span>
    @else
        <h1 class="mb-1">Pilih Sistem</h1>
        <div class="text-secondary">Pilih layanan yang akan digunakan, kemudian masuk dengan akun layanan tersebut.</div>
    @endif
</div>

<div class="row g-3">
    <div class="col-12">
        <a href="{{ route('portal.siakad') }}" class="card card-link card-link-pop text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <span class="avatar avatar-xl bg-blue-lt"><i class="ti ti-building-bank fs-1"></i></span>
                <div class="flex-fill">
                    <h2 class="mb-1">SIAKAD</h2>
                    <div class="text-secondary">KRS, jadwal, nilai, KHS, transkrip, dan administrasi akademik</div>
                    <span class="badge bg-blue-lt mt-2">Masuk dengan akun SIAKAD</span>
                    @unless($siakadReady)<span class="badge bg-yellow-lt mt-2">Menunggu konfigurasi alamat</span>@endunless
                </div>
                <i class="ti ti-chevron-right fs-2"></i>
            </div>
        </a>
    </div>
    <div class="col-12">
        <a href="{{ $user ? route('portal.lms') : route('login') }}" class="card card-link card-link-pop text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3 py-4">
                <span class="avatar avatar-xl bg-green-lt"><i class="ti ti-device-laptop fs-1"></i></span>
                <div class="flex-fill">
                    <h2 class="mb-1">LMS</h2>
                    <div class="text-secondary">Kelas, materi, tugas, kuis, diskusi, presensi, dan penilaian pembelajaran</div>
                    <span class="badge bg-green-lt mt-2">Masuk dengan akun LMS</span>
                </div>
                <i class="ti ti-chevron-right fs-2"></i>
            </div>
        </a>
    </div>
</div>

@if ($user)
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button class="btn btn-link text-secondary"><i class="ti ti-logout me-1"></i>Keluar dari LMS</button>
    </form>
@endif
@endsection
