<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        .newpage { page-break-before: always; }
        .matrix { font-size: 8px; }
        .matrix th, .matrix td { padding: 2px 2px; }
        .matrix .nm { text-align: left; }
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

    @if ($course->syllabus && ($course->syllabus->cpl || $course->syllabus->cpmk || $course->syllabus->sub_cpmk || $course->syllabus->description))
        <h2>Capaian Pembelajaran</h2>
        @if ($course->syllabus->description)<div style="margin-bottom:3px"><strong>Deskripsi MK:</strong> <span style="white-space:pre-line">{{ $course->syllabus->description }}</span></div>@endif
        @if ($course->syllabus->cpl)<div style="margin-bottom:3px"><strong>CPL:</strong> <span style="white-space:pre-line">{{ $course->syllabus->cpl }}</span></div>@endif
        @if ($course->syllabus->cpmk)<div style="margin-bottom:3px"><strong>CPMK:</strong> <span style="white-space:pre-line">{{ $course->syllabus->cpmk }}</span></div>@endif
        @if ($course->syllabus->sub_cpmk)<div style="margin-bottom:3px"><strong>Sub-CPMK:</strong> <span style="white-space:pre-line">{{ $course->syllabus->sub_cpmk }}</span></div>@endif
    @endif

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
                    <td>{{ $m->topic ?: '—' }}@if ($m->materials->isNotEmpty())<div class="muted" style="font-size:8px">Materi: {{ $m->materials->pluck('title')->implode(', ') }}</div>@endif</td>
                    <td>{{ $m->date?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td>{{ $m->typeLabel() }}</td>
                    <td class="c">{{ $a ? $a['hadir'].'/'.$a['total'] : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="c muted">Belum ada pertemuan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="muted" style="margin-top:2px">Terlaksana {{ $completeness['meetings_held'] }} dari {{ $completeness['meetings_total'] }} pertemuan terjadwal.</div>

    <h2>Komponen Penilaian &amp; Bobot</h2>
    <table>
        <thead><tr><th>Komponen</th><th style="width:18%">Jenis</th><th class="c" style="width:12%">Bobot</th><th style="width:16%">Sumber Nilai</th></tr></thead>
        <tbody>
            @forelse ($components as $comp)
                <tr>
                    <td>{{ $comp->name }}</td>
                    <td>{{ \App\Models\GradeComponent::TYPES[$comp->type] ?? ucfirst($comp->type) }}</td>
                    <td class="c">{{ $comp->weight }}%</td>
                    <td>{{ in_array($comp->id, $autoComponentIds) ? 'Otomatis' : 'Manual' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="c muted">Belum ada komponen.</td></tr>
            @endforelse
            @if ($components->isNotEmpty())
                <tr><td colspan="2" style="text-align:right"><strong>Total</strong></td><td class="c"><strong>{{ $completeness['weight_total'] }}%</strong></td><td></td></tr>
            @endif
        </tbody>
    </table>

    <h2>Skala Nilai</h2>
    <table>
        <thead><tr>@foreach (\App\Support\Grades::scale() as $s)<th class="c">{{ $s['letter'] }}</th>@endforeach</tr></thead>
        <tbody><tr>@foreach (\App\Support\Grades::scale() as $s)<td class="c">&ge; {{ \App\Support\Grades::num($s['min']) }}</td>@endforeach</tr></tbody>
    </table>

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

    <h2>Mahasiswa Berisiko</h2>
    @if ($risk->isEmpty())
        <div class="muted">Tidak ada — semua mahasiswa memenuhi nilai akhir &ge; 60 dan kehadiran &ge; 75%.</div>
    @else
        <table>
            <thead><tr><th class="c" style="width:5%">No</th><th style="width:16%">NIM</th><th>Nama</th><th class="c" style="width:10%">Akhir</th><th class="c" style="width:10%">Hadir</th><th style="width:26%">Catatan</th></tr></thead>
            <tbody>
                @foreach ($risk as $i => $x)
                    <tr>
                        <td class="c">{{ $i + 1 }}</td>
                        <td>{{ $x['student']->nim_nip }}</td>
                        <td>{{ $x['student']->name }}</td>
                        <td class="c {{ $x['low_grade'] ? 'low' : '' }}">{{ $x['final'] }}</td>
                        <td class="c {{ $x['low_att'] ? 'low' : '' }}">{{ is_null($x['percent']) ? '-' : $x['percent'].'%' }}</td>
                        <td>{{ collect([$x['low_grade'] ? 'Nilai < 60' : null, $x['low_att'] ? 'Kehadiran < 75%' : null])->filter()->implode(' · ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="sign">
        <tr>
            <td style="width:50%">
                Mengetahui,<br>Ketua Program Studi,<br><br><br><br>
                <strong>_____________________</strong><br>NIP.
            </td>
            <td style="width:50%">
                Makassar, {{ $generatedAt->translatedFormat('d F Y') }}<br>Dosen Pengampu,<br><br><br><br>
                <strong>{{ $course->lecturer->name }}</strong><br>NIP. {{ $course->lecturer->nim_nip ?: '—' }}
            </td>
        </tr>
    </table>

    {{-- Halaman terpisah (landscape): matriks kehadiran per pertemuan --}}
    @php($L = ['hadir' => 'H', 'izin' => 'I', 'sakit' => 'S', 'alpa' => 'A'])
    <div class="newpage">
        <h1 style="font-size:13px;margin-bottom:2px">REKAP KEHADIRAN — Mahasiswa × Pertemuan</h1>
        <div class="muted" style="margin-bottom:4px">{{ $course->name }} ({{ $course->code }}) · {{ $course->semester }} {{ $course->year }} ·
            H=Hadir · I=Izin · S=Sakit · A=Alpa · <strong>{{ $grid['sessions'] }}</strong> sesi</div>
        <table class="matrix">
            <thead>
                <tr>
                    <th class="c" style="width:3%">No</th>
                    <th class="nm">Mahasiswa</th>
                    @foreach ($grid['meetings'] as $m)<th class="c">P{{ $m->number }}</th>@endforeach
                    <th class="c" style="width:6%">%</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grid['students'] as $i => $s)
                    @php($sum = $grid['summary'][$s->id])
                    @php($low = ! is_null($sum['percent']) && $sum['percent'] < 75)
                    <tr>
                        <td class="c">{{ $i + 1 }}</td>
                        <td class="nm">{{ $s->name }} <span class="muted">({{ $s->nim_nip }})</span></td>
                        @foreach ($grid['meetings'] as $m)
                            @php($status = $grid['matrix'][$s->id][$m->id] ?? null)
                            <td class="c">{{ $status ? ($L[$status] ?? '-') : '·' }}</td>
                        @endforeach
                        <td class="c {{ $low ? 'low' : '' }}">{{ is_null($sum['percent']) ? '-' : $sum['percent'].'%' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 3 + $grid['meetings']->count() }}" class="c muted">Belum ada mahasiswa.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="muted" style="margin-top:4px">Persentase = jumlah Hadir ÷ sesi absensi. <span class="low">Merah</span> = kehadiran &lt; 75%.</div>
    </div>
</body>
</html>
