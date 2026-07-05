@extends('layouts.app')

@section('title', 'Laporan Perkuliahan')

@section('hero-actions')
    <a href="{{ route('reports.pdf', $course) }}" class="btn btn-outline-red"><i class="ti ti-file-type-pdf me-1"></i>Unduh PDF</a>
@endsection

@section('content')
@include('courses._hero')

@php($c = $completeness)

{{-- ===== Ringkasan status (pemantauan) ===== --}}
<div class="row row-cards mb-1">
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0">{{ $totalStudents }}</div>
            <div class="text-secondary">Mahasiswa</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0">{{ $c['attendance_sessions'] }}<small class="text-secondary fs-4">/{{ $c['meetings_total'] }}</small></div>
            <div class="text-secondary">Sesi absensi</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0">{{ is_null($attAvg) ? '—' : $attAvg.'%' }}</div>
            <div class="text-secondary">Rata-rata hadir</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body text-center">
            <div class="h1 m-0 text-{{ $totalStudents > 0 && $lulus === $totalStudents ? 'green' : 'orange' }}">{{ $lulus }}<small class="text-secondary fs-4">/{{ $totalStudents }}</small></div>
            <div class="text-secondary">Lulus (≥60)</div>
        </div></div>
    </div>
</div>

{{-- Kelengkapan --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            @php($items = [
                ['RPS terisi', $c['has_syllabus']],
                ['Komponen nilai diatur', $c['has_components']],
                ['Total bobot 100%', $c['weight_ok'], $c['weight_total'].'%'],
                ['Penilaian lengkap', $c['grades_complete_all'], $c['grades_complete'].'/'.$totalStudents],
            ])
            @foreach ($items as $it)
                <div class="col-6 col-md-3 d-flex align-items-center">
                    <span class="avatar avatar-sm bg-{{ $it[1] ? 'green' : 'orange' }}-lt me-2"><i class="ti {{ $it[1] ? 'ti-check' : 'ti-alert-triangle' }}"></i></span>
                    <div>
                        <div class="fw-bold small">{{ $it[0] }}</div>
                        <div class="text-secondary small">{{ $it[1] ? 'OK' : 'Perlu dilengkapi' }}@isset($it[2]) · {{ $it[2] }}@endisset</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row row-cards">
    {{-- ===== Realisasi Pertemuan (BAP) ===== --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-calendar-event me-1"></i>Realisasi Pertemuan</h3></div>
            @if ($meetings->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-calendar-off" title="Belum ada pertemuan" /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th class="w-1">#</th><th>Topik</th><th>Tanggal</th><th>Model</th><th class="text-center">Hadir</th></tr></thead>
                        <tbody>
                            @foreach ($meetings as $m)
                                @php($a = $attByMeeting[$m->id] ?? null)
                                <tr>
                                    <td>{{ $m->number }}</td>
                                    <td>{{ $m->topic ?: '—' }}
                                        @if ($m->materials->isNotEmpty())<div class="small text-secondary">{{ $m->materials->count() }} materi</div>@endif
                                    </td>
                                    <td class="text-secondary">{{ $m->date?->translatedFormat('d M Y') ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $m->isMandiri() ? 'purple' : 'blue' }}-lt">{{ $m->typeLabel() }}</span></td>
                                    <td class="text-center">{{ $a ? $a['hadir'].'/'.$a['total'] : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== Distribusi Nilai + Tugas ===== --}}
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-chart-bar me-1"></i>Distribusi Nilai</h3></div>
            <div class="card-body">
                @if ($totalStudents === 0)
                    <div class="text-secondary small">Belum ada mahasiswa.</div>
                @else
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @foreach ($dist as $letter => $count)
                            <span class="badge bg-{{ \App\Support\Grades::color($letter) }}-lt">{{ $letter }}: {{ $count }}</span>
                        @endforeach
                    </div>
                    <div class="text-secondary small">Rata-rata <strong>{{ $summary['avg'] }}</strong> · Tertinggi <strong>{{ $summary['max'] }}</strong> · Terendah <strong>{{ $summary['min'] }}</strong></div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-clipboard-list me-1"></i>Rekap Tugas &amp; Kuis</h3></div>
            @if ($assignments->isEmpty())
                <div class="card-body"><div class="text-secondary small">Belum ada tugas/kuis.</div></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Judul</th><th class="text-center">Kumpul</th><th class="text-center">Rata²</th></tr></thead>
                        <tbody>
                            @foreach ($assignments as $a)
                                <tr>
                                    <td>{{ $a->title }}<div class="small text-secondary text-capitalize">{{ $a->type }}</div></td>
                                    <td class="text-center">{{ $a->submissions_count }}/{{ $totalStudents }}</td>
                                    <td class="text-center">{{ is_null($a->avg_score) ? '—' : \App\Support\Grades::num($a->avg_score) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ===== Rekap Kehadiran per Mahasiswa ===== --}}
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-user-check me-1"></i>Rekap Kehadiran per Mahasiswa</h3>
        <div class="ms-auto text-secondary small">Rata-rata {{ is_null($attAvg) ? '—' : $attAvg.'%' }} · {{ $attBelow75 }} mhs &lt;75%</div>
    </div>
    @if ($grid['students']->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users" title="Belum ada mahasiswa" /></div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-sortable">
                <thead><tr><th class="w-1">#</th><th>Mahasiswa</th><th class="text-center">Hadir</th><th class="text-center">%</th></tr></thead>
                <tbody>
                    @foreach ($grid['students'] as $i => $s)
                        @php($sum = $grid['summary'][$s->id])
                        @php($low = ! is_null($sum['percent']) && $sum['percent'] < 75)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $s->name }}<div class="small text-secondary">{{ $s->nim_nip }}</div></td>
                            <td class="text-center">{{ $sum['hadir'] }}/{{ $sum['sessions'] }}</td>
                            <td class="text-center">
                                @if ($low)<span class="badge bg-red-lt">{{ is_null($sum['percent']) ? '—' : $sum['percent'].'%' }}</span>
                                @else {{ is_null($sum['percent']) ? '—' : $sum['percent'].'%' }}@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ===== Daftar Nilai Akhir (DPNA) ===== --}}
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-list-numbers me-1"></i>Daftar Nilai Akhir</h3>
        <input type="text" class="form-control form-control-sm ms-auto" style="max-width:220px" placeholder="Cari mahasiswa…" data-table-search="#tbl-dpna">
    </div>
    @if ($rows->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users" title="Belum ada mahasiswa" /></div>
    @else
        <div class="table-responsive">
            <table id="tbl-dpna" class="table table-vcenter card-table table-sortable">
                <thead>
                    <tr>
                        <th class="w-1">#</th><th>Mahasiswa</th>
                        @foreach ($components as $comp)<th class="text-center">{{ $comp->name }}<div class="small fw-normal">{{ $comp->weight }}%</div></th>@endforeach
                        <th class="text-center">Akhir</th><th class="text-center">Huruf</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['student']->name }}<div class="small text-secondary">{{ $row['student']->nim_nip }}</div></td>
                            @foreach ($components as $comp)
                                <td class="text-center">{{ is_null($row['components'][$comp->id]) ? '—' : \App\Support\Grades::num($row['components'][$comp->id]) }}</td>
                            @endforeach
                            <td class="text-center fw-bold">{{ $row['final'] }}</td>
                            <td class="text-center"><span class="badge bg-{{ \App\Support\Grades::color($row['letter']) }}-lt">{{ $row['letter'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
