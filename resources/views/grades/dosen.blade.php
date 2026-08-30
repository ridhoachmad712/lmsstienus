@extends('layouts.app')

@section('title', 'Penilaian')

@section('hero-actions')
    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#modal-komponen">
        <i class="ti ti-adjustments-alt me-1"></i>Komponen Nilai
        <span class="badge bg-{{ $summary['weight_total'] === 100 ? 'green' : 'orange' }}-lt ms-1">{{ $summary['weight_total'] }}%</span>
    </button>
    <a href="{{ route('export.nilai.excel', $course) }}" class="btn btn-outline-green"><i class="ti ti-file-spreadsheet me-1"></i>Excel</a>
    <a href="{{ route('export.nilai.pdf', $course) }}" class="btn btn-outline-red"><i class="ti ti-file-type-pdf me-1"></i>PDF</a>
@endsection

@section('content')
@include('courses._hero')

@php($weightTotal = $summary['weight_total'])

{{-- Peringatan alur penilaian --}}
@if (! $components->isEmpty() && $weightTotal !== 100)
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="ti ti-alert-triangle me-2 fs-3"></i>
        <div>Total bobot komponen <strong>{{ $weightTotal }}%</strong> (seharusnya 100%).
            @if ($weightTotal < 100) Nilai akhir akan lebih rendah dari semestinya karena ada bobot yang belum dialokasikan.
            @else Bobot melebihi 100% — nilai akhir bisa melampaui 100. @endif
            <a href="#" data-bs-toggle="modal" data-bs-target="#modal-komponen" class="alert-link ms-1">Atur komponen</a>.
        </div>
    </div>
@endif
@if (($unlinkedGraded ?? 0) > 0)
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="ti ti-link-off me-2 fs-3"></i>
        <div>Ada <strong>{{ $unlinkedGraded }}</strong> tugas/kuis yang sudah dinilai tapi <strong>belum ditautkan</strong> ke komponen nilai, jadi nilainya tidak masuk rekap. Buka tugas → Edit → pilih <em>Komponen Nilai</em>.</div>
    </div>
@endif
@if (($summary['pending_students'] ?? 0) > 0)
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="ti ti-hourglass me-2 fs-3"></i>
        <div><strong>{{ $summary['pending_students'] }} mahasiswa</strong> memiliki pengumpulan yang belum dinilai. Komponen terkait ditampilkan kosong dan finalisasi kelas/SIAKAD dikunci sampai koreksi selesai.</div>
    </div>
@endif

{{-- Integrasi nilai resmi SIAKAD --}}
@php($syncCounts = $gradeSyncs->countBy('status'))
<div class="card mb-3">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="ti ti-arrows-exchange me-2"></i>Sinkronisasi Nilai SIAKAD</h3>
            <div class="text-secondary small">Nilai hanya dikirim kepada mahasiswa yang memiliki KRS resmi pada jadwal yang dipilih.</div>
        </div>
        @if ($course->grades_finalized_at)
            <div class="ms-auto text-end small">
                <div class="text-green fw-bold"><i class="ti ti-lock-check me-1"></i>Difinalisasi</div>
                <div class="text-secondary">{{ $course->grades_finalized_at->format('d M Y H:i') }} WITA</div>
            </div>
        @endif
    </div>
    <div class="card-body">
        @if (! $syncOverview['enabled'])
            <div class="alert alert-info mb-0">
                <i class="ti ti-settings me-1"></i>Sinkronisasi nilai belum diaktifkan administrator. Aktifkan <code>SIAKAD_GRADE_SYNC_ENABLED</code> setelah koneksi database resmi siap.
            </div>
        @elseif (! $syncOverview['configured'])
            <div class="alert alert-warning mb-0">
                <i class="ti ti-database-off me-1"></i>Koneksi database SIAKAD pada LMS belum lengkap.
            </div>
        @elseif ($syncOverview['connectionError'])
            <div class="alert alert-danger mb-0"><i class="ti ti-plug-connected-x me-1"></i>{{ $syncOverview['connectionError'] }}</div>
        @else
            <div class="row g-3 align-items-end">
                <div class="col-lg-7">
                    <form method="POST" action="{{ route('grades.siakad.map', $course) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col">
                            <label class="form-label">Jadwal resmi SIAKAD</label>
                            @if ($syncOverview['candidates']->isNotEmpty())
                                <select name="siakad_schedule_id" class="form-select">
                                    <option value="">— pilih jadwal —</option>
                                    @if ($course->siakad_schedule_id && ! $syncOverview['candidates']->contains('id_jadwal', $course->siakad_schedule_id))
                                        <option value="{{ $course->siakad_schedule_id }}" selected>Jadwal #{{ $course->siakad_schedule_id }} (pemetaan tersimpan)</option>
                                    @endif
                                    @foreach ($syncOverview['candidates'] as $candidate)
                                        <option value="{{ $candidate->id_jadwal }}" @selected((int) $course->siakad_schedule_id === (int) $candidate->id_jadwal)>
                                            #{{ $candidate->id_jadwal }} · {{ $candidate->kode_mk }} · {{ $candidate->kode_prodi }} · {{ $candidate->ket }} {{ $candidate->thn_akademik }} · NIP {{ $candidate->nip }}{{ $candidate->lecturer_match ? ' (dosen cocok)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="number" min="1" name="siakad_schedule_id" value="{{ $course->siakad_schedule_id }}" class="form-control" placeholder="ID jadwal SIAKAD">
                                <small class="form-hint">Kandidat otomatis tidak ditemukan. Periksa kode MK/periode atau isi ID jadwal resmi.</small>
                            @endif
                        </div>
                        <div class="col-auto"><button class="btn"><i class="ti ti-link me-1"></i>Simpan Pemetaan</button></div>
                    </form>
                </div>
                <div class="col-lg-5 text-lg-end">
                    @if (! $course->isCompleted())
                        <div class="text-secondary"><i class="ti ti-info-circle me-1"></i>Selesaikan kelas setelah 16 pertemuan dan seluruh nilai lengkap untuk membuka pengiriman.</div>
                    @else
                        <form method="POST" action="{{ route('grades.siakad.sync', $course) }}" data-confirm="Finalisasi snapshot nilai dan kirim ke database resmi SIAKAD?">
                            @csrf
                            <button class="btn btn-primary">
                                <i class="ti ti-cloud-upload me-1"></i>{{ $course->grades_finalized_at ? 'Sinkronkan Ulang / Retry' : 'Finalisasi & Kirim ke SIAKAD' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($gradeSyncs->isNotEmpty())
                <div class="mt-3 pt-3 border-top d-flex gap-2 flex-wrap align-items-center">
                    <span class="text-secondary small me-1">Status {{ $gradeSyncs->count() }} mahasiswa:</span>
                    <span class="badge bg-green-lt">{{ $syncCounts[\App\Models\SiakadGradeSync::STATUS_SYNCED] ?? 0 }} tersinkron</span>
                    <span class="badge bg-red-lt">{{ $syncCounts[\App\Models\SiakadGradeSync::STATUS_FAILED] ?? 0 }} gagal</span>
                    <span class="badge bg-orange-lt">{{ $syncCounts[\App\Models\SiakadGradeSync::STATUS_STALE] ?? 0 }} perlu ulang</span>
                    <span class="text-secondary small ms-auto">Retry aman: nilai yang sudah sama tidak ditulis ulang.</span>
                </div>
            @endif
        @endif
    </div>
</div>

{{-- Rekap (lebar penuh) --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Rekap Nilai</h3>
        <div class="ms-auto text-secondary small">
            Rata-rata <strong>{{ $summary['avg'] }}</strong> · Tertinggi <strong>{{ $summary['max'] }}</strong> · Terendah <strong>{{ $summary['min'] }}</strong>
        </div>
    </div>
    @if ($components->isEmpty())
        <div class="card-body text-center">
            <x-empty-state icon="ti-clipboard-off" title="Atur komponen nilai dulu" description="Nilai akhir butuh komponen berbobot." />
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-komponen"><i class="ti ti-adjustments-alt me-1"></i>Kelola Komponen Nilai</button>
        </div>
    @elseif ($rows->isEmpty())
        <div class="card-body"><x-empty-state icon="ti-users" title="Belum ada mahasiswa" /></div>
    @else
        @php($editable = ! $course->isCompleted())
        <form method="POST" action="{{ route('grades.saveManual', $course) }}">
            @csrf
            <div class="card-body py-2 border-bottom d-flex align-items-center gap-2 flex-wrap">
                <input type="text" class="form-control form-control-sm" style="max-width:240px" placeholder="Cari mahasiswa…" data-table-search="#tbl-rekap">
                @if ($editable)
                    <span class="text-secondary small ms-auto"><i class="ti ti-pencil me-1"></i>Semua kolom bisa diisi (0–100), lalu Simpan. Kolom <span class="text-secondary">otomatis</span>: isi untuk <strong>override manual</strong>, kosongkan untuk kembali otomatis.</span>
                @endif
            </div>
            <div class="table-responsive">
                <table id="tbl-rekap" class="table table-vcenter card-table table-sortable">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            @foreach ($components as $c)
                                <th class="text-center">{{ $c->name }}
                                    <div class="small fw-normal">{{ $c->weight }}% ·
                                        @if (in_array($c->id, $autoComponentIds ?? []))
                                            <span class="text-secondary">otomatis</span>
                                        @else
                                            <span class="badge bg-blue-lt">manual</span>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                            <th class="text-center">Akhir</th><th class="text-center">Huruf</th><th class="text-center">SIAKAD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['student']->name }}<div class="small text-secondary">{{ $row['student']->nim_nip }}</div></td>
                                @foreach ($components as $c)
                                    @php($val = $row['components'][$c->id])
                                    @php($isAuto = in_array($c->id, $autoComponentIds ?? []))
                                    @php($isOverride = $row['overrides'][$c->id] ?? false)
                                    @if ($course->isCompleted())
                                        <td class="text-center">{{ is_null($val) ? '—' : \App\Support\Grades::num($val) }}</td>
                                    @elseif ($isAuto)
                                        {{-- Otomatis: kosong = pakai hitungan (placeholder), isi = override manual --}}
                                        <td class="text-center" style="min-width:92px">
                                            <input type="number" name="scores[{{ $c->id }}][{{ $row['student']->id }}]"
                                                   value="{{ $isOverride ? \App\Support\Grades::num($val) : '' }}"
                                                   min="0" max="100" step="0.01"
                                                   class="form-control form-control-sm text-center @if ($isOverride) fw-bold text-orange @endif"
                                                   placeholder="{{ is_null($val) ? 'auto' : \App\Support\Grades::num($val) }}"
                                                   title="Otomatis: {{ is_null($val) ? '—' : \App\Support\Grades::num($val) }}. Isi untuk override; kosongkan untuk kembali otomatis.">
                                        </td>
                                    @else
                                        <td class="text-center" style="min-width:84px">
                                            <input type="number" name="scores[{{ $c->id }}][{{ $row['student']->id }}]"
                                                   value="{{ is_null($val) ? '' : \App\Support\Grades::num($val) }}"
                                                   min="0" max="100" step="0.01" class="form-control form-control-sm text-center" placeholder="—">
                                        </td>
                                    @endif
                                @endforeach
                                <td class="text-center fw-bold">{{ $row['final'] }}</td>
                                <td class="text-center"><span class="badge bg-{{ \App\Support\Grades::color($row['letter']) }}-lt">{{ $row['letter'] }}</span></td>
                                @php($gradeSync = $gradeSyncs->get($row['student']->id))
                                <td class="text-center" style="min-width:130px">
                                    @if ($gradeSync)
                                        <span class="badge bg-{{ $gradeSync->statusColor() }}-lt">{{ $gradeSync->statusLabel() }}</span>
                                        @if ($gradeSync->letter_grade)
                                            <div class="small mt-1">{{ \App\Support\Grades::num($gradeSync->numeric_score) }} · {{ $gradeSync->letter_grade }}</div>
                                        @endif
                                        @if ($gradeSync->error_message)
                                            <div class="small text-red mt-1" title="{{ $gradeSync->error_message }}">{{ \Illuminate\Support\Str::limit($gradeSync->error_message, 55) }}</div>
                                        @elseif ($gradeSync->synced_at)
                                            <div class="small text-secondary mt-1">{{ $gradeSync->synced_at->format('d/m H:i') }}</div>
                                        @endif
                                    @else
                                        <span class="text-secondary small">Belum dikirim</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($editable)
                <div class="card-footer text-end">
                    <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan Nilai</button>
                </div>
            @endif
        </form>
    @endif
</div>

{{-- ===================== MODAL KOMPONEN NILAI ===================== --}}
<div class="modal modal-blur fade" id="modal-komponen" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Komponen Nilai</h5>
                <span class="badge bg-{{ $weightTotal === 100 ? 'green' : 'orange' }}-lt ms-2">Total {{ $weightTotal }}%</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                @if ($weightTotal !== 100 && ! $components->isEmpty())
                    <div class="px-3 pt-3"><div class="text-secondary small"><i class="ti ti-info-circle me-1"></i>Total bobot sebaiknya tepat 100%.</div></div>
                @endif
                <div class="list-group list-group-flush">
                    @forelse ($components as $c)
                        <div class="list-group-item" x-data="{ edit: false, type: '{{ $c->type }}' }">
                            <div class="d-flex align-items-center">
                                <div>
                                    <div class="fw-bold">{{ $c->name }} <span class="badge bg-blue-lt ms-1">{{ $c->weight }}%</span></div>
                                    <div class="small text-secondary">{{ \App\Models\GradeComponent::TYPES[$c->type] ?? ucfirst($c->type) }}@if ($c->isAttendance()) · <span class="text-teal">otomatis dari absensi</span>@endif{{ $c->description ? ' · '.$c->description : '' }}</div>
                                </div>
                                @unless ($course->isCompleted())
                                <div class="ms-auto btn-list">
                                    <button type="button" class="btn btn-sm btn-ghost-secondary" @click="edit = ! edit" title="Ubah"><i class="ti ti-pencil"></i></button>
                                    <form method="POST" action="{{ route('grade-components.destroy', $c) }}" data-confirm="Hapus komponen ini?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger" title="Hapus"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                                @endunless
                            </div>
                            @unless ($course->isCompleted())
                            <form method="POST" action="{{ route('grade-components.update', $c) }}" class="mt-2" x-show="edit" x-cloak>
                                @csrf @method('PUT')
                                <div class="row g-2">
                                    <div class="col-7"><label class="form-label small mb-1 required">Tipe</label>
                                        <select name="type" class="form-select form-select-sm" x-model="type">
                                            @foreach (\App\Models\GradeComponent::TYPES as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select></div>
                                    <div class="col-5"><label class="form-label small mb-1 required">Bobot %</label>
                                        <input type="number" name="weight" class="form-control form-control-sm" min="1" max="100" value="{{ $c->weight }}" required></div>
                                    <div class="col-12" x-show="type === 'lainnya'" x-cloak><label class="form-label small mb-1 required">Nama</label>
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $c->type === 'lainnya' ? $c->name : '' }}" placeholder="Nama komponen" :required="type === 'lainnya'"></div>
                                    <div class="col-12 text-end"><button class="btn btn-sm btn-primary"><i class="ti ti-device-floppy me-1"></i>Simpan</button></div>
                                </div>
                            </form>
                            @endunless
                        </div>
                    @empty
                        <div class="list-group-item text-secondary small">Belum ada komponen. Tambahkan agar nilai akhir terhitung.</div>
                    @endforelse
                </div>
            </div>
            @unless ($course->isCompleted())
            <div class="modal-footer">
                <form class="w-100" method="POST" action="{{ route('grade-components.store', $course) }}" x-data="{ type: 'tugas' }">
                    @csrf
                    <div class="text-secondary small mb-2">Tambah komponen baru</div>
                    <div class="row g-2">
                        <div class="col-7"><label class="form-label required">Tipe</label>
                            <select name="type" class="form-select" x-model="type">
                                @foreach (\App\Models\GradeComponent::TYPES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select></div>
                        <div class="col-5"><label class="form-label required">Bobot %</label>
                            <input type="number" name="weight" class="form-control" min="1" max="100" required></div>
                        <div class="col-12" x-show="type === 'lainnya'" x-cloak>
                            <label class="form-label required">Nama</label>
                            <input type="text" name="name" class="form-control" placeholder="Nama komponen" :required="type === 'lainnya'">
                        </div>
                        <div class="col-12"><button class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>Tambah Komponen</button></div>
                    </div>
                </form>
            </div>
            @endunless
        </div>
    </div>
</div>
@endsection
