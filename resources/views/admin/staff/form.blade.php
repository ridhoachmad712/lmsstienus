@extends('layouts.app')

@php($isEdit = $user->exists)
@section('title', $isEdit ? 'Edit Akun' : 'Tambah Akun')
@section('page-pretitle', 'Admin')
@section('page-title', $isEdit ? 'Edit Akun — '.$user->name : 'Tambah Dosen / Kaprodi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Dosen & Kaprodi</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <form class="card" method="POST" data-warn-unsaved
              action="{{ $isEdit ? route('admin.staff.update', $user) : route('admin.staff.store') }}">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <div class="card-body">
                <div class="mb-3"><label class="form-label required">Nama</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3"><label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label required">Peran</label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror">
                            @foreach (['dosen' => 'Dosen', 'kaprodi' => 'Kaprodi (Ketua Prodi)'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label required">Program Studi</label>
                        <select name="prodi_id" class="form-select @error('prodi_id') is-invalid @enderror">
                            <option value="">— Pilih prodi —</option>
                            @foreach ($prodis as $p)
                                <option value="{{ $p->id }}" @selected(old('prodi_id', $user->prodi_id) == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('prodi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-hint">Kaprodi hanya mengelola prodi ini; kelas dosen mengikuti prodi ini.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">NIP / NIDN</label>
                        <input type="text" name="nim_nip" class="form-control @error('nim_nip') is-invalid @enderror" value="{{ old('nim_nip', $user->nim_nip) }}">
                        @error('nim_nip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @unless ($isEdit)
                        <div class="col-md-6 mb-3"><label class="form-label required">Kata Sandi</label>
                            <input type="text" name="password" class="form-control @error('password') is-invalid @enderror" value="{{ old('password') }}" placeholder="Minimal 6 karakter">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-hint">Bisa diubah pengguna nanti di profil.</small>
                        </div>
                    @endunless
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">NIDN</label>
                        <input type="text" name="nidn" class="form-control" value="{{ old('nidn', $user->nidn) }}">
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Jabatan Akademik</label>
                        <input type="text" name="jabatan" class="form-control" value="{{ old('jabatan', $user->jabatan) }}" placeholder="mis. Lektor / Asisten Ahli">
                    </div>
                </div>

                <hr class="my-3"><div class="text-secondary small mb-2">Biodata</div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-select">
                            <option value="">—</option>
                            <option value="L" @selected(old('gender', $user->gender) === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('gender', $user->gender) === 'P')>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3"><label class="form-label">Tempat Lahir</label>
                        <input type="text" name="birth_place" class="form-control" value="{{ old('birth_place', $user->birth_place) }}">
                    </div>
                    <div class="col-md-4 mb-3"><label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-12 mb-3"><label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $user->address) }}</textarea>
                    </div>
                </div>

                @if ($isEdit)
                    <div class="text-secondary small">Untuk mengubah kata sandi, gunakan tombol <i class="ti ti-key"></i> Reset di daftar.</div>
                @endif
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('admin.staff.index') }}" class="btn btn-link">Batal</a>
                <button class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
