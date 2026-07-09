@extends('layouts.app')

@section('title', 'Sesi Kuliah')
@section('page-pretitle', 'Data Master')
@section('page-title', 'Sesi Kuliah')

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $slots->count() }} sesi</h3></div>
            @if ($slots->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-clock-hour-8" title="Belum ada sesi" description="Tambahkan slot jam baku, mis. Sesi 1 (08:00–09:40)." /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th class="text-center">Urutan</th><th>Nama</th><th>Jam</th><th class="text-center">Jadwal</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($slots as $s)
                                <tr>
                                    <td class="text-center text-secondary">{{ $s->sort }}</td>
                                    <td class="fw-bold">{{ $s->name }}</td>
                                    <td>{{ $s->start_time }}–{{ $s->end_time }}</td>
                                    <td class="text-center text-secondary">{{ $s->schedules_count }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.timeslots.destroy', $s) }}" onsubmit="return confirm('Hapus sesi {{ $s->name }}?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <form class="card" method="POST" action="{{ route('admin.timeslots.store') }}">
            @csrf
            <div class="card-header"><h3 class="card-title">Tambah Sesi</h3></div>
            <div class="card-body">
                <div class="mb-2"><label class="form-label required">Nama</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Sesi 1" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-6 mb-2"><label class="form-label required">Mulai</label>
                        <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}" required>
                        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 mb-2"><label class="form-label required">Selesai</label>
                        <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}" required>
                        @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-1"><label class="form-label">Urutan</label>
                    <input type="number" name="sort" class="form-control" value="{{ old('sort', 0) }}" min="0" placeholder="0">
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button></div>
        </form>
    </div>
</div>
@endsection
