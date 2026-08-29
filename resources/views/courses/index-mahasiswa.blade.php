@extends('layouts.app')

@section('title', 'Mata Kuliah Saya')
@section('page-pretitle', 'Perkuliahan')
@section('page-title', 'Mata Kuliah Saya')

@section('page-actions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-join-course">
        <i class="ti ti-login-2 me-1"></i>Gabung Kelas
    </button>
@endsection

@section('content')
@if ($courses->isEmpty())
    <div class="card">
        <div class="card-body">
            <x-empty-state icon="ti-school" title="Belum ada kelas aktif"
                description="Minta kode kelas kepada dosen, lalu gunakan tombol Gabung Kelas.">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-join-course"><i class="ti ti-login-2 me-1"></i>Gabung Kelas</button>
            </x-empty-state>
        </div>
    </div>
@else
    {{-- Mobile: list ringkas, seluruh baris dapat disentuh. --}}
    <div class="card d-md-none overflow-hidden">
        <div class="list-group list-group-flush">
            @foreach ($courses as $course)
                <a href="{{ route('courses.show', $course) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                    <span class="avatar avatar-md bg-{{ $course->color() }}-lt flex-shrink-0"><i class="ti ti-book-2 fs-2"></i></span>
                    <div class="min-w-0 flex-fill">
                        <div class="fw-bold text-truncate">{{ $course->name }}</div>
                        <div class="text-secondary small text-truncate">{{ $course->lecturer->name }}</div>
                    </div>
                    <i class="ti ti-chevron-right text-secondary flex-shrink-0"></i>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Tablet/desktop: pertahankan card grid. --}}
    <div class="row row-cards d-none d-md-flex">
        @foreach ($courses as $course)
            <div class="col-md-6 col-lg-4">
                <div class="card card-lift overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="avatar bg-{{ $course->color() }}-lt me-2"><i class="ti ti-school"></i></span>
                            <div class="min-w-0"><h3 class="card-title mb-0 text-truncate">{{ $course->name }}</h3><div class="text-secondary small text-truncate">{{ $course->lecturer->name }}</div></div>
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

<div class="modal modal-blur fade" id="modal-join-course" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('enrollments.join') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Gabung Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <label class="form-label required" for="join_code">Kode kelas</label>
                <input id="join_code" name="join_code" type="text" maxlength="12"
                    class="form-control form-control-lg text-uppercase @error('join_code') is-invalid @enderror"
                    value="{{ old('join_code') }}" placeholder="Contoh: A7K9P2" autocomplete="off" required autofocus>
                <div class="form-hint">Kode 6 karakter diberikan oleh dosen pengampu. Mahasiswa lintas program studi dapat memakai kode yang sama.</div>
                @error('join_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Gabung Sekarang</button>
            </div>
        </form>
    </div>
</div>

@if ($errors->has('join_code'))
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-join-course')).show());</script>
    @endpush
@endif
@endsection
