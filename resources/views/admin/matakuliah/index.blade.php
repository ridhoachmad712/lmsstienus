@extends('layouts.app')

@section('title', 'Mata Kuliah')
@section('page-pretitle', 'Admin')
@section('page-title', 'Katalog Mata Kuliah')

@section('page-actions')
    <a href="{{ route('admin.matakuliah.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah Mata Kuliah</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Total: {{ $items->total() }} mata kuliah</h3></div>
    @if ($items->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-book" title="Belum ada mata kuliah" description="Tambahkan mata kuliah ke katalog. Satu mata kuliah bisa punya beberapa kelas paralel (beda dosen)." /></div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Kode</th><th>Nama</th><th>Prodi</th><th class="text-center">SKS</th><th class="text-center">Kelas paralel</th><th></th></tr></thead>
                <tbody>
                    @foreach ($items as $mk)
                        <tr>
                            <td class="fw-bold">{{ $mk->code }}</td>
                            <td>{{ $mk->name }}</td>
                            <td>{{ $mk->prodi?->name ?? '—' }}</td>
                            <td class="text-center">{{ $mk->sks }}</td>
                            <td class="text-center">{{ $mk->courses_count }}</td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    <a href="{{ route('admin.matakuliah.edit', $mk) }}" class="btn btn-sm" title="Edit" data-bs-toggle="tooltip"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="{{ route('admin.matakuliah.destroy', $mk) }}" data-confirm="Hapus mata kuliah {{ $mk->code }}?@if ($mk->courses_count > 0) (Akan ditolak karena masih ada {{ $mk->courses_count }} kelas.)@endif">
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
@endsection
