@extends('layouts.app')

@section('title', 'Pencarian')
@section('page-pretitle', 'Pencarian')
@section('page-title', $q !== '' ? 'Hasil untuk “'.$q.'”' : 'Pencarian')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <form method="GET" action="{{ route('search') }}" class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Cari kelas, tugas/kuis, atau mahasiswa…" autofocus>
                <button class="btn btn-primary">Cari</button>
            </div>
        </form>

        @php($isStaff = auth()->user()->isStaff())
        @php($total = $courses->count() + $assignments->count() + $students->count() + $lecturers->count() + $mataKuliah->count())
        @if ($q !== '' && $total === 0)
            <div class="card"><div class="card-body"><x-empty-state icon="ti-search-off" title="Tidak ada hasil" description="Coba kata kunci lain." /></div></div>
        @endif

        @if ($students->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Mahasiswa</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($students as $s)
                        @if ($isStaff)
                            <a href="{{ route('admin.students.edit', $s) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <span class="avatar bg-secondary-lt me-2">{{ strtoupper(mb_substr($s->name,0,1)) }}</span>
                                <div><div class="fw-bold">{{ $s->name }}</div><div class="small text-secondary">{{ $s->nim_nip }} · {{ $s->prodi?->name ?? '—' }}</div></div>
                            </a>
                        @else
                            <div class="list-group-item d-flex align-items-center">
                                <span class="avatar bg-secondary-lt me-2">{{ strtoupper(mb_substr($s->name,0,1)) }}</span>
                                <div><div class="fw-bold">{{ $s->name }}</div><div class="small text-secondary">{{ $s->nim_nip }} · {{ $s->email }}</div></div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($lecturers->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Dosen &amp; Kaprodi</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($lecturers as $d)
                        <div class="list-group-item d-flex align-items-center">
                            <span class="avatar bg-blue-lt me-2"><i class="ti ti-user-star"></i></span>
                            <div><div class="fw-bold">{{ $d->name }}</div><div class="small text-secondary text-capitalize">{{ $d->role }} · {{ $d->prodi?->name ?? '—' }} · {{ $d->nim_nip }}</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($mataKuliah->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Mata Kuliah</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($mataKuliah as $mk)
                        <a href="{{ route('admin.matakuliah.edit', $mk) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="avatar bg-green-lt me-2"><i class="ti ti-book"></i></span>
                            <div><div class="fw-bold">{{ $mk->code }} — {{ $mk->name }}</div><div class="small text-secondary">{{ $mk->sks }} SKS</div></div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($courses->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Kelas</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($courses as $c)
                        @if ($isStaff)
                            <div class="list-group-item d-flex align-items-center">
                                <span class="avatar bg-primary-lt me-2"><i class="ti ti-school"></i></span>
                                <div><div class="fw-bold">{{ $c->name }}</div><div class="small text-secondary">{{ $c->code }} · {{ $c->lecturer?->name ?? '—' }} · {{ $c->semester }} {{ $c->year }}</div></div>
                            </div>
                        @else
                            <a href="{{ route('courses.show', $c) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <span class="avatar bg-primary-lt me-2"><i class="ti ti-school"></i></span>
                                <div><div class="fw-bold">{{ $c->name }}</div><div class="small text-secondary">{{ $c->code }} · {{ $c->semester }} {{ $c->year }}</div></div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($assignments->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Tugas & Kuis</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($assignments as $a)
                        <a href="{{ route('assignments.show', $a) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="avatar bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt me-2"><i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-file-text' }}"></i></span>
                            <div><div class="fw-bold">{{ $a->title }}</div><div class="small text-secondary">{{ $a->course->name }}</div></div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
