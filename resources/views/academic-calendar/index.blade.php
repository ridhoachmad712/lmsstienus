@extends('layouts.app')

@section('title', 'Kalender Akademik')
@section('page-pretitle', 'Akademik')
@section('page-title', 'Kalender Akademik')

@section('page-actions')
    <div class="dropdown">
        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="ti ti-calendar me-1"></i>{{ $periodLabel }}</button>
        <div class="dropdown-menu dropdown-menu-end">
            @foreach ($periods as $k)
                <a class="dropdown-item {{ $period === $k ? 'active' : '' }}" href="{{ route('academic.calendar', ['periode' => $k]) }}">{{ \App\Models\Semester::keyLabel($k) }}</a>
            @endforeach
        </div>
    </div>
@endsection

@section('content')
<div class="row row-cards">
    <div class="{{ $canManage ? 'col-lg-8' : 'col-12' }}">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Agenda {{ $periodLabel }}</h3></div>
            @if ($events->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-calendar-off" title="Belum ada agenda" description="Agenda akademik untuk periode ini belum diisi." /></div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($events as $e)
                        <div class="list-group-item d-flex align-items-center @if ($e->isPast()) opacity-75 @endif">
                            <span class="avatar bg-{{ $e->typeColor() }}-lt me-3"><i class="ti {{ $e->typeIcon() }}"></i></span>
                            <div class="me-auto">
                                <div class="fw-bold">{{ $e->title }}
                                    @if ($e->isOngoing())<span class="badge bg-green-lt ms-1">berlangsung</span>@endif
                                </div>
                                <div class="text-secondary small">{{ $e->typeLabel() }}@if ($e->note) · {{ $e->note }}@endif</div>
                            </div>
                            <div class="text-nowrap text-secondary small">{{ $e->dateRange() }}</div>
                            @if ($canManage)
                                <form method="POST" action="{{ route('academic.calendar.destroy', $e) }}" class="ms-3" onsubmit="return confirm('Hapus agenda ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($canManage)
        <div class="col-lg-4">
            <form class="card" method="POST" action="{{ route('academic.calendar.store') }}">
                @csrf
                <div class="card-header"><h3 class="card-title">Tambah Agenda</h3></div>
                <div class="card-body">
                    @php
                        [$py, $ps] = explode('-', $period, 2);
                    @endphp
                    <div class="mb-2"><label class="form-label required">Judul</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="mis. Pengisian KRS" required>
                    </div>
                    <div class="mb-2"><label class="form-label required">Jenis</label>
                        <select name="type" class="form-select">
                            @foreach (\App\Models\AcademicEvent::TYPES as $key => $t)
                                <option value="{{ $key }}" @selected(old('type') === $key)>{{ $t[0] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2"><label class="form-label required">Mulai</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="col-6 mb-2"><label class="form-label">Selesai</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-7 mb-2"><label class="form-label required">Tahun</label>
                            <input type="number" name="year" class="form-control" value="{{ old('year', $py) }}" min="2000" max="2100" required>
                        </div>
                        <div class="col-5 mb-2"><label class="form-label required">Semester</label>
                            <select name="semester" class="form-select">
                                @foreach (['Ganjil', 'Genap', 'Antara'] as $s)
                                    <option value="{{ $s }}" @selected(old('semester', $ps) === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-1"><label class="form-label">Catatan</label>
                        <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="opsional">
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button></div>
            </form>
        </div>
    @endif
</div>
@endsection
