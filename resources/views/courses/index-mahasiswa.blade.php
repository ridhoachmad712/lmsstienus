@extends('layouts.app')

@section('title', 'Kelas Saya')
@section('page-pretitle', 'Perkuliahan')
@section('page-title', 'Kelas Saya')

@section('page-actions')
    <a href="{{ route('krs.index') }}" class="btn"><i class="ti ti-clipboard-list me-1"></i>Lihat KRS</a>
@endsection

@section('content')
@if ($krsPending > 0)
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="ti ti-clock-hour-4 me-2"></i>
        <div class="flex-fill">Ada <strong>{{ $krsPending }}</strong> kelas di KRS Anda yang belum disetujui dosen wali. Kelas otomatis muncul di sini setelah disetujui.</div>
        <a href="{{ route('krs.index') }}" class="btn btn-sm btn-info">Buka KRS</a>
    </div>
@endif

@if ($courses->isEmpty())
    <div class="card">
        <div class="card-body">
            <x-empty-state icon="ti-school" title="Belum ada kelas aktif"
                description="Kelas mengikuti KRS yang Anda programkan. Susun KRS lalu ajukan ke dosen wali — setelah disetujui, kelasnya otomatis muncul di sini.">
                <a href="{{ route('krs.index') }}" class="btn btn-primary"><i class="ti ti-clipboard-list me-1"></i>Buka KRS</a>
            </x-empty-state>
        </div>
    </div>
@else
    <div class="row row-cards">
        @foreach ($courses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card card-lift overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="avatar bg-{{ $course->color() }}-lt me-2"><i class="ti ti-school"></i></span>
                            <h3 class="card-title mb-0">{{ $course->name }}</h3>
                        </div>
                        <div class="text-secondary mb-2">{{ $course->code }}@if ($course->class_name) · {{ $course->class_name }}@endif · {{ $course->semester }} {{ $course->year }}</div>
                        <div class="d-flex align-items-center text-secondary small">
                            <i class="ti ti-user me-1"></i>{{ $course->lecturer->name }}
                            <span class="ms-auto"><i class="ti ti-calendar me-1"></i>{{ $course->meetings_count }} pertemuan</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('courses.show', $course) }}" class="btn w-100">
                            <i class="ti ti-folder-open me-1"></i>Buka Kelas
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
