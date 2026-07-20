@extends('layouts.app')

@section('title', 'Kelola Semester')
@section('page-pretitle', 'Admin')
@section('page-title', 'Kelola Semester')

@section('page-actions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-semester">
        <i class="ti ti-plus me-1"></i>Tambah Semester
    </button>
@endsection

@section('content')
{{-- ===================== RINGKASAN SEMESTER AKTIF (strip tipis) ===================== --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center flex-wrap gap-2">
        <span class="avatar rounded bg-primary-lt"><i class="ti ti-calendar-event"></i></span>
        <span class="fw-bold me-1">Semester aktif:</span>
        @forelse ($activeKeys as $k)
            <span class="badge bg-green-lt"><i class="ti ti-circle-check-filled me-1"></i>{{ \App\Models\Semester::keyLabel($k) }}</span>
        @empty
            <span class="text-secondary">Belum ada — centang di daftar bawah.</span>
        @endforelse
        <span class="form-hint mb-0 ms-auto text-end d-none d-md-block" style="max-width:32rem">
            Boleh lebih dari satu; periode terbaru jadi default kelas baru. Atur di <strong>Periode Akademik</strong> di bawah.
        </span>
    </div>
</div>

{{-- ===================== PENGATURAN PERIODE: KRS & EDOM (2 kolom) ===================== --}}
<div class="row row-cards mb-3">
    {{-- KRS --}}
    <div class="col-lg-6">
        <form class="card h-100" method="POST" action="{{ route('admin.semesters.krs') }}">
            @csrf @method('PUT')
            <div class="card-header">
                <span class="avatar rounded bg-{{ $krsOpen ? 'green' : 'secondary' }}-lt me-2"><i class="ti ti-clipboard-list"></i></span>
                <h3 class="card-title mb-0">Pengisian KRS</h3>
                <span class="ms-auto">
                    @if ($krsStart || $krsEnd)<span class="badge bg-azure-lt me-1"><i class="ti ti-calendar-clock me-1"></i>Terjadwal</span>@endif
                    <span class="badge bg-{{ $krsOpen ? 'green' : 'red' }}-lt">
                        <i class="ti ti-{{ $krsOpen ? 'lock-open' : 'lock' }} me-1"></i>{{ $krsOpen ? 'DIBUKA' : 'DITUTUP' }}
                    </span>
                </span>
            </div>
            <div class="card-body">
                <p class="form-hint">Mahasiswa menyusun &amp; mengajukan KRS untuk periode <strong>{{ $krsPeriodLabel }}</strong>. Dosen wali menyetujui di menu Perwalian.</p>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <label class="form-label mb-1">Jadwal buka <span class="text-secondary">(opsional)</span></label>
                        <input type="date" name="krs_start" class="form-control" value="{{ $krsStart }}">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label mb-1">Jadwal tutup</label>
                        <input type="date" name="krs_end" class="form-control" value="{{ $krsEnd }}">
                    </div>
                </div>
                <div class="row g-2 align-items-center mt-1">
                    <div class="col-auto">
                        <label class="form-label mb-1">Batas SKS</label>
                        <input type="number" name="krs_max_sks" class="form-control" style="width:6rem" value="{{ old('krs_max_sks', $krsMaxSks) }}" min="1" max="30" required>
                    </div>
                    <div class="col">
                        <label class="form-check form-switch mb-0 mt-4">
                            <input class="form-check-input" type="checkbox" name="krs_open" value="1" @checked($krsManual)>
                            <span class="form-check-label">Buka manual</span>
                        </label>
                    </div>
                </div>
                <div class="form-hint mt-2"><i class="ti ti-info-circle me-1"></i>Isi rentang tanggal untuk buka/tutup <strong>otomatis</strong>; kosongkan lalu pakai sakelar "Buka manual" untuk kontrol langsung.</div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button></div>
        </form>
    </div>

    {{-- EDOM --}}
    <div class="col-lg-6">
        <form class="card h-100" method="POST" action="{{ route('admin.semesters.edom') }}">
            @csrf @method('PUT')
            <div class="card-header">
                <span class="avatar rounded bg-{{ $edomOpen ? 'green' : 'secondary' }}-lt me-2"><i class="ti ti-star"></i></span>
                <h3 class="card-title mb-0">Evaluasi Dosen (EDOM)</h3>
                <span class="ms-auto">
                    @if ($edomStart || $edomEnd)<span class="badge bg-azure-lt me-1"><i class="ti ti-calendar-clock me-1"></i>Terjadwal</span>@endif
                    <span class="badge bg-{{ $edomOpen ? 'green' : 'red' }}-lt">
                        <i class="ti ti-{{ $edomOpen ? 'lock-open' : 'lock' }} me-1"></i>{{ $edomOpen ? 'DIBUKA' : 'DITUTUP' }}
                    </span>
                </span>
            </div>
            <div class="card-body">
                <p class="form-hint">Mahasiswa menilai dosen tiap kelas aktif yang diikuti. Hasil direkap di Akademik → Rekap EDOM.</p>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <label class="form-label mb-1">Jadwal buka <span class="text-secondary">(opsional)</span></label>
                        <input type="date" name="edom_start" class="form-control" value="{{ $edomStart }}">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label mb-1">Jadwal tutup</label>
                        <input type="date" name="edom_end" class="form-control" value="{{ $edomEnd }}">
                    </div>
                </div>
                <label class="form-check form-switch mb-1 mt-2">
                    <input class="form-check-input" type="checkbox" name="edom_open" value="1" @checked($edomManual)>
                    <span class="form-check-label">Buka manual</span>
                </label>
                <label class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="edom_required" value="1" @checked($edomRequired)>
                    <span class="form-check-label">Wajib diisi — kunci nilai/transkrip sampai EDOM lengkap</span>
                </label>
                <div class="form-hint mt-2"><i class="ti ti-info-circle me-1"></i>Isi rentang tanggal untuk otomatis; kosongkan lalu pakai sakelar "Buka manual".</div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button></div>
        </form>
    </div>
</div>

{{-- ===================== DAFTAR PERIODE ===================== --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Periode Akademik</h3>
        <div class="card-actions text-secondary">{{ $periods->count() }} semester</div>
    </div>

    @if ($periods->isEmpty())
        <div class="card-body">
            <x-empty-state icon="ti-calendar" title="Belum ada semester"
                description="Tambahkan semester lewat tombol “Tambah Semester” di kanan atas." />
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <th class="w-1 text-center">Aktif</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th class="text-center">Kelas</th>
                    <th class="text-center">Dosen</th>
                    <th class="text-center">Mahasiswa</th>
                    <th class="text-center">SKS</th>
                    <th class="text-end">Aksi</th>
                </tr></thead>
                <tbody>
                    @foreach ($periods as $p)
                        <tr @class(['table-active' => $p->is_active])>
                            <td class="text-center">
                                {{-- Terhubung ke form Simpan via atribut form= (hindari form bersarang) --}}
                                <input class="form-check-input m-0" type="checkbox" name="periods[]"
                                       value="{{ $p->key }}" form="active-form" @checked($p->is_active)
                                       aria-label="Aktifkan {{ $p->label }}">
                            </td>
                            <td>
                                <span class="fw-bold">{{ $p->label }}</span>
                                <div class="small text-secondary">TA {{ $p->academic_year }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $p->status_color }}-lt"><i class="ti {{ $p->status_icon }} me-1"></i>{{ $p->status_label }}</span>
                            </td>
                            <td class="text-center">{{ $p->courses_count }}</td>
                            <td class="text-center">{{ $p->lecturers_count }}</td>
                            <td class="text-center">{{ $p->students_count }}</td>
                            <td class="text-center">{{ $p->sks_total ?: '—' }}</td>
                            <td>
                                <div class="btn-list justify-content-end">
                                    @if ($p->id)
                                        <form method="POST" action="{{ route('admin.semesters.destroy', $p->id) }}"
                                              data-confirm="Hapus semester {{ $p->label }} dari daftar?@if ($p->courses_count > 0) (Akan ditolak karena masih ada {{ $p->courses_count }} kelas.)@endif">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-danger" title="Hapus semester" data-bs-toggle="tooltip">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex align-items-center flex-wrap gap-2">
            <div class="text-secondary small">Centang semester yang ingin diaktifkan (boleh lebih dari satu), lalu simpan. Semester hanya bisa dihapus bila tidak ada kelas di dalamnya.</div>
            <form id="active-form" method="POST" action="{{ route('admin.semesters.updateActive') }}" class="ms-auto">
                @csrf @method('PUT')
                <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Semester Aktif</button>
            </form>
        </div>
    @endif
</div>

{{-- ===================== MODAL TAMBAH SEMESTER ===================== --}}
<div class="modal modal-blur fade" id="modal-add-semester" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.semesters.store') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-7 mb-3">
                        <label class="form-label required">Tahun Ajaran</label>
                        <input type="number" name="year" class="form-control" value="{{ old('year', $academicYear) }}" min="2000" max="2100" required>
                    </div>
                    <div class="col-5 mb-3">
                        <label class="form-label required">Semester</label>
                        <select name="semester" class="form-select">
                            @foreach (['Ganjil', 'Genap', 'Antara'] as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="activate" value="1">
                    <span class="form-check-label">Langsung aktifkan semester ini</span>
                    <span class="form-check-description">Ditambahkan ke daftar semester aktif; periode terbaru menjadi default saat membuat kelas.</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Tambah</button>
            </div>
        </form>
    </div>
</div>
@endsection
