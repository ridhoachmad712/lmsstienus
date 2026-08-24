@php($subnav = [
    ['courses.show', 'ti-home', 'Beranda', [$course], ['courses.show']],
    ['courses.materials', 'ti-folder', 'Materi', [$course], ['courses.materials']],
    ['assignments.index', 'ti-checklist', 'Tugas & Kuis', [$course], ['assignments.*', 'quizzes.*']],
    ['attendance.index', 'ti-qrcode', 'Absensi', [$course], ['attendance.*']],
    ['grades.index', 'ti-clipboard-check', 'Penilaian', [$course], ['grades.*']],
    ['forum.index', 'ti-messages', 'Forum', [$course], ['forum.*']],
    ['announcements.index', 'ti-speakerphone', 'Pengumuman', [$course], ['announcements.*']],
    ['syllabus.show', 'ti-file-text', 'RPS', [$course], ['syllabus.*']],
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
    {{-- HP, halaman bagian: tombol kembali menjaga konteks mata kuliah. --}}
    <div class="d-md-none d-flex gap-2">
        <a href="{{ route('courses.show', $course) }}" class="btn btn-icon flex-shrink-0" aria-label="Kembali ke {{ $course->name }}"><i class="ti ti-arrow-left"></i></a>
        <div class="dropdown flex-fill">
        <button class="btn w-100 d-flex align-items-center dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti {{ $activeItem[1] }} me-2"></i>
            <span class="me-auto text-truncate">{{ $activeItem[2] }}</span>
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
    .course-mobile-menu{display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;}
    .course-mobile-menu__item{display:flex;min-width:0;min-height:5rem;padding:.65rem .35rem;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;border:1px solid var(--tblr-border-color);border-radius:.875rem;background:var(--tblr-bg-surface);color:inherit;text-decoration:none;text-align:center;font-size:.7rem;font-weight:600;box-shadow:0 1px 3px rgba(35,46,60,.05);}
    .course-mobile-menu__item i{font-size:1.4rem;color:var(--tblr-primary);}
}
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
