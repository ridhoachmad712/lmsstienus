@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-pretitle', 'Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
{{-- ===================== STATISTIK ===================== --}}
<div class="row row-cards mb-3">
    @foreach ($statCards as [$label, $value, $sub, $icon, $color, $route])
        <div class="col-6 col-lg-3">
            @if ($route)<a href="{{ route($route) }}" class="card card-sm card-link">@else<div class="card card-sm">@endif
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-{{ $color }} text-white avatar"><i class="ti {{ $icon }} fs-2"></i></span></div>
                        <div class="col">
                            <div class="h1 m-0">{{ $value }}</div>
                            <div class="text-secondary">{{ $label }}</div>
                            @if ($sub)<div class="text-secondary small text-truncate">{{ $sub }}</div>@endif
                        </div>
                    </div>
                </div>
            @if ($route)</a>@else</div>@endif
        </div>
    @endforeach
</div>

@unless ($isAdmin)
    <div class="alert alert-info">Anda login sebagai <strong>Kaprodi{{ $prodi ? ' '.$prodi->name : '' }}</strong>. Pengelolaan terbatas pada lingkup program studi Anda.</div>
@endunless

{{-- Akses cepat sisi mengajar — untuk dosen yang merangkap kaprodi (satu akun) --}}
@if ($teaching)
    <div class="card mb-3 border-primary">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <span class="avatar avatar-lg rounded bg-primary-lt"><i class="ti ti-device-laptop icon-lg"></i></span>
                </div>
                <div class="col-md">
                    <div class="fw-bold">Anda juga dosen pengampu</div>
                    <div class="text-secondary mt-1">
                        Mengampu <strong>{{ $teaching['courses'] }}</strong> kelas.
                        @if ($teaching['ungraded'] > 0)
                            <span class="text-orange ms-1"><i class="ti ti-clipboard-list me-1"></i><strong>{{ $teaching['ungraded'] }}</strong> pengumpulan menunggu dinilai.</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-auto">
                    <a href="{{ route('courses.index') }}" class="btn btn-primary"><i class="ti ti-school me-1"></i>Kelas Saya (mengajar)</a>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ===================== KELOMPOK MENU ===================== --}}
@foreach ($menuGroups as [$groupLabel, $groupIcon, $color, $items])
    <h3 class="text-secondary text-uppercase fs-5 mb-2"><i class="ti {{ $groupIcon }} me-1"></i>{{ $groupLabel }}</h3>
    <div class="row row-cards mb-3">
        @foreach ($items as [$route, $icon, $title, $desc])
            <div class="col-md-6 col-lg-4">
                <a href="{{ route($route) }}" class="card card-link card-sm">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar bg-{{ $color }}-lt me-3"><i class="ti {{ $icon }} fs-2"></i></span>
                        <div>
                            <div class="fw-bold">{{ $title }}</div>
                            <div class="text-secondary small">{{ $desc }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endforeach
@endsection
