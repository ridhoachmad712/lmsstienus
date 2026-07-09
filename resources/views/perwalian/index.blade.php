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
                <thead><tr><th>Nama</th><th>NIM</th><th class="text-center">Smt</th><th class="text-center">IPK</th><th class="text-center">IPS</th><th class="text-center">SKS</th><th class="text-center">Status</th><th class="text-center">KRS {{ $periodLabel }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($advisees as $m)
                        @php($a = $summaries[$m->id])
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <x-avatar :name="$m->name" :url="$m->avatarUrl()" class="me-2" />
                                    <div>{{ $m->name }}<div class="text-secondary small">{{ $m->prodi?->name ?? '—' }}</div></div>
                                </div>
                            </td>
                            <td>{{ $m->nim_nip ?? '—' }}</td>
                            <td class="text-center">{{ $a['semester_ke'] ?? '—' }}</td>
                            <td class="text-center"><span class="badge bg-{{ $a['sks_kumulatif'] > 0 && $a['ipk'] < 2 ? 'red' : 'blue' }}-lt">{{ number_format($a['ipk'], 2) }}</span></td>
                            <td class="text-center text-secondary">{{ is_null($a['ips_terakhir']) ? '—' : number_format($a['ips_terakhir'], 2) }}</td>
                            <td class="text-center text-secondary">{{ $a['sks_kumulatif'] }}</td>
                            <td class="text-center"><span class="badge bg-{{ $a['status_color'] }}-lt text-capitalize">{{ $m->student_status }}</span></td>
                            <td class="text-center">
                                @if ($m->krs_pending_count > 0)
                                    <span class="badge bg-yellow-lt">{{ $m->krs_pending_count }} perlu persetujuan</span>
                                @else
                                    <span class="text-secondary small">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('perwalian.krs', $m) }}" class="btn btn-sm @if ($m->krs_pending_count > 0) btn-primary @endif"><i class="ti ti-clipboard-check me-1"></i>KRS</a>
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
