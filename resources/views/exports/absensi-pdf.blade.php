<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        h1 { font-size: 15px; margin: 0; }
        .head { text-align: center; border-bottom: 2px solid #206bc4; padding-bottom: 8px; margin-bottom: 10px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ccc; padding: 3px 4px; }
        th { background: #206bc4; color: #fff; }
        td.c, th.c { text-align: center; }
        .low { color: #c00; font-weight: bold; }
        .legend { margin-top: 6px; }
        .sign { margin-top: 26px; width: 100%; }
        .sign td { border: none; text-align: center; }
    </style>
</head>
<body>
    @php($L = ['hadir' => 'H', 'izin' => 'I', 'sakit' => 'S', 'alpa' => 'A'])
    <div class="head">
        @if (! empty($logoData))<img src="{{ $logoData }}" style="height:40px;margin-bottom:4px;">@endif
        <h1>REKAP KEHADIRAN</h1>
        <div class="muted">{{ $course->name }} ({{ $course->code }}) · {{ $course->semester }} {{ $course->year }}</div>
        <div class="muted">{{ $footerText }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="c" style="width:4%">No</th>
                <th style="width:12%">NIM</th>
                <th>Nama</th>
                @foreach ($grid['meetings'] as $m)
                    <th class="c" style="width:3%" title="Pertemuan {{ $m->number }}">P{{ $m->number }}</th>
                @endforeach
                <th class="c" style="width:7%">Hadir</th>
                <th class="c" style="width:6%">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($grid['students'] as $i => $s)
                @php($sum = $grid['summary'][$s->id])
                @php($low = ! is_null($sum['percent']) && $sum['percent'] < 75)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $s->nim_nip }}</td>
                    <td>{{ $s->name }}</td>
                    @foreach ($grid['meetings'] as $m)
                        @php($status = $grid['matrix'][$s->id][$m->id] ?? null)
                        <td class="c">{{ $status ? ($L[$status] ?? '-') : '-' }}</td>
                    @endforeach
                    <td class="c">{{ $sum['hadir'] }}/{{ $sum['sessions'] }}</td>
                    <td class="c {{ $low ? 'low' : '' }}">{{ is_null($sum['percent']) ? '-' : $sum['percent'].'%' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 5 + $grid['meetings']->count() }}" class="c muted">Belum ada mahasiswa.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="legend muted">
        Keterangan: <strong>H</strong> Hadir · <strong>I</strong> Izin · <strong>S</strong> Sakit · <strong>A</strong> Alpa · <strong>-</strong> belum ada sesi.
        Persentase = jumlah Hadir ÷ sesi absensi. <span class="low">Merah</span> = kehadiran &lt; 75%.
    </div>

    <table class="sign">
        <tr>
            <td style="width:60%"></td>
            <td>
                Makassar, {{ now()->translatedFormat('d F Y') }}<br>Dosen Pengampu,<br><br><br><br>
                <strong>{{ $course->lecturer->name }}</strong><br>NIP. {{ $course->lecturer->nim_nip ?: '—' }}
            </td>
        </tr>
    </table>
</body>
</html>
