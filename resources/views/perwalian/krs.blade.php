@extends('layouts.app')

@section('title', 'KRS ' . $student->name)
@section('page-pretitle', 'Perwalian')
@section('page-title', 'KRS — ' . $student->name)

@section('page-actions')
    <a href="{{ route('perwalian.index') }}" class="btn"><i class="ti ti-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-secondary small">Mahasiswa</div>
                        <div class="fw-bold">{{ $student->name }} · {{ $student->nim_nip ?? '—' }} · {{ $student->prodi?->name ?? '—' }}</div>
                        <div class="text-secondary small mt-1">Periode {{ $periodLabel }} · Total {{ $totalSks }} SKS</div>
                    </div>
                    @if ($pendingCount > 0)
                        <div class="col-auto d-flex gap-2">
                            <form method="POST" action="{{ route('perwalian.krs.reject', $student) }}"
                                  onsubmit="return confirm('Kembalikan pengajuan KRS ke mahasiswa untuk direvisi?');">
                                @csrf
                                <button class="btn btn-outline-danger"><i class="ti ti-x me-1"></i>Tolak</button>
                            </form>
                            <form method="POST" action="{{ route('perwalian.krs.approve', $student) }}"
                                  onsubmit="return confirm('Setujui {{ $pendingCount }} kelas yang diajukan?');">
                                @csrf
                                <button class="btn btn-primary"><i class="ti ti-check me-1"></i>Setujui KRS ({{ $pendingCount }})</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $items->count() }} kelas</h3></div>
            @if ($items->isEmpty())
                <div class="card-body">
                    <x-empty-state icon="ti-clipboard-off" title="Belum ada KRS"
                        description="Mahasiswa belum menyusun rencana studi untuk periode ini." />
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Kode</th><th>Mata Kuliah / Kelas</th><th>Dosen</th><th class="text-center">SKS</th><th class="text-center">Status</th></tr></thead>
                        <tbody>
                            @foreach ($items as $e)
                                <tr>
                                    <td class="text-secondary">{{ $e->course->mataKuliah->code ?? $e->course->code }}</td>
                                    <td>
                                        <div>{{ $e->course->mataKuliah->name ?? $e->course->name }}</div>
                                        <div class="text-secondary small">{{ $e->course->name }}@if ($e->course->class_name) · {{ $e->course->class_name }}@endif</div>
                                    </td>
                                    <td>{{ $e->course->lecturer->name }}</td>
                                    <td class="text-center">{{ $e->course->mataKuliah->sks ?? 0 }}</td>
                                    <td class="text-center"><span class="badge bg-{{ $e->statusColor() }}-lt">{{ $e->statusLabel() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
