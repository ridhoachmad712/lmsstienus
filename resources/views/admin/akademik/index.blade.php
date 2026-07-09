@extends('layouts.app')

@php($who = auth()->user()->isKaprodi() ? 'Kaprodi' : 'Admin')
@section('title', 'Rekap Akademik')
@section('page-pretitle', $who)
@section('page-title', 'Rekap Akademik')

@section('content')
{{-- Statistik ringkas --}}
<div class="row row-cards mb-3">
    <div class="col-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto"><span class="bg-blue text-white avatar"><i class="ti ti-users fs-2"></i></span></div>
                <div class="col"><div class="h1 m-0">{{ $stats['total'] }}</div><div class="text-secondary">Mahasiswa</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto"><span class="bg-green text-white avatar"><i class="ti ti-award fs-2"></i></span></div>
                <div class="col"><div class="h1 m-0">{{ is_null($stats['avg_ipk']) ? '—' : number_format($stats['avg_ipk'], 2) }}</div><div class="text-secondary">Rata-rata IPK</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <a href="{{ route('admin.academic.index', ['filter' => 'bermasalah', 'prodi' => $prodiId]) }}" class="card card-sm card-link">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-red text-white avatar"><i class="ti ti-alert-triangle fs-2"></i></span></div>
                    <div class="col"><div class="h1 m-0">{{ $stats['bermasalah'] }}</div><div class="text-secondary">Bermasalah (IPK &lt; {{ number_format($ipkMin, 1) }})</div></div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Mahasiswa</h3>
        <form method="GET" action="{{ route('admin.academic.index') }}" class="ms-auto d-flex gap-2 flex-wrap">
            @if (auth()->user()->isAdmin())
                <select name="prodi" class="form-select" onchange="this.form.submit()" style="min-width:150px">
                    <option value="">Semua prodi</option>
                    @foreach ($prodis as $p)
                        <option value="{{ $p->id }}" @selected($prodiId === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            @endif
            <label class="form-check form-switch mt-2 mb-0">
                <input class="form-check-input" type="checkbox" name="filter" value="bermasalah" onchange="this.form.submit()" @checked($onlyBermasalah)>
                <span class="form-check-label">Hanya bermasalah</span>
            </label>
        </form>
    </div>
    @if ($rows->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users" title="Tidak ada data" description="Belum ada mahasiswa pada lingkup ini." /></div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th>Nama</th><th>NIM</th><th>Prodi</th><th class="text-center">Smt</th>
                    <th class="text-center">IPK</th><th class="text-center">IPS</th><th class="text-center">SKS</th>
                    <th class="text-center">Status</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php($a = $r['a'])
                        <tr @class(['table-danger' => $r['bermasalah']])>
                            <td>{{ $r['student']->name }}</td>
                            <td class="text-secondary">{{ $r['student']->nim_nip ?? '—' }}</td>
                            <td class="text-secondary">{{ $r['student']->prodi?->code ?? '—' }}</td>
                            <td class="text-center">{{ $a['semester_ke'] ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $r['bermasalah'] ? 'red' : 'blue' }}-lt">{{ number_format($a['ipk'], 2) }}</span>
                            </td>
                            <td class="text-center text-secondary">{{ is_null($a['ips_terakhir']) ? '—' : number_format($a['ips_terakhir'], 2) }}</td>
                            <td class="text-center text-secondary">{{ $a['sks_kumulatif'] }}</td>
                            <td class="text-center"><span class="badge bg-{{ $a['status_color'] }}-lt text-capitalize">{{ $r['student']->student_status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.students.transkrip', $r['student']) }}" class="btn btn-sm"><i class="ti ti-certificate me-1"></i>Transkrip</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-center">
            <div class="text-secondary small me-auto">Baris merah = IPK di bawah {{ number_format($ipkMin, 1) }} (perlu pembinaan). IPK/IPS hanya menghitung kelas selesai & ber-SKS.</div>
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endsection
