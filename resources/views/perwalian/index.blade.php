@extends('layouts.app')

@section('title', 'Perwalian')
@section('page-pretitle', 'Dosen Wali')
@section('page-title', 'Mahasiswa Bimbingan')

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">{{ $advisees->count() }} mahasiswa bimbingan</h3></div>
    @if ($advisees->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users-group" title="Belum ada mahasiswa bimbingan" description="Admin/kaprodi menetapkan mahasiswa bimbingan Anda lewat data mahasiswa." /></div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Nama</th><th>NIM</th><th>Prodi</th><th class="text-center">Angkatan</th><th class="text-center">Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($advisees as $m)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <x-avatar :name="$m->name" :url="$m->avatarUrl()" class="me-2" />{{ $m->name }}
                                </div>
                            </td>
                            <td>{{ $m->nim_nip ?? '—' }}</td>
                            <td>{{ $m->prodi?->name ?? '—' }}</td>
                            <td class="text-center">{{ $m->entry_year ?? '—' }}</td>
                            <td class="text-center"><span class="badge bg-{{ $m->student_status === 'aktif' ? 'green' : 'secondary' }}-lt text-capitalize">{{ $m->student_status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('perwalian.transkrip', $m) }}" class="btn btn-sm"><i class="ti ti-certificate me-1"></i>Transkrip</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
