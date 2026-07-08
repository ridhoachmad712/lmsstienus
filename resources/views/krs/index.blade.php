@extends('layouts.app')

@section('title', 'KRS')
@section('page-pretitle', 'Kartu Rencana Studi')
@section('page-title', 'KRS — ' . $periodLabel)

@section('content')
<div class="row row-cards">
    {{-- Ringkasan & status --}}
    <div class="col-12">
        @if (! $krsOpen)
            <div class="alert alert-warning" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-lock me-2"></i></div>
                    <div>Periode pengisian KRS sedang <strong>tutup</strong>. Anda hanya dapat melihat rencana yang sudah ada.</div>
                </div>
            </div>
        @elseif (! $advisor)
            <div class="alert alert-danger" role="alert">
                <i class="ti ti-alert-triangle me-2"></i>Anda belum memiliki dosen wali. KRS tidak dapat diajukan sampai admin/kaprodi menetapkannya.
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="badge bg-{{ $totalSks > $maxSks ? 'red' : 'blue' }}-lt fs-3 px-3 py-2">{{ $totalSks }} / {{ $maxSks }} SKS</span>
                    </div>
                    <div class="col">
                        <div class="text-secondary small">Dosen wali</div>
                        <div class="fw-bold">{{ $advisor?->name ?? '— belum ada —' }}</div>
                    </div>
                    @if ($krsOpen && $advisor)
                        <div class="col-auto">
                            <form method="POST" action="{{ route('krs.submit') }}"
                                  onsubmit="return confirm('Ajukan seluruh rencana (Rencana) ke dosen wali untuk disetujui?');">
                                @csrf
                                <button class="btn btn-primary"><i class="ti ti-send me-1"></i>Ajukan KRS ke Wali</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- KRS saat ini --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Rencana Studi Saya ({{ $myKrs->count() }} kelas)</h3></div>
            @if ($myKrs->isEmpty())
                <div class="card-body">
                    <x-empty-state icon="ti-clipboard-list" title="KRS masih kosong"
                        description="Pilih kelas dari daftar di bawah, lalu ajukan ke dosen wali." />
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Kode</th><th>Mata Kuliah / Kelas</th><th>Dosen</th><th>Jadwal</th><th class="text-center">SKS</th><th class="text-center">Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($myKrs as $e)
                                <tr @class(['table-warning' => in_array($e->course_id, $clashCourseIds)])>
                                    <td class="text-secondary">{{ $e->course->mataKuliah->code ?? $e->course->code }}</td>
                                    <td>
                                        <div>{{ $e->course->mataKuliah->name ?? $e->course->name }}</div>
                                        <div class="text-secondary small">{{ $e->course->name }}@if ($e->course->class_name) · {{ $e->course->class_name }}@endif</div>
                                    </td>
                                    <td>{{ $e->course->lecturer->name }}</td>
                                    <td>
                                        @forelse ($e->course->schedules as $s)
                                            <div class="small">{{ $s->dayLabel() }} {{ $s->start_time }}–{{ $s->end_time }}@if ($s->room) · {{ $s->room }}@endif</div>
                                        @empty
                                            <span class="text-secondary small">—</span>
                                        @endforelse
                                        @if (in_array($e->course_id, $clashCourseIds))
                                            <span class="badge bg-orange-lt mt-1"><i class="ti ti-alert-triangle me-1"></i>Jadwal bentrok</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $e->course->mataKuliah->sks ?? 0 }}</td>
                                    <td class="text-center"><span class="badge bg-{{ $e->statusColor() }}-lt">{{ $e->statusLabel() }}</span></td>
                                    <td class="text-end">
                                        @if ($krsOpen && $e->status !== \App\Models\Enrollment::STATUS_APPROVED)
                                            <form method="POST" action="{{ route('krs.remove', $e) }}" onsubmit="return confirm('Hapus kelas ini dari KRS?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Kelas tersedia --}}
    @if ($krsOpen)
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Kelas Tersedia ({{ $available->count() }})</h3></div>
                @if ($available->isEmpty())
                    <div class="card-body text-secondary">Tidak ada kelas lain yang tersedia untuk periode ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead><tr><th>Kode</th><th>Mata Kuliah / Kelas</th><th>Prodi</th><th>Dosen</th><th>Jadwal</th><th class="text-center">SKS</th><th class="text-center">Kuota</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($available as $c)
                                    @php $full = $c->quota !== null && $c->seats_taken_count >= $c->quota; @endphp
                                    <tr>
                                        <td class="text-secondary">{{ $c->mataKuliah->code ?? $c->code }}</td>
                                        <td>
                                            <div>{{ $c->mataKuliah->name ?? $c->name }}</div>
                                            <div class="text-secondary small">{{ $c->name }}@if ($c->class_name) · {{ $c->class_name }}@endif</div>
                                        </td>
                                        <td>{{ $c->prodi->code ?? '—' }}</td>
                                        <td>{{ $c->lecturer->name }}</td>
                                        <td>
                                            @forelse ($c->schedules as $s)
                                                <div class="small">{{ $s->dayLabel() }} {{ $s->start_time }}–{{ $s->end_time }}@if ($s->room) · {{ $s->room }}@endif</div>
                                            @empty
                                                <span class="text-secondary small">—</span>
                                            @endforelse
                                            @if (isset($availableWarn[$c->id]))
                                                <span class="badge bg-orange-lt mt-1"><i class="ti ti-alert-triangle me-1"></i>Bentrok dgn KRS</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $c->mataKuliah->sks ?? 0 }}</td>
                                        <td class="text-center">
                                            @if ($c->quota === null)
                                                <span class="text-secondary">∞</span>
                                            @else
                                                {{ $c->seats_taken_count }}/{{ $c->quota }}
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($full)
                                                <span class="badge bg-red-lt">Penuh</span>
                                            @else
                                                <form method="POST" action="{{ route('krs.add', $c) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
