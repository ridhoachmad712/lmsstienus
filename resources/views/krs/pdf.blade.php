<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 15px; margin: 0; }
        .head { text-align: center; border-bottom: 2px solid #206bc4; padding-bottom: 8px; margin-bottom: 10px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #ccc; padding: 3px 6px; }
        th { background: #206bc4; color: #fff; }
        td.c, th.c { text-align: center; }
        .idn td { border: none; padding: 1px 4px; }
        .idn td.k { color: #666; width: 22%; }
        .sign { margin-top: 28px; width: 100%; }
        .sign td { border: none; text-align: center; vertical-align: top; }
    </style>
</head>
<body>
    <div class="head">
        @if (! empty($logoData))<img src="{{ $logoData }}" style="height:40px;margin-bottom:4px;">@endif
        <h1>KARTU RENCANA STUDI (KRS)</h1>
        <div class="muted">{{ $footerText ?? '' }} — {{ $periodLabel }}</div>
    </div>

    <table class="idn">
        <tr><td class="k">Nama</td><td>: {{ $student->name }}</td><td class="k">Program Studi</td><td>: {{ $student->prodi->name ?? '—' }}</td></tr>
        <tr><td class="k">NIM</td><td>: {{ $student->nim_nip ?? '—' }}</td><td class="k">Dosen Wali</td><td>: {{ $student->advisor->name ?? '—' }}</td></tr>
    </table>

    <table>
        <thead><tr>
            <th class="c" style="width:5%">No</th><th style="width:14%">Kode</th><th>Mata Kuliah</th>
            <th style="width:18%">Kelas / Dosen</th><th class="c" style="width:8%">SKS</th><th class="c" style="width:14%">Status</th>
        </tr></thead>
        <tbody>
            @forelse ($items as $i => $e)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $e->course->mataKuliah->code ?? $e->course->code }}</td>
                    <td>{{ $e->course->mataKuliah->name ?? $e->course->name }}</td>
                    <td>{{ $e->course->class_name ?: $e->course->name }}<br><span class="muted">{{ $e->course->lecturer->name ?? '' }}</span></td>
                    <td class="c">{{ $e->course->mataKuliah->sks ?? 0 }}</td>
                    <td class="c">{{ $e->statusLabel() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="c muted">Belum ada mata kuliah di KRS.</td></tr>
            @endforelse
            <tr>
                <th colspan="4" style="text-align:right">Total SKS</th>
                <th class="c">{{ $totalSks }}</th>
                <th></th>
            </tr>
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td style="width:50%">
                Mahasiswa,<br><br><br><br>
                <strong>{{ $student->name }}</strong><br>NIM. {{ $student->nim_nip ?? '—' }}
            </td>
            <td style="width:50%">
                {{ now()->translatedFormat('d F Y') }}<br>Dosen Wali,<br><br><br><br>
                <strong>{{ $student->advisor->name ?? '_____________________' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
