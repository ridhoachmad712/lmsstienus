@extends('layouts.app')

@section('title', 'Pengumuman')
@section('page-pretitle', 'Kampus')
@section('page-title', 'Pengumuman')

@section('content')
<div class="row row-cards">
    <div class="{{ $canManage ? 'col-lg-8' : 'col-12' }}">
        @forelse ($announcements as $a)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <span class="avatar bg-azure-lt me-3"><i class="ti ti-speakerphone"></i></span>
                        <div class="flex-fill">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <h3 class="card-title mb-0">{{ $a->title }}</h3>
                                @if ($a->pinned)<span class="badge bg-yellow-lt"><i class="ti ti-pin me-1"></i>Disematkan</span>@endif
                                <span class="badge bg-{{ $a->prodi ? 'blue' : 'green' }}-lt">{{ $a->audienceLabel() }}</span>
                            </div>
                            <div class="text-secondary small mb-2">{{ $a->creator->name ?? 'Sistem' }} · {{ $a->created_at->translatedFormat('d M Y H:i') }}</div>
                            <div style="white-space:pre-line">{{ $a->body }}</div>
                        </div>
                        @if ($canManage && (auth()->user()->isAdmin() || $a->prodi_id === auth()->user()->prodi_id))
                            <form method="POST" action="{{ route('pengumuman.destroy', $a) }}" class="ms-2" onsubmit="return confirm('Hapus pengumuman ini?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body">
                <x-empty-state icon="ti-speakerphone" title="Belum ada pengumuman" description="Pengumuman kampus/prodi akan tampil di sini." />
            </div></div>
        @endforelse

        {{ $announcements->links() }}
    </div>

    @if ($canManage)
        <div class="col-lg-4">
            <form class="card" method="POST" action="{{ route('pengumuman.store') }}">
                @csrf
                <div class="card-header"><h3 class="card-title">Terbitkan Pengumuman</h3></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label required">Judul</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2"><label class="form-label required">Isi</label>
                        <textarea name="body" class="form-control @error('body') is-invalid @enderror" rows="5" required>{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if (auth()->user()->isAdmin())
                        <div class="mb-2"><label class="form-label">Sasaran</label>
                            <select name="prodi_id" class="form-select">
                                <option value="">Seluruh Kampus</option>
                                @foreach ($prodis as $p)
                                    <option value="{{ $p->id }}" @selected(old('prodi_id') == $p->id)>Prodi {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="form-hint mb-2">Pengumuman akan tampil untuk mahasiswa & dosen prodi Anda.</div>
                    @endif
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="pinned" value="1" @checked(old('pinned'))>
                        <span class="form-check-label">Sematkan di atas</span>
                    </label>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary"><i class="ti ti-send me-1"></i>Terbitkan</button></div>
            </form>
        </div>
    @endif
</div>
@endsection
