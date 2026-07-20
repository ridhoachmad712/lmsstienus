@extends('layouts.app')

@section('title', 'Kurikulum')
@section('page-pretitle', 'Admin')
@section('page-title', 'Kurikulum')

@section('page-actions')
    <div class="btn-list">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import"><i class="ti ti-file-import me-1"></i>Import CSV</button>
        <a href="{{ route('admin.kurikulum.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Kurikulum</a>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Total: {{ $items->total() }} kurikulum</h3></div>
    @if ($items->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-notebook" title="Belum ada kurikulum" description="Tambahkan versi kurikulum per prodi, lalu susun mata kuliahnya." /></div>
    @else
        @include('partials.bulk-select', ['deleteRoute' => route('admin.kurikulum.bulkDestroy'), 'noun' => 'kurikulum'])
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th class="w-1"><input type="checkbox" id="sel-all" class="form-check-input m-0"></th>
                    <th>Nama</th><th>Prodi</th><th class="text-center">Tahun</th><th class="text-center">Mata Kuliah</th><th class="text-center">Status</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($items as $k)
                        <tr>
                            <td><input type="checkbox" class="form-check-input m-0 row-select" value="{{ $k->id }}"></td>
                            <td class="fw-bold">{{ $k->name }}</td>
                            <td>{{ $k->prodi?->name ?? '—' }}</td>
                            <td class="text-center">{{ $k->year }}</td>
                            <td class="text-center">{{ $k->mata_kuliah_count }}</td>
                            <td class="text-center">
                                @if ($k->is_active)<span class="badge bg-green-lt">Aktif</span>@else<span class="badge bg-secondary-lt">Nonaktif</span>@endif
                            </td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    <a href="{{ route('admin.matakuliah.index', ['kurikulum' => $k->id]) }}" class="btn btn-sm" title="Lihat mata kuliah" data-bs-toggle="tooltip"><i class="ti ti-list"></i></a>
                                    <a href="{{ route('admin.kurikulum.edit', $k) }}" class="btn btn-sm" title="Edit" data-bs-toggle="tooltip"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="{{ route('admin.kurikulum.destroy', $k) }}" data-confirm="Hapus kurikulum {{ $k->name }}?@if ($k->mata_kuliah_count > 0) (Akan ditolak — masih ada {{ $k->mata_kuliah_count }} MK.)@endif">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger" title="Hapus" data-bs-toggle="tooltip"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex">{{ $items->links() }}</div>
    @endif
</div>

@include('partials.import-modal', [
    'importRoute' => route('admin.kurikulum.import'),
    'title' => 'Import Kurikulum (CSV)',
    'columns' => 'nama, tahun, kode_prodi, aktif',
    'note' => 'kode_prodi mengikuti kode di Data Master (mis. AK/MN); kaprodi otomatis memakai prodinya. Kolom aktif: 1/aktif/ya.',
])
@endsection
