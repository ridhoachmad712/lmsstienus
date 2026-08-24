@php($subnav = [
    ['courses.show', 'ti-home', 'Beranda', [$course], ['courses.show']],
    ['courses.materials', 'ti-folder', 'Materi', [$course], ['courses.materials']],
    ['assignments.index', 'ti-checklist', 'Tugas & Kuis', [$course], ['assignments.*', 'quizzes.*']],
    ['attendance.index', 'ti-qrcode', 'Absensi', [$course], ['attendance.*']],
    ['grades.index', 'ti-clipboard-check', 'Penilaian', [$course], ['grades.*']],
    ['forum.index', 'ti-messages', 'Forum', [$course], ['forum.*']],
    ['announcements.index', 'ti-speakerphone', 'Pengumuman', [$course], ['announcements.*']],
    ['syllabus.show', 'ti-file-text', 'RPS', [$course], ['syllabus.*']],
    ['schedule.course', 'ti-calendar-time', 'Jadwal', [$course], ['schedule.course']],
])

@if (auth()->user()->isDosen())
    @php(array_splice($subnav, 1, 1))
    @php($subnav[0] = ['courses.show', 'ti-folder', 'Pertemuan & Materi', [$course], ['courses.show']])
    @php(array_splice($subnav, 1, 0, [['courses.students', 'ti-users', 'Mahasiswa', [$course], ['courses.students']]]))
    @php($subnav[] = ['analytics.index', 'ti-chart-histogram', 'Analitik', [$course], ['analytics.*']])
    @php($subnav[] = ['reports.index', 'ti-report', 'Laporan', [$course], ['reports.*']])
@endif

@php($activeItem = collect($subnav)->first(fn ($it) => request()->routeIs($it[4])) ?? $subnav[0])

<div class="mb-3">
    {{-- HP, beranda mata kuliah: tujuan utama terlihat tanpa membuka dropdown. --}}
    @if (request()->routeIs('courses.show'))
        <div class="d-md-none">
            <div class="text-secondary small mb-2">Pilih bagian mata kuliah</div>
            <div class="course-mobile-menu">
                @foreach (array_slice($subnav, 1) as [$route, $icon, $label, $params, $patterns])
                    <a class="course-mobile-menu__item" href="{{ route($route, $params) }}">
                        <i class="ti {{ $icon }}"></i><span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @else
    {{-- HP, halaman bagian: satu app bar untuk konteks, kembali, dan perpindahan menu. --}}
    <div class="d-md-none course-app-bar">
        <a href="{{ route('courses.show', $course) }}" class="course-app-bar__back" aria-label="Kembali ke {{ $course->name }}"><i class="ti ti-chevron-left"></i></a>
        <div class="dropdown flex-fill min-w-0">
        <button class="course-app-bar__menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="course-app-bar__icon"><i class="ti {{ $activeItem[1] }}"></i></span>
            <span class="min-w-0 me-auto text-start">
                <span class="course-app-bar__title text-truncate">{{ $activeItem[2] }}</span>
                <span class="course-app-bar__course text-truncate">{{ $course->name }}</span>
            </span>
        </button>
        <div class="dropdown-menu w-100">
            @foreach ($subnav as [$route, $icon, $label, $params, $patterns])
                <a class="dropdown-item d-flex align-items-center {{ request()->routeIs($patterns) ? 'active' : '' }}" href="{{ route($route, $params) }}">
                    <i class="ti {{ $icon }} me-2"></i>{{ $label }}
                </a>
            @endforeach
        </div>
    </div>
    </div>
    @endif

    {{-- Desktop: pill --}}
    <ul class="nav nav-pills flex-nowrap overflow-x-auto lms-subnav gap-1 pb-1 d-none d-md-flex">
        @foreach ($subnav as [$route, $icon, $label, $params, $patterns])
            <li class="nav-item flex-shrink-0">
                <a class="nav-link text-nowrap {{ request()->routeIs($patterns) ? 'active' : '' }}" href="{{ route($route, $params) }}">
                    <i class="ti {{ $icon }} me-1"></i>{{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</div>

@push('styles')
<style>
@media (max-width:575.98px){
    .course-app-bar{display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;}
    .course-app-bar__back{display:grid;place-items:center;width:2.75rem;height:2.75rem;flex:0 0 2.75rem;border:1px solid var(--tblr-border-color);border-radius:.8rem;background:var(--tblr-bg-surface);color:inherit;text-decoration:none;box-shadow:0 1px 3px rgba(35,46,60,.05);}
    .course-app-bar__back i{font-size:1.25rem;}
    .course-app-bar__menu{display:flex;align-items:center;gap:.65rem;width:100%;min-height:2.75rem;padding:.35rem .75rem;border:1px solid var(--tblr-border-color);border-radius:.8rem;background:var(--tblr-bg-surface);color:inherit;box-shadow:0 1px 3px rgba(35,46,60,.05);}
    .course-app-bar__icon{display:grid;place-items:center;width:1.75rem;height:1.75rem;flex:0 0 1.75rem;border-radius:.55rem;background:rgba(var(--tblr-primary-rgb),.1);color:var(--tblr-primary);}
    .course-app-bar__title,.course-app-bar__course{display:block;line-height:1.15;}
    .course-app-bar__title{font-size:.8rem;font-weight:700;}
    .course-app-bar__course{margin-top:.15rem;color:var(--tblr-secondary-color);font-size:.68rem;}
    .course-mobile-menu{display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;}
    .course-mobile-menu__item{display:flex;min-width:0;min-height:5rem;padding:.65rem .35rem;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;border:1px solid var(--tblr-border-color);border-radius:.875rem;background:var(--tblr-bg-surface);color:inherit;text-decoration:none;text-align:center;font-size:.7rem;font-weight:600;box-shadow:0 1px 3px rgba(35,46,60,.05);transition:color .15s ease,background-color .15s ease,border-color .15s ease,transform .15s ease,box-shadow .15s ease;}
    .course-mobile-menu__item i{font-size:1.4rem;color:var(--tblr-primary);}
    .course-mobile-menu__item:hover,.course-mobile-menu__item:focus{color:var(--tblr-primary);background:rgba(var(--tblr-primary-rgb),.08);border-color:rgba(var(--tblr-primary-rgb),.35);text-decoration:none;transform:translateY(-2px);box-shadow:0 .35rem .8rem rgba(35,46,60,.1);}
}
@media (prefers-reduced-motion:reduce){.course-mobile-menu__item{transition:none;transform:none !important;}}
</style>
@endpush

@if ($course->isCompleted())
    <div class="alert alert-secondary d-flex align-items-center" role="alert">
        <i class="ti ti-lock me-2 fs-3"></i>
        <div class="me-auto"><strong>Kelas selesai</strong> — mode lihat saja (read-only). Buka kembali dari halaman kelas untuk mengubah.</div>
        @if (auth()->user()->isDosen())
            <form method="POST" action="{{ route('courses.complete', $course) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm"><i class="ti ti-lock-open me-1"></i>Buka Kembali</button>
            </form>
        @endif
    </div>
@endif
