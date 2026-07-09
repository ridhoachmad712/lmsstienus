@extends('layouts.app')

@section('title', 'Program Studi')
@section('page-pretitle', 'Data Master')
@section('page-title', 'Program Studi')

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $prodis->count() }} program studi</h3></div>
            @if ($prodis->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-building" title="Belum ada prodi" description="Tambahkan program studi di sebelah kanan." /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Kode</th><th>Nama</th><th class="text-center">Pengguna</th><th class="text-center">Kelas</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($prodis as $p)
                                <tr>
                                    <td class="fw-bold">{{ $p->code }}</td>
                                    <td>{{ $p->name }}</td>
                                    <td class="text-center text-secondary">{{ $p->users_count }}</td>
                                    <td class="text-center text-secondary">{{ $p->courses_count }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.prodi.destroy', $p) }}" onsubmit="return confirm('Hapus prodi {{ $p->name }}?');">
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
@endsection
