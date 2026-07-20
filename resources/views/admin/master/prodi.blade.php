@extends('layouts.app')

@section('title', 'Program Studi')
@section('page-pretitle', 'Data Master')
@section('page-title', 'Program Studi')

@section('page-actions')
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import"><i class="ti ti-file-import me-1"></i>Import CSV</button>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $prodis->count() }} program studi</h3></div>
            @if ($prodis->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-building" title="Belum ada prodi" description="Tambahkan program studi di sebelah kanan atau import CSV." /></div>
            @else
                @include('partials.bulk-select', ['deleteRoute' => route('admin.prodi.bulkDestroy'), 'noun' => 'prodi'])
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr>
                            <th class="w-1"><input type="checkbox" id="sel-all" class="form-check-input m-0"></th>
                            <th>Kode</th><th>Nama</th><th>Kaprodi</th><th class="text-center">Pengguna</th><th class="text-center">Kelas</th><th></th>
                        </tr></thead>
                        <tbody>
                            @foreach ($prodis as $p)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input m-0 row-select" value="{{ $p->id }}"></td>
                                    <td class="fw-bold">{{ $p->code }}</td>
                                    <td>{{ $p->name }}</td>
                                    <td>
                                        @if ($p->kaprodi)
                                            <span class="badge bg-purple-lt"><i class="ti ti-user-star me-1"></i>{{ $p->kaprodi->name }}</span>
                                        @else
                                            <span class="text-secondary small">— belum ditunjuk —</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-secondary">{{ $p->users_count }}</td>
                                    <td class="text-center text-secondary">{{ $p->courses_count }}</td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end">
                                            <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#modal-edit-{{ $p->id }}" title="Edit / tunjuk kaprodi"><i class="ti ti-edit"></i></button>
                                            <form method="POST" action="{{ route('admin.prodi.destroy', $p) }}" data-confirm="Hapus prodi {{ $p->name }}?@if ($p->users_count > 0 || $p->courses_count > 0) (Akan ditolak — masih dipakai.)@endif">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-danger" title="Hapus"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </div>
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
        <form class="card" method="POST" action="{{ route('admin.prodi.store') }}">
            @csrf
            <div class="card-header"><h3 class="card-title">Tambah Prodi</h3></div>
            <div class="card-body">
                <div class="mb-2"><label class="form-label required">Kode</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="AK / MN" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-1"><label class="form-label required">Nama</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Akuntansi" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button></div>
        </form>
    </div>
</div>

{{-- Modal edit prodi + penunjukan kaprodi --}}
@foreach ($prodis as $p)
    <div class="modal modal-blur fade" id="modal-edit-{{ $p->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('admin.prodi.update', $p) }}">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Prodi — {{ $p->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-4"><label class="form-label required">Kode</label>
                            <input type="text" name="code" class="form-control" value="{{ $p->code }}" required></div>
                        <div class="col-8"><label class="form-label required">Nama</label>
                            <input type="text" name="name" class="form-control" value="{{ $p->name }}" required></div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Kaprodi (dosen penanggung jawab)</label>
                        <select name="kaprodi_id" class="form-select">
                            <option value="">— tidak ditunjuk —</option>
                            @foreach (($candidates[$p->id] ?? collect()) as $u)
                                <option value="{{ $u->id }}" @selected($p->kaprodi_id === $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">Dosen yang ditunjuk tetap dapat mengajar, sekaligus memperoleh menu pengelolaan prodi ini — cukup satu akun. Daftar berisi dosen/kaprodi di prodi ini.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button></div>
            </form>
        </div>
    </div>
@endforeach

@include('partials.import-modal', [
    'importRoute' => route('admin.prodi.import'),
    'title' => 'Import Program Studi (CSV)',
    'columns' => 'kode, nama',
    'note' => 'Kode yang sudah ada akan dilewati.',
])
@endsection
