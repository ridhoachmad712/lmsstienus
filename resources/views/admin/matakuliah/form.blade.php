@extends('layouts.app')

@php($isEdit = $mk->exists)
@section('title', $isEdit ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah')
@section('page-pretitle', 'Admin')
@section('page-title', $isEdit ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.matakuliah.index') }}">Mata Kuliah</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? $mk->code : 'Tambah' }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <form class="card" method="POST" data-warn-unsaved
              action="{{ $isEdit ? route('admin.matakuliah.update', $mk) : route('admin.matakuliah.store') }}">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label required">Kode</label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $mk->code) }}" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8 mb-3"><label class="form-label required">Nama Mata Kuliah</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $mk->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label required">SKS</label>
                        <input type="number" name="sks" class="form-control @error('sks') is-invalid @enderror" value="{{ old('sks', $mk->sks ?: 3) }}" min="1" max="10" required>
                        @error('sks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if (auth()->user()->isAdmin())
                        <div class="col-md-8 mb-3"><label class="form-label">Program Studi</label>
                            <select name="prodi_id" class="form-select">
                                <option value="">— Lintas prodi / tidak ditentukan —</option>
                                @foreach ($prodis as $p)
                                    <option value="{{ $p->id }}" @selected(old('prodi_id', $mk->prodi_id) == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.matakuliah.index') }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
