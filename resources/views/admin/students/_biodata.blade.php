{{-- Biodata mahasiswa. Butuh variabel $u (User|null). --}}
<hr class="my-3">
<div class="text-secondary small mb-2">Biodata</div>
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Jenis Kelamin</label>
        <select name="gender" class="form-select">
            <option value="">—</option>
            <option value="L" @selected(old('gender', $u->gender ?? '') === 'L')>Laki-laki</option>
            <option value="P" @selected(old('gender', $u->gender ?? '') === 'P')>Perempuan</option>
        </select>
    </div>
    <div class="col-md-4 mb-3"><label class="form-label">Angkatan</label>
        <input type="number" name="entry_year" class="form-control" value="{{ old('entry_year', $u->entry_year ?? '') }}" min="2000" max="2100" placeholder="mis. 2023">
    </div>
    <div class="col-md-4 mb-3"><label class="form-label">Status</label>
        <select name="student_status" class="form-select">
            @foreach (\App\Models\User::STUDENT_STATUSES as $st)
                <option value="{{ $st }}" @selected(old('student_status', $u->student_status ?? 'aktif') === $st)>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3"><label class="form-label">Tempat Lahir</label>
        <input type="text" name="birth_place" class="form-control" value="{{ old('birth_place', $u->birth_place ?? '') }}">
    </div>
    <div class="col-md-6 mb-3"><label class="form-label">Tanggal Lahir</label>
        <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', isset($u) && $u?->birth_date ? $u->birth_date->format('Y-m-d') : '') }}">
    </div>
    @isset($kurikulums)
        <div class="col-md-6 mb-3"><label class="form-label">Kurikulum</label>
            <select name="kurikulum_id" class="form-select">
                <option value="">— Tidak ditentukan —</option>
                @foreach ($kurikulums as $kur)
                    <option value="{{ $kur->id }}" @selected(old('kurikulum_id', $u->kurikulum_id ?? '') == $kur->id)>{{ $kur->name }} ({{ $kur->year }})</option>
                @endforeach
            </select>
        </div>
    @endisset
    @isset($advisors)
        <div class="col-md-6 mb-3"><label class="form-label">Dosen Pembimbing Akademik</label>
            <select name="advisor_id" class="form-select">
                <option value="">— Tidak ditentukan —</option>
                @foreach ($advisors as $adv)
                    <option value="{{ $adv->id }}" @selected(old('advisor_id', $u->advisor_id ?? '') == $adv->id)>{{ $adv->name }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="col-12 mb-3"><label class="form-label">Alamat</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address', $u->address ?? '') }}</textarea>
    </div>
</div>
