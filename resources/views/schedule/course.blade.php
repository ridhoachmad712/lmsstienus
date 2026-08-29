@extends('layouts.app')

@section('title', 'Jadwal · '.$course->name)

@section('content')
@php($canManageSchedule = auth()->user()->isDosen() && $course->user_id === auth()->id())
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
                                    <td>@if ($s->timeSlot){{ $s->timeSlot->name }} · @endif{{ $s->start_time }}–{{ $s->end_time }}</td>
                                    <td>{{ $s->roomLabel() ?? '—' }}</td>
                                    <td class="text-end">
                                        @if ($canManageSchedule)
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

    @if ($canManageSchedule)
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
                        <div class="mb-3"><label class="form-label required">Sesi</label>
                            @if ($timeSlots->isEmpty())
                                <div class="form-hint">Belum ada sesi. <a href="{{ route('admin.timeslots.index') }}">Tambah Sesi Kuliah</a> dulu.</div>
                            @else
                                <select name="time_slot_id" class="form-select" required>
                                    @foreach ($timeSlots as $slot)
                                        <option value="{{ $slot->id }}">{{ $slot->label() }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="mb-1"><label class="form-label">Ruangan</label>
                            <select name="room_id" class="form-select">
                                <option value="">— tanpa ruang —</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->label() }}</option>
                                @endforeach
                            </select>
                            @if ($rooms->isEmpty())<div class="form-hint mt-1"><a href="{{ route('admin.rooms.index') }}">Tambah Ruangan</a> di Data Master.</div>@endif
                        </div>
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button></div>
                </form>
            </div>
        @endunless
    @endif
</div>
@endsection
