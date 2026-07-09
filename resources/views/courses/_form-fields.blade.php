<div class="row">
    @if (! empty($dosenOptions))
        <div class="col-12 mb-3">
            <label class="form-label required">Dosen Pengampu</label>
            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                <option value="">— pilih dosen —</option>
                @foreach ($dosenOptions as $d)
                    <option value="{{ $d->id }}" @selected(old('user_id', $course->user_id ?? '') == $d->id)>{{ $d->name }}@if ($d->prodi) — {{ $d->prodi->code }}@endif</option>
                @endforeach
            </select>
            <small class="form-hint">Kelas mengikuti program studi dosen yang dipilih.</small>
            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif
    @if (! empty($mataKuliahs) && $mataKuliahs->isNotEmpty())
        <div class="col-12 mb-3">
            <label class="form-label">Mata Kuliah (katalog)</label>
            <select name="mata_kuliah_id" class="form-select @error('mata_kuliah_id') is-invalid @enderror">
                <option value="">— Tidak ditautkan —</option>
                @foreach ($mataKuliahs as $mk)
                    <option value="{{ $mk->id }}" @selected(old('mata_kuliah_id', $course->mata_kuliah_id ?? '') == $mk->id)>{{ $mk->code }} — {{ $mk->name }} ({{ $mk->sks }} SKS)</option>
                @endforeach
            </select>
            <small class="form-hint">Opsional — tautkan ke katalog agar kelas ini terhitung sebagai kelas paralel dari mata kuliah tersebut.</small>
            @error('mata_kuliah_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif
    <div class="col-md-8 mb-3">
        <label class="form-label required">Nama Mata Kuliah</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $course->name ?? '') }}" placeholder="Manajemen Keuangan" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label required">Kode MK</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $course->code ?? '') }}" placeholder="MKU101" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Nama Kelas</label>
        <input type="text" name="class_name" class="form-control @error('class_name') is-invalid @enderror"
               value="{{ old('class_name', $course->class_name ?? '') }}" placeholder="Kelas A / Reguler Pagi">
        <small class="form-hint">Opsional — untuk membedakan beberapa kelas pada mata kuliah yang sama.</small>
        @error('class_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label required">Semester</label>
        <select name="semester" class="form-select @error('semester') is-invalid @enderror" required>
            @foreach (['Ganjil', 'Genap', 'Antara'] as $sem)
                <option value="{{ $sem }}" @selected(old('semester', $course->semester ?? \App\Models\Setting::get('semester', 'Ganjil')) === $sem)>{{ $sem }}</option>
            @endforeach
        </select>
        @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label required">Tahun Ajaran</label>
        <input type="number" name="year" class="form-control @error('year') is-invalid @enderror"
               value="{{ old('year', $course->year ?? \App\Models\Setting::get('academic_year', date('Y'))) }}" min="2000" max="2100" required>
        @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Kuota</label>
        <input type="number" name="quota" class="form-control @error('quota') is-invalid @enderror"
               value="{{ old('quota', $course->quota ?? '') }}" min="1" placeholder="Tanpa batas">
        <small class="form-hint">Kosongkan = tanpa batas (untuk KRS).</small>
        @error('quota')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 mb-3">
        <label class="form-label required">Jenis Pertemuan Default</label>
        <select name="default_meeting_type" class="form-select @error('default_meeting_type') is-invalid @enderror">
            @php($dmt = old('default_meeting_type', $course->default_meeting_type ?? 'tatap_muka'))
            <option value="tatap_muka" @selected($dmt === 'tatap_muka')>Tatap Muka (jadwal, lokasi, presensi)</option>
            <option value="mandiri" @selected($dmt === 'mandiri')>Mandiri / Full LMS (swa-presensi)</option>
        </select>
        <small class="form-hint">Dipakai otomatis saat menambah pertemuan baru. Tiap pertemuan tetap bisa diubah sendiri.</small>
        @error('default_meeting_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<hr class="my-4">
<h3 class="mb-1">RPS — Rencana Pembelajaran Semester</h3>
<p class="text-secondary small mb-3">Opsional — tersimpan langsung ke RPS mata kuliah. Bisa juga diisi atau diubah nanti lewat tab <strong>RPS</strong>.</p>

@php($syl = $course->syllabus ?? null)
<x-numbered-list name="syllabus[cpl]" label="CPL — Capaian Pembelajaran Lulusan"
    :value="old('syllabus.cpl', $syl->cpl ?? '')" placeholder="Satu CPL per baris" />
<x-numbered-list name="syllabus[cpmk]" label="CPMK — Capaian Pembelajaran Mata Kuliah"
    :value="old('syllabus.cpmk', $syl->cpmk ?? '')" placeholder="Satu CPMK per baris" />
<x-numbered-list name="syllabus[sub_cpmk]" label="Sub-CPMK"
    :value="old('syllabus.sub_cpmk', $syl->sub_cpmk ?? '')" placeholder="Satu Sub-CPMK per baris" />
<div class="mb-3">
    <label class="form-label">Deskripsi Mata Kuliah</label>
    <textarea name="syllabus[description]" class="form-control" rows="3" placeholder="Gambaran umum mata kuliah">{{ old('syllabus.description', $syl->description ?? '') }}</textarea>
</div>
<x-numbered-list name="syllabus[references]" label="Referensi / Pustaka"
    :value="old('syllabus.references', $syl->references ?? '')" placeholder="Satu referensi per baris (buku, jurnal, dll.)" />
