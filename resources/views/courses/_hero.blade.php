@php
    $heroStudents = $course->students_count ?? ($course->relationLoaded('students') ? $course->students->count() : $course->students()->count());
    $heroMeetings = $course->meetings_count ?? ($course->relationLoaded('meetings') ? $course->meetings->count() : $course->meetings()->count());
@endphp
<div class="card mb-3 text-white course-hero" style="background: linear-gradient(135deg, #206bc4, #4263eb);">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <span class="avatar avatar-lg bg-white text-primary flex-shrink-0"><i class="ti ti-school fs-1"></i></span>
        <div class="me-auto" style="min-width:0">
            <div class="course-hero-title fw-bold mb-0 text-white">{{ $course->name }}</div>
            @unless (auth()->user()->isDosen())
                <div class="text-white-50 small mt-1 course-hero-lecturer"><i class="ti ti-user me-1"></i>{{ $course->lecturer->name }}</div>
            @endunless
            <div @class(['text-white-50 small course-hero-meta', 'd-none' => auth()->user()->isMahasiswa()])>
                {{ $course->code }}@if ($course->class_name) · {{ $course->class_name }}@endif · {{ $course->semester }} {{ $course->year }}
                @unless (auth()->user()->isDosen()) · {{ $course->lecturer->name }} @endunless
                @if ($course->isCompleted()) · <span class="badge bg-white text-dark">Selesai</span> @endif
            </div>
        </div>
        <div @class(['d-flex gap-4 text-center course-hero-stats', 'd-none' => auth()->user()->isMahasiswa()])>
            <div class="course-hero-students"><div class="h2 mb-0 text-white">{{ $heroStudents }}</div><div class="text-white-50 small">Mahasiswa</div></div>
            <div><div class="h2 mb-0 text-white">{{ $heroMeetings }}</div><div class="text-white-50 small">Pertemuan</div></div>
        </div>
        @hasSection('hero-actions')
            <div class="btn-list">@yield('hero-actions')</div>
        @endif
    </div>
</div>

@include('courses._subnav')

@push('styles')
<style>
    /* Tombol di hero (latar biru): varian yang menyatu dengan latar dibuat putih
       dengan teks berwarna semantik agar tetap terlihat & terbedakan. */
    .course-hero .btn-primary,
    .course-hero .btn-outline-primary,
    .course-hero .btn-outline-green,
    .course-hero .btn-outline-red {
        --tblr-btn-bg: #fff;
        --tblr-btn-border-color: #fff;
        --tblr-btn-hover-bg: #f1f3f5;
        --tblr-btn-hover-border-color: #f1f3f5;
        --tblr-btn-active-bg: #f1f3f5;
        --tblr-btn-active-border-color: #f1f3f5;
    }
    .course-hero .btn-primary,
    .course-hero .btn-outline-primary {
        --tblr-btn-color: var(--tblr-primary);
        --tblr-btn-hover-color: var(--tblr-primary);
        --tblr-btn-active-color: var(--tblr-primary);
    }
    .course-hero .btn-outline-green {
        --tblr-btn-color: var(--tblr-green);
        --tblr-btn-hover-color: var(--tblr-green);
        --tblr-btn-active-color: var(--tblr-green);
    }
    .course-hero .btn-outline-red {
        --tblr-btn-color: var(--tblr-red);
        --tblr-btn-hover-color: var(--tblr-red);
        --tblr-btn-active-color: var(--tblr-red);
    }
    @media (max-width:575.98px) {
        body.student-mobile-ui .course-hero .course-hero-students { display:none; }
        body.student-mobile-ui .course-hero .avatar-lg { display:none; }
        body.student-mobile-ui .course-hero .course-hero-meta,
        body.student-mobile-ui .course-hero .course-hero-stats { display:none !important; }
        body.student-mobile-ui .course-hero .card-body { padding:1rem; }
        body.student-mobile-ui .course-hero-stats { margin-left:auto; }
    }
</style>
@endpush
