@extends('layouts.app')

@section('title', 'Pengawasan Kelas')
@php($who = auth()->user()->isKaprodi() ? 'Kaprodi' : 'Admin')
@section('page-pretitle', $who)
@section('page-title', 'Pengawasan Kelas')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Total: {{ $courses->total() }} kelas</h3>
        <form method="GET" action="{{ route('admin.courses.index') }}" class="ms-auto d-flex gap-2 flex-wrap">
            @if (auth()->user()->isAdmin())
                <select name="prodi" class="form-select" onchange="this.form.submit()" style="min-width:150px">
                    <option value="">Semua prodi</option>
                    @foreach ($prodis as $p)
                        <option value="{{ $p->id }}" @selected($prodiId === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            @endif
            <select name="status" class="form-select" onchange="this.form.submit()" style="min-width:140px">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="completed" @selected($status === 'completed')>Selesai</option>
            </select>
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari kelas / kode…">
            </div>
        </form>
    </div>
    @if ($courses->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-school-off" title="Belum ada kelas" description="Kelas yang dibuat dosen akan tampil di halaman pemantauan ini." /></div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th>Kelas</th><th>Dosen</th><th>Periode</th>
                    <th class="text-center">Mhs</th><th class="text-center">Pertemuan</th><th class="text-center">Tugas</th>
                    <th class="text-center">Bobot nilai</th><th class="text-center">Status</th>
                </tr></thead>
                <tbody>
                    @foreach ($courses as $c)
                        @php($weight = (int) ($c->weight_total ?? 0))
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $c->name }}</div>
                                @php($sub = $c->code.($c->class_name ? ' · '.$c->class_name : '').($c->mataKuliah ? ' · MK: '.$c->mataKuliah->code : ''))
                                <div class="small text-secondary">{{ $sub }}</div>
                            </td>
                            <td>{{ $c->lecturer?->name ?? '—' }}<div class="small text-secondary">{{ $c->prodi?->name ?? '—' }}</div></td>
                            <td class="text-secondary">{{ $c->semester }} {{ $c->year }}</td>
                            <td class="text-center">{{ $c->students_count }}</td>
                            <td class="text-center">{{ $c->meetings_count }}</td>
                            <td class="text-center">{{ $c->assignments_count }}</td>
                            <td class="text-center">
                                @if ($weight === 100)
                                    <span class="badge bg-green-lt">100%</span>
                                @elseif ($weight === 0)
                                    <span class="badge bg-secondary-lt">belum diatur</span>
                                @else
                                    <span class="badge bg-orange-lt">{{ $weight }}%</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $c->isCompleted() ? 'secondary' : 'green' }}-lt">{{ $c->isCompleted() ? 'Selesai' : 'Aktif' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-center">
            <div class="text-secondary small me-auto">Ringkasan pemantauan. Bobot nilai 100% = komponen penilaian sudah lengkap.</div>
            {{ $courses->links() }}
        </div>
    @endif
</div>
@endsection
