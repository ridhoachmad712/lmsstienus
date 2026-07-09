@extends('layouts.app')

@section('title', 'Ruangan')
@section('page-pretitle', 'Data Master')
@section('page-title', 'Ruangan')

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $rooms->count() }} ruangan</h3></div>
            @if ($rooms->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-door" title="Belum ada ruangan" description="Tambahkan ruangan di sebelah kanan." /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Kode</th><th>Nama</th><th class="text-center">Kapasitas</th><th>Catatan</th><th class="text-center">Jadwal</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($rooms as $r)
                                <tr>
                                    <td class="fw-bold">{{ $r->code ?? '—' }}</td>
                                    <td>{{ $r->name }}</td>
                                    <td class="text-center text-secondary">{{ $r->capacity ?? '—' }}</td>
                                    <td class="text-secondary small">{{ $r->note ?? '—' }}</td>
                                    <td class="text-center text-secondary">{{ $r->schedules_count }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.rooms.destroy', $r) }}" onsubmit="return confirm('Hapus ruangan {{ $r->name }}?');">
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
        <form class="card" method="POST" action="{{ route('admin.rooms.store') }}">
            @csrf
            <div class="card-header"><h3 class="card-title">Tambah Ruangan</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-5 mb-2"><label class="form-label">Kode</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="R201">
                    </div>
                    <div class="col-7 mb-2"><label class="form-label required">Nama</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Lab Komputer" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-2"><label class="form-label">Kapasitas</label>
                    <input type="number" name="capacity" class="form-control" value="{{ old('capacity') }}" min="1" placeholder="40">
                </div>
                <div class="mb-1"><label class="form-label">Catatan</label>
                    <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="opsional">
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button></div>
        </form>
    </div>
</div>
@endsection
