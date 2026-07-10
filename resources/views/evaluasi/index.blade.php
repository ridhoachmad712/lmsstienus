@extends('layouts.app')

@section('title', 'Evaluasi Dosen')
@section('page-pretitle', 'Akademik')
@section('page-title', 'Evaluasi Dosen (EDOM)')

@section('content')
@unless ($edomOpen)
    <div class="alert alert-warning"><i class="ti ti-lock me-1"></i>Periode pengisian EDOM sedang <strong>tutup</strong>.</div>
@endunless

@if ($courses->isEmpty())
    <div class="card"><div class="card-body">
        <x-empty-state icon="ti-star" title="Belum ada kelas untuk dievaluasi" description="Kelas aktif yang Anda ikuti akan muncul di sini saat EDOM dibuka." />
    </div></div>
@else
    <div class="text-secondary small mb-3">Skor: 1 = Kurang · 2 = Cukup · 3 = Baik · 4 = Sangat Baik. Penilaian bersifat anonim.</div>
    @foreach ($courses as $course)
        @php($done = in_array($course->id, $doneCourseIds))
        <div class="card mb-3">
            <div class="card-header">
                <div>
                    <h3 class="card-title mb-0">{{ $course->name }}</h3>
                    <div class="text-secondary small">{{ $course->code }} · {{ $course->lecturer->name }}</div>
                </div>
                @if ($done)<span class="badge bg-green-lt ms-auto"><i class="ti ti-check me-1"></i>Sudah dievaluasi</span>@endif
            </div>
            @if (! $done && $edomOpen)
                <form method="POST" action="{{ route('edom.store', $course) }}">
                    @csrf
                    <div class="card-body">
                        @foreach ($questions as $i => $q)
                            <div class="mb-3">
                                <label class="form-label">{{ $i + 1 }}. {{ $q }}</label>
                                <div class="btn-group w-100" role="group">
                                    @foreach ($scaleLabels as $val => $lbl)
                                        <input type="radio" class="btn-check" name="answers[{{ $i }}]" id="c{{ $course->id }}q{{ $i }}v{{ $val }}" value="{{ $val }}" required>
                                        <label class="btn btn-outline-primary" for="c{{ $course->id }}q{{ $i }}v{{ $val }}">{{ $val }} · {{ $lbl }}</label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <div class="mb-1">
                            <label class="form-label">Komentar / saran (opsional)</label>
                            <textarea name="comment" class="form-control" rows="2" maxlength="1000"></textarea>
                        </div>
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-send me-1"></i>Kirim Evaluasi</button></div>
                </form>
            @elseif (! $done)
                <div class="card-body text-secondary small">Menunggu periode EDOM dibuka.</div>
            @endif
        </div>
    @endforeach
@endif
@endsection
