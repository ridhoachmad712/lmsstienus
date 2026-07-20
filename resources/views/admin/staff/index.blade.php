@extends('layouts.app')

@section('title', 'Dosen & Kaprodi')
@section('page-pretitle', 'Admin')
@section('page-title', 'Kelola Dosen & Kaprodi')

@section('page-actions')
    <div class="btn-list">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import"><i class="ti ti-file-import me-1"></i>Import CSV</button>
        <a href="{{ route('admin.staff.create') }}" class="btn btn-primary"><i class="ti ti-user-plus me-1"></i>Tambah Akun</a>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Total: {{ $staff->total() }} akun</h3>
        <form method="GET" action="{{ route('admin.staff.index') }}" class="ms-auto d-flex gap-2 flex-wrap">
            <select name="role" class="form-select" onchange="this.form.submit()" style="min-width:140px">
                <option value="">Semua peran</option>
                <option value="dosen" @selected($role === 'dosen')>Dosen</option>
                <option value="kaprodi" @selected($role === 'kaprodi')>Kaprodi</option>
            </select>
            <select name="prodi" class="form-select" onchange="this.form.submit()" style="min-width:150px">
                <option value="">Semua prodi</option>
                @foreach ($prodis as $p)
                    <option value="{{ $p->id }}" @selected($prodiId === $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama / email / NIP…">
            </div>
        </form>
    </div>
    @if ($staff->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users" title="Belum ada akun" description="Tambahkan akun dosen atau kaprodi." /></div>
    @else
        @include('partials.bulk-select', ['deleteRoute' => route('admin.staff.bulkDestroy'), 'noun' => 'akun'])
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th class="w-1"><input type="checkbox" id="sel-all" class="form-check-input m-0"></th>
                    <th>Nama</th><th>Email</th><th>Peran</th><th>Prodi</th><th>NIP</th><th class="text-center">Kelas</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($staff as $s)
                        <tr>
                            <td><input type="checkbox" class="form-check-input m-0 row-select" value="{{ $s->id }}"></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <x-avatar :name="$s->name" :url="$s->avatarUrl()" class="me-2" />{{ $s->name }}
                                </div>
                            </td>
                            <td class="text-secondary">{{ $s->email }}</td>
                            <td>
                                <span class="badge bg-{{ $s->role === 'kaprodi' ? 'purple' : 'blue' }}-lt text-capitalize">{{ $s->role }}</span>
                                @if ($s->role !== 'kaprodi' && $s->headedProdi)
                                    <span class="badge bg-purple-lt" title="Merangkap Kaprodi"><i class="ti ti-user-star me-1"></i>Kaprodi {{ $s->headedProdi->code }}</span>
                                @endif
                            </td>
                            <td>{{ $s->prodi?->name ?? '—' }}</td>
                            <td>{{ $s->nim_nip ?? '—' }}</td>
                            <td class="text-center">{{ $s->teaching_courses_count }}</td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    <form method="POST" action="{{ route('admin.impersonate.start', $s) }}" data-confirm="Masuk sebagai {{ $s->name }}? Anda akan melihat aplikasi sebagai pengguna ini.">
                                        @csrf
                                        <button class="btn btn-sm" title="Masuk sebagai (spy)" data-bs-toggle="tooltip"><i class="ti ti-eye"></i></button>
                                    </form>
                                    <a href="{{ route('admin.staff.edit', $s) }}" class="btn btn-sm" title="Edit" data-bs-toggle="tooltip"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="{{ route('admin.staff.resetPassword', $s) }}" data-confirm="Reset kata sandi {{ $s->name }} menjadi NIP-nya (atau 'password')?">
                                        @csrf
                                        <button class="btn btn-sm" title="Reset kata sandi" data-bs-toggle="tooltip"><i class="ti ti-key"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.staff.destroy', $s) }}" data-confirm="Hapus akun {{ $s->name }}?@if ($s->teaching_courses_count > 0) (Akan ditolak — masih mengampu {{ $s->teaching_courses_count }} kelas.)@endif">
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
        <div class="card-footer d-flex">{{ $staff->links() }}</div>
    @endif
</div>

@include('partials.import-modal', [
    'importRoute' => route('admin.staff.import'),
    'title' => 'Import Dosen & Kaprodi (CSV)',
    'columns' => 'nama, email, peran, kode_prodi, nip, nidn',
    'note' => 'peran: dosen/kaprodi (default dosen). kode_prodi wajib & mengikuti Data Master. Sandi awal = NIP (atau "password"). Email duplikat dilewati.',
    'templateRoute' => route('admin.master.template', 'staff'),
])
@endsection
