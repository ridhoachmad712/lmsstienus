@extends('layouts.app')

@php($who = auth()->user()->isKaprodi() ? 'Kaprodi' : 'Admin')
@section('title', 'Rekap EDOM')
@section('page-pretitle', $who)
@section('page-title', 'Rekap Evaluasi Dosen (EDOM)')

@section('content')
<div class="row row-cards mb-3">
    <div class="col-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto"><span class="bg-yellow text-white avatar"><i class="ti ti-star fs-2"></i></span></div>
                <div class="col"><div class="h1 m-0">{{ is_null($overallAvg) ? '—' : number_format($overallAvg, 2) }}</div><div class="text-secondary">Rata-rata skor (1–4)</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card card-sm"><div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto"><span class="bg-blue text-white avatar"><i class="ti ti-messages fs-2"></i></span></div>
                <div class="col"><div class="h1 m-0">{{ $totalResponden }}</div><div class="text-secondary">Total responden</div></div>
            </div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Hasil per Kelas</h3>
        @if (auth()->user()->isAdmin())
            <form method="GET" class="ms-auto">
                <select name="prodi" class="form-select" onchange="this.form.submit()" style="min-width:150px">
                    <option value="">Semua prodi</option>
                    @foreach ($prodis as $p)
                        <option value="{{ $p->id }}" @selected($prodiId === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if ($rows->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-star-off" title="Belum ada hasil EDOM" description="Hasil muncul setelah mahasiswa mengisi evaluasi." /></div>
    @else
        <div class="card-body border-bottom small text-secondary">
            @foreach ($questions as $i => $q)<span class="me-3"><strong>Q{{ $i + 1 }}</strong>: {{ $q }}</span>@endforeach
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th>Kelas / Dosen</th><th class="text-center">Responden</th>
                    @foreach ($questions as $i => $q)<th class="text-center" title="{{ $q }}">Q{{ $i + 1 }}</th>@endforeach
                    <th class="text-center">Rata²</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php($ov = $r['overall'])
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $r['course']->name }}</div>
                                <div class="text-secondary small">{{ $r['course']->lecturer?->name ?? '—' }} · {{ $r['course']->semester }} {{ $r['course']->year }}</div>
                            </td>
                            <td class="text-center text-secondary">{{ $r['n'] }}</td>
                            @foreach ($r['perQ'] as $avg)
                                <td class="text-center">{{ is_null($avg) ? '—' : number_format($avg, 2) }}</td>
                            @endforeach
                            <td class="text-center">
                                <span class="badge bg-{{ is_null($ov) ? 'secondary' : ($ov >= 3 ? 'green' : ($ov >= 2.5 ? 'yellow' : 'red')) }}-lt">{{ is_null($ov) ? '—' : number_format($ov, 2) }}</span>
                            </td>
                            <td class="text-end">
                                @if ($r['comments']->isNotEmpty())
                                    <details>
                                        <summary class="btn btn-sm">{{ $r['comments']->count() }} komentar</summary>
                                        <div class="text-start small mt-2">
                                            @foreach ($r['comments'] as $cmt)
                                                <div class="border rounded p-2 mb-1">{{ $cmt }}</div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-secondary small">Penilaian anonim. Skor 1–4 (makin tinggi makin baik).</div>
    @endif
</div>
@endsection
