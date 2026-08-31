@extends('layouts.app')

@section('title', 'Backup')
@section('page-pretitle', 'Admin')
@section('page-title', 'Backup Database')

@section('page-actions')
    <form method="POST" action="{{ route('admin.backups.run') }}">
        @csrf
        <button class="btn btn-primary"><i class="ti ti-database-export me-1"></i>Backup Sekarang</button>
    </form>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="alert alert-info">
            <i class="ti ti-info-circle me-1"></i>Backup LMS berjalan otomatis pukul 02.00 WITA, disimpan privat di <code>storage/app/backups</code>, dan dibersihkan sesuai masa retensi. Koneksi: <strong>{{ strtoupper($connection) }}</strong> ({{ $connection === 'mysql' ? '.sql' : '.sqlite' }}).
        </div>

        {{-- Unggah / impor berkas backup --}}
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-upload me-1"></i>Unggah Berkas Backup</h3></div>
            <form method="POST" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <label class="form-label">Berkas <span class="text-secondary">(.sql untuk MySQL, .sqlite untuk SQLite — maks 50 MB)</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".sql,.sqlite" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-auto">
                            <button class="btn"><i class="ti ti-upload me-1"></i>Unggah</button>
                        </div>
                    </div>
                    <div class="form-hint mt-2">Berkas yang diunggah hanya masuk ke daftar di bawah. Penerapan ke database dilakukan lewat tombol <strong>Pulihkan</strong>.</div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Daftar Backup ({{ $backups->count() }})</h3></div>
            @if ($backups->isEmpty())
                <div class="card-body"><x-empty-state icon="ti-database-off" title="Belum ada backup" description="Klik “Backup Sekarang” atau unggah berkas di atas." /></div>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Berkas</th><th>Ukuran</th><th>Dibuat</th><th class="w-1"></th></tr></thead>
                        <tbody>
                            @foreach ($backups as $b)
                                <tr>
                                    <td><i class="ti ti-database me-1 text-secondary"></i>{{ $b['name'] }}</td>
                                    <td class="text-secondary">{{ $b['size'] }}</td>
                                    <td class="text-secondary">{{ $b['date'] }}</td>
                                    <td>
                                        <div class="btn-list flex-nowrap justify-content-end">
                                            <a href="{{ route('admin.backups.download', $b['name']) }}" class="btn btn-sm" title="Unduh" data-bs-toggle="tooltip"><i class="ti ti-download"></i></a>
                                            <form method="POST" action="{{ route('admin.backups.restore', $b['name']) }}"
                                                  onsubmit="return confirm('PULIHKAN database dari {{ $b['name'] }}?\n\nSeluruh data saat ini akan DITIMPA. Snapshot pra-restore dibuat otomatis. Lanjutkan?');">
                                                @csrf
                                                <button class="btn btn-sm btn-warning"><i class="ti ti-database-import me-1"></i>Pulihkan</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.backups.destroy', $b['name']) }}"
                                                  onsubmit="return confirm('Hapus berkas {{ $b['name'] }}?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-danger" title="Hapus" data-bs-toggle="tooltip"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
