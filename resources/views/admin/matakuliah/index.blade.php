@extends('layouts.app')

@section('title', 'Mata Kuliah')
@section('page-pretitle', 'Admin')
@section('page-title', 'Katalog Mata Kuliah')

@section('page-actions')
    <div class="btn-list">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import"><i class="ti ti-file-import me-1"></i>Import CSV</button>
        <a href="{{ route('admin.matakuliah.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Mata Kuliah</a>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Total: {{ $items->total() }} mata kuliah</h3>
        <form method="GET" action="{{ route('admin.matakuliah.index') }}" class="ms-auto">
            <select name="kurikulum" class="form-select" onchange="this.form.submit()" style="min-width:220px">
                <option value="">Semua kurikulum</option>
                @foreach ($kurikulums as $kur)
                    <option value="{{ $kur->id }}" @selected($kurikulumId === $kur->id)>{{ $kur->name }} ({{ $kur->year }})</option>
                @endforeach
            </select>
        </form>
    </div>
    @if ($items->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-book" title="Belum ada mata kuliah" description="Tambahkan MK; atur semester, jenis, prasyarat, dan tautkan ke kurikulum." /></div>
    @else
        @include('partials.bulk-select', ['deleteRoute' => route('admin.matakuliah.bulkDestroy'), 'noun' => 'mata kuliah'])
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th class="w-1"><input type="checkbox" id="sel-all" class="form-check-input m-0"></th>
                    <th>Kode</th><th>Nama</th><th class="text-center">SKS</th><th class="text-center">Smt</th><th class="text-center">Jenis</th><th>Kurikulum</th><th class="text-center">Kelas</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($items as $mk)
                        <tr>
                            <td><input type="checkbox" class="form-check-input m-0 row-select" value="{{ $mk->id }}"></td>
                            <td class="fw-bold">{{ $mk->code }}</td>
                            <td>{{ $mk->name }}<div class="small text-secondary">{{ $mk->prodi?->name ?? '—' }}</div></td>
                            <td class="text-center">{{ $mk->sks }}</td>
                            <td class="text-center">{{ $mk->semester_no ?? '—' }}</td>
                            <td class="text-center"><span class="badge bg-{{ $mk->jenis === 'pilihan' ? 'azure' : 'blue' }}-lt text-capitalize">{{ $mk->jenis }}</span></td>
                            <td class="text-secondary small">{{ $mk->kurikulum?->name ?? '—' }}</td>
                            <td class="text-center">{{ $mk->courses_count }}</td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    <a href="{{ route('admin.matakuliah.edit', $mk) }}" class="btn btn-sm" title="Edit" data-bs-toggle="tooltip"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="{{ route('admin.matakuliah.destroy', $mk) }}" data-confirm="Hapus mata kuliah {{ $mk->code }}?@if ($mk->courses_count > 0) (Akan ditolak — masih ada {{ $mk->courses_count }} kelas.)@endif">
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
    'importRoute' => route('admin.matakuliah.import'),
    'title' => 'Import Mata Kuliah (CSV)',
    'columns' => 'kode, nama, sks, semester, jenis, kode_prodi',
    'note' => 'jenis: wajib/pilihan (default wajib). kode_prodi mengikuti Data Master; kaprodi otomatis memakai prodinya. Kode yang sudah ada dilewati.',
    'templateRoute' => route('admin.master.template', 'matakuliah'),
])
@endsection
