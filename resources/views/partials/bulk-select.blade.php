{{--
    Toolbar aksi massal (hapus terpilih) untuk tabel Data Master.
    Kebutuhan di tabel pemakai:
      - header: <th class="w-1"><input type="checkbox" id="sel-all" class="form-check-input m-0"></th>
      - tiap baris: <td><input type="checkbox" class="form-check-input m-0 row-select" value="{{ $id }}"></td>
    Parameter:
      - $deleteRoute : URL route bulk destroy (POST, kirim ids[])
      - $confirm     : (opsional) pesan konfirmasi
      - $noun        : (opsional) kata benda entitas untuk teks default
--}}
@php($noun = $noun ?? 'data')
<div id="bulk-bar" class="card-body border-bottom bg-primary-lt d-none py-2">
    <div class="d-flex align-items-center">
        <span class="me-3"><strong id="bulk-count">0</strong> dipilih</span>
        <div class="btn-list ms-auto">
            <button type="button" id="bulk-delete" class="btn btn-sm btn-danger"><i class="ti ti-trash me-1"></i>Hapus terpilih</button>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk aksi massal (ID disuntik via JS) --}}
<form id="bulk-form" method="POST" class="d-none">@csrf</form>

@push('scripts')
<script>
(function () {
    var selAll = document.getElementById('sel-all');
    var bar = document.getElementById('bulk-bar');
    var countEl = document.getElementById('bulk-count');
    var form = document.getElementById('bulk-form');
    if (!form) return;
    var boxes = function () { return Array.prototype.slice.call(document.querySelectorAll('.row-select')); };
    var checked = function () { return boxes().filter(function (b) { return b.checked; }); };

    function refresh() {
        var n = checked().length;
        countEl.textContent = n;
        bar.classList.toggle('d-none', n === 0);
        if (selAll) { selAll.checked = n > 0 && n === boxes().length; }
    }
    if (selAll) { selAll.addEventListener('change', function (e) { boxes().forEach(function (b) { b.checked = e.target.checked; }); refresh(); }); }
    boxes().forEach(function (b) { b.addEventListener('change', refresh); });

    var db = document.getElementById('bulk-delete');
    if (db) {
        db.addEventListener('click', function () {
            var ids = checked().map(function (b) { return b.value; });
            if (!ids.length) return;
            if (!window.confirm(@json($confirm ?? ('Hapus '.$noun.' terpilih? Item yang masih dipakai akan dilewati.')))) return;
            form.action = @json($deleteRoute);
            form.querySelectorAll('input[name="ids[]"]').forEach(function (n) { n.remove(); });
            ids.forEach(function (id) {
                var i = document.createElement('input');
                i.type = 'hidden'; i.name = 'ids[]'; i.value = id;
                form.appendChild(i);
            });
            form.submit();
        });
    }
})();
</script>
@endpush
