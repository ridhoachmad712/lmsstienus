@extends('layouts.app')

@section('title', $self ? 'Transkrip Saya' : 'Transkrip — '.$student->name)
@section('page-pretitle', 'Akademik')
@section('page-title', $self ? 'Transkrip Nilai' : 'Transkrip — '.$student->name)

@section('page-actions')
    <a href="{{ $self ? route('transkrip.mine.pdf') : route('admin.students.transkrip.pdf', $student) }}" class="btn btn-outline-red"><i class="ti ti-file-type-pdf me-1"></i>Unduh PDF</a>
@endsection

@section('content')
<div class="row row-cards mb-1">
    <div class="col-md-8">
        <div class="card"><div class="card-body">
            <div class="row">
                <div class="col-6 col-md-3"><div class="text-secondary small">Nama</div><div class="fw-bold">{{ $student->name }}</div></div>
                <div class="col-6 col-md-3"><div class="text-secondary small">NIM</div><div class="fw-bold">{{ $student->nim_nip ?? '—' }}</div></div>
                <div class="col-6 col-md-3"><div class="text-secondary small">Prodi</div><div class="fw-bold">{{ $student->prodi?->name ?? '—' }}</div></div>
                <div class="col-6 col-md-3"><div class="text-secondary small">Angkatan</div><div class="fw-bold">{{ $student->entry_year ?? '—' }}</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body text-center">
            <div class="text-secondary">IPK</div>
            <div class="h1 display-6 mb-0">{{ number_format($ipk, 2) }}</div>
            <div class="text-secondary small">{{ $total_sks }} SKS lulus</div>
        </div></div>
    </div>
</div>

@forelse ($periods as $p)
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">{{ $p['label'] }}</h3>
            <div class="ms-auto text-secondary small">IPS: <strong>{{ is_null($p['ips']) ? '—' : number_format($p['ips'], 2) }}</strong> · {{ $p['sks'] }} SKS</div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Kode</th><th>Mata Kuliah</th><th class="text-center">SKS</th><th class="text-center">Nilai</th><th class="text-center">Huruf</th><th class="text-center">Mutu</th></tr></thead>
                <tbody>
                    @foreach ($p['items'] as $it)
                        <tr @class(['text-secondary' => $it['running']])>
                            <td>{{ $it['code'] }}</td>
                            <td>{{ $it['name'] }}</td>
                            <td class="text-center">{{ $it['sks'] ?: '—' }}</td>
                            <td class="text-center">{{ $it['running'] ? '—' : \App\Support\Grades::num($it['final']) }}</td>
                            <td class="text-center">
                                @if ($it['running'])<span class="badge bg-azure-lt">berjalan</span>
                                @else<span class="badge bg-{{ \App\Support\Grades::color($it['letter']) }}-lt">{{ $it['letter'] }}</span>@endif
                            </td>
                            <td class="text-center">{{ $it['counted'] ? number_format($it['sks'] * $it['point'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card mt-3"><div class="card-body"><x-empty-state icon="ti-file-off" title="Belum ada riwayat kelas" description="Transkrip akan terisi setelah mahasiswa mengikuti kelas & nilainya difinalkan." /></div></div>
@endforelse

<div class="text-secondary small mt-2">Catatan: IPS/IPK hanya menghitung kelas yang sudah <strong>selesai</strong> (nilai final) dan ber-SKS. Kelas berjalan ditampilkan tanpa dihitung.</div>
@endsection
