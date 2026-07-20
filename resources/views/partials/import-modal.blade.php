{{--
    Modal import CSV generik untuk Data Master.
    Parameter:
      - $importRoute : URL route import (POST, multipart, field "file")
      - $title       : judul modal
      - $columns     : string kolom, mis. "kode, nama"
      - $note        : (opsional) catatan tambahan
      - $modalId     : (opsional) id modal, default "modal-import"
--}}
@php($modalId = $modalId ?? 'modal-import')
<div class="modal modal-blur fade" id="{{ $modalId }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ $importRoute }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ $title }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label required">Berkas CSV</label><input type="file" name="file" class="form-control" accept=".csv,.txt" required></div>
                <div class="alert alert-info mb-0">
                    <strong>Format kolom:</strong> <code>{{ $columns }}</code>.
                    Baris pertama boleh berupa judul kolom (akan dilewati otomatis).
                    Pemisah koma <code>,</code> atau titik-koma <code>;</code> keduanya didukung.
                    @isset($note)<div class="mt-1 small">{{ $note }}</div>@endisset
                    @isset($templateRoute)<div class="mt-2"><a href="{{ $templateRoute }}" class="btn btn-sm btn-outline-info"><i class="ti ti-download me-1"></i>Unduh contoh CSV</a></div>@endisset
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="ti ti-file-import me-1"></i>Import</button></div>
        </form>
    </div>
</div>
