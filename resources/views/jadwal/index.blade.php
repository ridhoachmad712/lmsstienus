@extends('layouts.app')

@section('title', 'Jadwal')
@section('page-pretitle', 'Akademik')
@section('page-title', $isDosen ? 'Jadwal Mengajar' : 'Jadwal Kuliah')

@section('content')
@php($hasAny = collect($byDay)->flatten(1)->isNotEmpty())
@unless ($hasAny)
    <div class="card"><div class="card-body"><x-empty-state icon="ti-calendar-off" title="Belum ada jadwal"
        :description="$isDosen ? 'Atur jadwal tiap kelas lewat tab Jadwal di halaman kelas.' : 'Jadwal muncul setelah dosen mengatur jam kuliah kelas Anda.'" /></div></div>
@else
<div class="row row-cards">
    @foreach (\App\Models\ClassSchedule::DAYS as $d => $label)
        @continue($d === 7 && empty($byDay[$d]))
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-header py-2"><h3 class="card-title">{{ $label }}</h3></div>
                <div class="list-group list-group-flush">
                    @forelse ($byDay[$d] ?? [] as $slot)
                        <div class="list-group-item py-2">
                            <div class="fw-bold">{{ $slot['s']->start_time }}–{{ $slot['s']->end_time }}
                                @if ($slot['s']->room)<span class="badge bg-blue-lt ms-1"><i class="ti ti-map-pin me-1"></i>{{ $slot['s']->room }}</span>@endif
                            </div>
                            <div>{{ $slot['course']->name }}</div>
                            <div class="small text-secondary">{{ $slot['course']->code }}@if ($slot['course']->class_name) · {{ $slot['course']->class_name }}@endif</div>
                        </div>
                    @empty
                        <div class="list-group-item text-secondary small py-2">—</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
@endunless
@endsection
