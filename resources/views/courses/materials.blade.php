@extends('layouts.app')

@section('title', 'Materi · '.$course->name)

@section('content')
@include('courses._hero')

<div class="d-flex align-items-end mb-3">
    <div>
        <div class="text-secondary small">Bahan pembelajaran</div>
        <h2 class="h2 mb-0">Materi Kuliah</h2>
    </div>
    <span class="badge bg-secondary-lt ms-auto">{{ $course->meetings->count() }} pertemuan</span>
</div>

@forelse ($course->meetings as $meeting)
    <section class="card mb-3 overflow-hidden">
        <div class="card-header d-flex align-items-start gap-2 py-3">
            <span class="badge bg-blue-lt flex-shrink-0">P{{ $meeting->number }}</span>
            <div class="min-w-0">
                <h3 class="card-title mb-0">{{ $meeting->topic }}</h3>
                @if ($meeting->date)<div class="text-secondary small mt-1">{{ $meeting->date->translatedFormat('d M Y') }}</div>@endif
            </div>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($meeting->materials as $material)
                @if ($material->isText())
                    <div class="list-group-item">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="avatar avatar-sm bg-purple-lt"><i class="ti ti-notes"></i></span>
                            <div class="fw-bold flex-fill">{{ $material->title }}</div>
                            <span class="badge bg-purple-lt">Teks</span>
                        </div>
                        @if ($material->content)
                            <div class="markdown text-secondary">{!! \Illuminate\Support\Str::markdown($material->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                        @endif
                    </div>
                @else
                    <a href="{{ route('materials.preview', $material) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                        <span class="avatar avatar-sm bg-blue-lt"><i class="ti ti-{{ $material->isFile() ? 'file-text' : 'link' }}"></i></span>
                        <div class="min-w-0 flex-fill">
                            <div class="fw-bold text-truncate">{{ $material->title }}</div>
                            <div class="text-secondary small">{{ $material->isFile() ? 'Berkas materi' : 'Tautan materi' }}</div>
                        </div>
                        <i class="ti ti-chevron-right text-secondary"></i>
                    </a>
                @endif
            @empty
                <div class="list-group-item text-secondary text-center py-3">Belum ada materi pada pertemuan ini.</div>
            @endforelse
        </div>
    </section>
@empty
    <div class="card"><div class="card-body">
        <x-empty-state icon="ti-folder-off" title="Belum ada materi" description="Dosen belum menambahkan pertemuan atau bahan pembelajaran." />
    </div></div>
@endforelse
@endsection
