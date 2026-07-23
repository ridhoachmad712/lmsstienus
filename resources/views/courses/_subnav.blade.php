@php($subnav = [
    ['courses.show', 'ti-folder', 'Pertemuan & Materi', [$course], ['courses.show']],
    ['assignments.index', 'ti-checklist', 'Tugas & Kuis', [$course], ['assignments.*', 'quizzes.*']],
    ['attendance.index', 'ti-qrcode', 'Absensi', [$course], ['attendance.*']],
    ['grades.index', 'ti-clipboard-check', 'Penilaian', [$course], ['grades.*']],
    ['forum.index', 'ti-messages', 'Forum', [$course], ['forum.*']],
    ['announcements.index', 'ti-speakerphone', 'Pengumuman', [$course], ['announcements.*']],
    ['syllabus.show', 'ti-file-text', 'RPS', [$course], ['syllabus.*']],
])

@if (auth()->user()->isDosen())
    @php(array_splice($subnav, 1, 0, [['courses.students', 'ti-users', 'Mahasiswa', [$course], ['courses.students']]]))
    @php($subnav[] = ['analytics.index', 'ti-chart-histogram', 'Analitik', [$course], ['analytics.*']])
    @php($subnav[] = ['reports.index', 'ti-report', 'Laporan', [$course], ['reports.*']])
@endif

@php($activeItem = collect($subnav)->first(fn ($it) => request()->routeIs($it[4])) ?? $subnav[0])

<div class="mb-3">
    {{-- HP: satu dropdown "Menu Kelas" (menampilkan bagian aktif) --}}
    <div class="dropdown d-md-none">
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
