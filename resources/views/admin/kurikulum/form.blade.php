@extends('layouts.app')

@php($isEdit = $k->exists)
@section('title', $isEdit ? 'Edit Kurikulum' : 'Tambah Kurikulum')
@section('page-pretitle', 'Admin')
@section('page-title', $isEdit ? 'Edit Kurikulum' : 'Tambah Kurikulum')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.kurikulum.index') }}">Kurikulum</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? $k->name : 'Tambah' }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <form class="card" method="POST" data-warn-unsaved
              action="{{ $isEdit ? route('admin.kurikulum.update', $k) : route('admin.kurikulum.store') }}">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <div class="card-body">
                <div class="mb-3"><label class="form-label required">Nama Kurikulum</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $k->name) }}" placeholder="mis. Kurikulum 2021" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-md-5 mb-3"><label class="form-label required">Tahun</label>
                        <input type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', $k->year ?: date('Y')) }}" min="2000" max="2100" required>
                        @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if (auth()->user()->isAdmin())
                        <div class="col-md-7 mb-3"><label class="form-label">Program Studi</label>
                            <select name="prodi_id" class="form-select">
                                <option value="">— Tidak ditentukan —</option>
                                @foreach ($prodis as $p)
                                    <option value="{{ $p->id }}" @selected(old('prodi_id', $k->prodi_id) == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <label class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $k->is_active))>
                    <span class="form-check-label">Jadikan kurikulum aktif untuk prodi ini <span class="text-secondary">(kurikulum aktif lain di prodi yang sama akan dinonaktifkan)</span></span>
                </label>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.kurikulum.index') }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
