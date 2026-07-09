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
        <h1>KARTU HASIL STUDI (KHS)</h1>
        <div class="muted">{{ $footerText ?? '' }} — {{ $period['label'] }}</div>
    </div>

    <table class="idn">
        <tr><td class="k">Nama</td><td>: {{ $student->name }}</td><td class="k">Program Studi</td><td>: {{ $student->prodi->name ?? '—' }}</td></tr>
        <tr><td class="k">NIM</td><td>: {{ $student->nim_nip ?? '—' }}</td><td class="k">Dosen Wali</td><td>: {{ $student->advisor->name ?? '—' }}</td></tr>
    </table>

    <table>
        <thead><tr>
            <th class="c" style="width:5%">No</th><th style="width:16%">Kode</th><th>Mata Kuliah</th>
            <th class="c" style="width:8%">SKS</th><th class="c" style="width:10%">Huruf</th><th class="c" style="width:10%">Mutu</th>
        </tr></thead>
        <tbody>
            @foreach ($period['items'] as $i => $it)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $it['code'] }}</td>
                    <td>{{ $it['name'] }}</td>
                    <td class="c">{{ $it['sks'] ?: '-' }}</td>
                    <td class="c">{{ $it['running'] ? '(berjalan)' : $it['letter'] }}</td>
                    <td class="c">{{ $it['counted'] ? number_format($it['sks'] * $it['point'], 2) : '-' }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="3" style="text-align:right">Jumlah SKS semester ini / IPS</th>
                <th class="c">{{ $period['sks'] }}</th>
                <th class="c" colspan="2">IPS {{ is_null($period['ips']) ? '—' : number_format($period['ips'], 2) }}</th>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top:6px">Kumulatif s.d. periode ini: {{ $total_sks }} SKS · IPK {{ number_format($ipk, 2) }}</p>

    <table class="sign">
        <tr>
            <td style="width:60%"></td>
            <td>
                {{ now()->translatedFormat('d F Y') }}<br>Dosen Wali,<br><br><br><br>
                <strong>{{ $student->advisor->name ?? '_____________________' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
