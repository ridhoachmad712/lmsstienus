@extends('layouts.app')

@section('title', 'Mahasiswa — ' . $course->name)

@section('content')
@include('courses._hero')

@if ($course->siakad_schedule_id)
    @if ($siakadRoster['error'])
        <div class="alert alert-warning"><i class="ti ti-plug-connected-x me-1"></i>{{ $siakadRoster['error'] }}</div>
    @elseif ($siakadRoster['available'] && ($siakadRoster['missing']->isNotEmpty() || $siakadRoster['officialOnly']->isNotEmpty()))
        <div class="alert alert-danger">
            <div class="fw-bold mb-1"><i class="ti ti-users-minus me-1"></i>Peserta LMS belum sama dengan KRS resmi</div>
            @if ($siakadRoster['missing']->isNotEmpty())
                <div>Tidak ada di KRS resmi: {{ $siakadRoster['missing']->map(fn ($s) => $s->name.' ('.($s->nim_nip ?: 'NIM kosong').')')->join(', ') }}.</div>
            @endif
            @if ($siakadRoster['officialOnly']->isNotEmpty())
                <div>Ada di KRS tetapi belum masuk LMS: {{ $siakadRoster['officialOnly']->join(', ') }}.</div>
            @endif
            <div class="small mt-1">Selaraskan peserta sebelum pembelajaran berjalan agar nilai akhir tidak gagal disinkronkan.</div>
        </div>
    @elseif ($siakadRoster['available'])
        <div class="alert alert-success"><i class="ti ti-circle-check me-1"></i>Seluruh peserta LMS sesuai dengan KRS resmi SIAKAD.</div>
    @endif
@endif

{{-- Akses mahasiswa ke kelas LMS. --}}
<div class="card mb-3">
    <div class="card-body d-flex align-items-center flex-wrap gap-3">
        <div class="me-auto">
            <div class="text-secondary small mb-1">Kode gabung mahasiswa</div>
            <div class="d-flex align-items-center gap-2">
                <code class="fs-2 fw-bold text-primary user-select-all">{{ $course->join_code }}</code>
                <span class="text-secondary small">Berlaku untuk semua program studi</span>
            </div>
        </div>
        @unless ($course->isCompleted())
            <form method="POST" action="{{ route('enrollments.regenerateJoinCode', $course) }}" data-confirm="Ganti kode gabung? Kode lama tidak dapat digunakan lagi.">
                @csrf @method('PATCH')
                <button class="btn btn-sm"><i class="ti ti-refresh me-1"></i>Ganti Kode</button>
            </form>
            <a href="{{ route('enrollments.template') }}" class="btn btn-sm"><i class="ti ti-download me-1"></i>Template CSV</a>
        @endunless
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3 d-flex gap-2 align-items-center">
            @if (! $students->isEmpty())
                <input type="text" class="form-control form-control-sm" style="max-width:240px" placeholder="Cari mahasiswa…" data-table-search="#tbl-students">
            @endif
            @unless ($course->isCompleted())
            <div class="btn-list ms-auto">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import">
                    <i class="ti ti-file-import me-1"></i>Import CSV
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-enroll">
                    <i class="ti ti-user-plus me-1"></i>Tambah Mahasiswa
                </button>
            </div>
            @endunless
        </div>

        @if ($students->isEmpty())
            <x-empty-state icon="ti-users" title="Belum ada mahasiswa"
                description="Tambahkan mahasiswa secara manual atau impor dari berkas CSV." />
        @else
            <div class="table-responsive">
                <table id="tbl-students" class="table table-vcenter table-sortable">
                    <thead><tr><th class="w-1 no-sort">#</th><th>Nama</th><th>NIM</th><th>Email</th><th class="no-sort"></th></tr></thead>
                    <tbody>
                        @foreach ($students as $i => $student)
                            <tr>
                                <td class="text-secondary">{{ $i + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <x-avatar :name="$student->name" :url="$student->avatarUrl()" class="me-2" />
                                        {{ $student->name }}
                                    </div>
                                </td>
                                <td>{{ $student->nim_nip ?? '—' }}</td>
                                <td class="text-secondary">{{ $student->email }}</td>
                                <td class="text-end">
                                    @unless ($course->isCompleted())
                                    <div class="btn-list justify-content-end">
                                        <form method="POST" action="{{ route('enrollments.destroy', [$course, $student]) }}"
                                              data-confirm="Keluarkan {{ $student->name }} dari kelas?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" title="Keluarkan" data-bs-toggle="tooltip"><i class="ti ti-user-minus"></i></button>
                                        </form>
                                    </div>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ============ MODALS ============ --}}
@unless ($course->isCompleted())
    {{-- Enroll mahasiswa --}}
    <div class="modal modal-blur fade" id="modal-enroll" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" action="{{ route('enrollments.store', $course) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Mahasiswa ke Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($availableStudents->isEmpty())
                        <div class="text-secondary">Semua mahasiswa terdaftar sudah ada di kelas ini. Gunakan Import CSV untuk menambah akun baru.</div>
                    @else
                        <p class="text-secondary">Pilih mahasiswa yang ingin ditambahkan:</p>
                        <div style="max-height:320px;overflow:auto;" x-data>
                            @foreach ($availableStudents as $s)
                                <label class="form-check">
                                    <input type="checkbox" name="user_ids[]" value="{{ $s->id }}" class="form-check-input">
                                    <span class="form-check-label">{{ $s->name }} <span class="text-secondary">({{ $s->nim_nip ?? '—' }})</span></span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" @disabled($availableStudents->isEmpty())>Tambahkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Import CSV --}}
    <div class="modal modal-blur fade" id="modal-import" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('enrollments.import', $course) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Mahasiswa dari CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Berkas CSV</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <div class="alert alert-info mb-0">
                        <strong>Format kolom:</strong> <code>nama, email, nim</code> (satu mahasiswa per baris).
                        Akun baru otomatis dibuat dengan kata sandi = NIM.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
@endunless
@endsection
