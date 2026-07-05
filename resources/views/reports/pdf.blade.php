<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 15px; margin: 0; }
        h2 { font-size: 11px; margin: 14px 0 4px; padding-bottom: 3px; border-bottom: 1px solid #206bc4; color: #206bc4; }
        .head { text-align: center; border-bottom: 2px solid #206bc4; padding-bottom: 8px; margin-bottom: 10px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #ccc; padding: 3px 6px; vertical-align: top; }
        th { background: #206bc4; color: #fff; }
        td.c, th.c { text-align: center; }
        .low { color: #c00; font-weight: bold; }
        .idn td { border: none; padding: 1px 4px; }
        .idn td.k { color: #666; width: 26%; }
        .nb { border: none; }
        .sign { margin-top: 30px; width: 100%; }
        .sign td { border: none; text-align: center; }
        .pill { display: inline-block; padding: 1px 6px; border: 1px solid #ccc; border-radius: 8px; margin: 0 4px 4px 0; }
    </style>
</head>
<body>
    <div class="head">
        @if (! empty($logoData))<img src="{{ $logoData }}" style="height:40px;margin-bottom:4px;">@endif
        <h1>LAPORAN PERKULIAHAN</h1>
        <div class="muted">{{ $course->name }} ({{ $course->code }}) · {{ $course->semester }} {{ $course->year }}</div>
        <div class="muted">{{ $footerText }}</div>
    </div>

    {{-- Identitas --}}
    <table class="idn">
        <tr><td class="k">Mata Kuliah</td><td>: {{ $course->name }}</td><td class="k">Semester</td><td>: {{ $course->semester }} {{ $course->year }}</td></tr>
        <tr><td class="k">Kode</td><td>: {{ $course->code }}</td><td class="k">Jumlah Mahasiswa</td><td>: {{ $totalStudents }}</td></tr>
        <tr><td class="k">Kelas</td><td>: {{ $course->class_name ?: '—' }}</td><td class="k">Jumlah Pertemuan</td><td>: {{ $completeness['meetings_total'] }}</td></tr>
        <tr><td class="k">Dosen Pengampu</td><td>: {{ $course->lecturer->name }}</td><td class="k">Sesi Absensi</td><td>: {{ $completeness['attendance_sessions'] }}</td></tr>
    </table>

    <h2>Ringkasan</h2>
    <span class="pill">Rata-rata hadir: {{ is_null($attAvg) ? '—' : $attAvg.'%' }}</span>
    <span class="pill">Hadir &lt;75%: {{ $attBelow75 }} mhs</span>
    <span class="pill">Lulus (≥60): {{ $lulus }}/{{ $totalStudents }}</span>
    <span class="pill">Rata-rata nilai: {{ $summary['avg'] }}</span>
    <span class="pill">Total bobot: {{ $completeness['weight_total'] }}%</span>
    <span class="pill">RPS: {{ $completeness['has_syllabus'] ? 'ada' : 'belum' }}</span>

    <h2>Realisasi Pertemuan (BAP)</h2>
    <table>
        <thead><tr><th class="c" style="width:5%">#</th><th>Topik</th><th style="width:16%">Tanggal</th><th style="width:14%">Model</th><th class="c" style="width:12%">Hadir</th></tr></thead>
        <tbody>
            @forelse ($meetings as $m)
                @php($a = $attByMeeting[$m->id] ?? null)
                <tr>
                    <td class="c">{{ $m->number }}</td>
                    <td>{{ $m->topic ?: '—' }}</td>
                    <td>{{ $m->date?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td>{{ $m->typeLabel() }}</td>
                    <td class="c">{{ $a ? $a['hadir'].'/'.$a['total'] : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="c muted">Belum ada pertemuan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Rekap Kehadiran per Mahasiswa</h2>
    <table>
        <thead><tr><th class="c" style="width:5%">No</th><th style="width:16%">NIM</th><th>Nama</th><th class="c" style="width:12%">Hadir</th><th class="c" style="width:10%">%</th></tr></thead>
        <tbody>
            @forelse ($grid['students'] as $i => $s)
                @php($sum = $grid['summary'][$s->id])
                @php($low = ! is_null($sum['percent']) && $sum['percent'] < 75)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $s->nim_nip }}</td>
                    <td>{{ $s->name }}</td>
                    <td class="c">{{ $sum['hadir'] }}/{{ $sum['sessions'] }}</td>
                    <td class="c {{ $low ? 'low' : '' }}">{{ is_null($sum['percent']) ? '-' : $sum['percent'].'%' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="c muted">Belum ada mahasiswa.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="muted" style="margin-top:4px">Rata-rata kehadiran {{ is_null($attAvg) ? '—' : $attAvg.'%' }} · {{ $attBelow75 }} mahasiswa &lt; 75%. Rincian H/I/S/A per pertemuan tersedia pada Rekap Kehadiran (PDF terpisah).</div>

    <h2>Distribusi Nilai</h2>
    <table>
        <thead><tr>@foreach ($dist as $letter => $count)<th class="c">{{ $letter }}</th>@endforeach</tr></thead>
        <tbody><tr>@foreach ($dist as $letter => $count)<td class="c">{{ $count }}</td>@endforeach</tr></tbody>
    </table>

    @if ($assignments->isNotEmpty())
        <h2>Rekap Tugas &amp; Kuis</h2>
        <table>
            <thead><tr><th>Judul</th><th style="width:12%">Jenis</th><th class="c" style="width:16%">Pengumpulan</th><th class="c" style="width:14%">Rata-rata</th></tr></thead>
            <tbody>
                @foreach ($assignments as $a)
                    <tr>
                        <td>{{ $a->title }}</td>
                        <td style="text-transform:capitalize">{{ $a->type }}</td>
                        <td class="c">{{ $a->submissions_count }}/{{ $totalStudents }}</td>
                        <td class="c">{{ is_null($a->avg_score) ? '—' : \App\Support\Grades::num($a->avg_score) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Daftar Nilai Akhir (DPNA)</h2>
    <table>
        <thead>
            <tr>
                <th class="c" style="width:5%">No</th>
                <th style="width:16%">NIM</th>
                <th>Nama</th>
                @foreach ($components as $comp)<th class="c">{{ $comp->name }}<br>({{ $comp->weight }}%)</th>@endforeach
                <th class="c">Akhir</th>
                <th class="c">Huruf</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $row)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $row['student']->nim_nip }}</td>
                    <td>{{ $row['student']->name }}</td>
                    @foreach ($components as $comp)<td class="c">{{ is_null($row['components'][$comp->id]) ? '-' : \App\Support\Grades::num($row['components'][$comp->id]) }}</td>@endforeach
                    <td class="c">{{ $row['final'] }}</td>
                    <td class="c"><strong>{{ $row['letter'] }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="{{ 5 + $components->count() }}" class="c muted">Belum ada mahasiswa.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="muted" style="margin-top:6px">Rata-rata {{ $summary['avg'] }} · Tertinggi {{ $summary['max'] }} · Terendah {{ $summary['min'] }} · Lulus {{ $lulus }}/{{ $totalStudents }}</div>

    <table class="sign">
        <tr>
            <td style="width:55%"></td>
            <td>
                Makassar, {{ $generatedAt->translatedFormat('d F Y') }}<br>Dosen Pengampu,<br><br><br><br>
                <strong>{{ $course->lecturer->name }}</strong><br>NIP. {{ $course->lecturer->nim_nip ?: '—' }}
            </td>
        </tr>
    </table>
</body>
</html>
