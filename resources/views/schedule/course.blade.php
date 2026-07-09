@extends('layouts.app')

@section('title', 'Jadwal · '.$course->name)

@section('content')
@include('courses._hero')

<div class="row row-cards">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Jadwal Kuliah</h3></div>
            @if ($schedules->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-calendar-off" title="Belum ada jadwal" description="Tambahkan hari & jam kuliah kelas ini." /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Hari</th><th>Jam</th><th>Ruang</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($schedules as $s)
                                <tr>
                                    <td class="fw-bold">{{ $s->dayLabel() }}</td>
                                    <td>{{ $s->start_time }}–{{ $s->end_time }}</td>
                                    <td>{{ $s->room ?? '—' }}</td>
                                    <td class="text-end">
                                        @if (auth()->user()->isAdmin())
                                            @unless ($course->isCompleted())
                                                <form method="POST" action="{{ route('schedule.destroy', $s) }}" data-confirm="Hapus jadwal {{ $s->dayLabel() }} {{ $s->start_time }}?">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                                </form>
                                            @endunless
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

    @if (auth()->user()->isAdmin())
        @unless ($course->isCompleted())
            <div class="col-lg-5">
                <form class="card" method="POST" action="{{ route('schedule.store', $course) }}">
                    @csrf
                    <div class="card-header"><h3 class="card-title">Tambah Jadwal</h3></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label required">Hari</label>
                            <select name="day" class="form-select" required>
                                @foreach (\App\Models\ClassSchedule::DAYS as $d => $label)
                                    <option value="{{ $d }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3"><label class="form-label required">Mulai</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3"><label class="form-label required">Selesai</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-1"><label class="form-label">Ruang</label>
                            <input type="text" name="room" class="form-control" placeholder="mis. R.201 / Lab Komputer">
                        </div>
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button></div>
                </form>
            </div>
        @endunless
    @endif
</div>
@endsection
