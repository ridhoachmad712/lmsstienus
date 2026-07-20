<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MataKuliahController extends Controller
{
    use ImportsCsv;

    /** Kaprodi hanya melihat/menyentuh MK prodinya. */
    private function authorize(Request $request, MataKuliah $mk): void
    {
        if ($request->user()->isKaprodi()) {
            abort_unless($mk->prodi_id === $request->user()->prodi_id, 403);
        }
    }

    private function targetProdiId(Request $request): ?int
    {
        return $request->user()->isKaprodi()
            ? $request->user()->prodi_id
            : ($request->integer('prodi_id') ?: null);
    }

    private function rules(Request $request, ?MataKuliah $mk = null): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('mata_kuliah', 'code')->ignore($mk?->id)],
            'name' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1', 'max:10'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodi,id'],
            'kurikulum_id' => ['nullable', 'integer', 'exists:kurikulum,id'],
            'semester_no' => ['nullable', 'integer', 'min:1', 'max:14'],
            'jenis' => ['nullable', 'in:wajib,pilihan'],
            'prasyarat' => ['nullable', 'array'],
            'prasyarat.*' => ['integer', 'exists:mata_kuliah,id'],
        ];
    }

    /** MK yang boleh dipakai (untuk pilihan prasyarat) sesuai lingkup aktor. */
    private function options(Request $request, ?MataKuliah $mk = null)
    {
        return [
            'kurikulums' => Kurikulum::query()
                ->when($request->user()->isKaprodi(), fn ($q) => $q->where('prodi_id', $request->user()->prodi_id))
                ->orderByDesc('year')->get(),
            'allMk' => MataKuliah::query()
                ->when($request->user()->isKaprodi(), fn ($q) => $q->where('prodi_id', $request->user()->prodi_id))
                ->when($mk, fn ($q) => $q->where('id', '!=', $mk->id))
                ->orderBy('code')->get(),
            'prodis' => Prodi::orderBy('name')->get(),
        ];
    }

    public function index(Request $request): View
    {
        $kurikulumId = $request->integer('kurikulum') ?: null;

        $items = MataKuliah::query()
            ->when($request->user()->isKaprodi(), fn ($q) => $q->where('prodi_id', $request->user()->prodi_id))
            ->when($kurikulumId, fn ($q) => $q->where('kurikulum_id', $kurikulumId))
            ->with(['prodi', 'kurikulum'])
            ->withCount('courses')
            ->orderBy('semester_no')
            ->orderBy('code')
            ->paginate(30);

        $kurikulums = Kurikulum::query()
            ->when($request->user()->isKaprodi(), fn ($q) => $q->where('prodi_id', $request->user()->prodi_id))
            ->orderByDesc('year')->get();

        return view('admin.matakuliah.index', ['items' => $items, 'kurikulums' => $kurikulums, 'kurikulumId' => $kurikulumId]);
    }

    public function create(Request $request): View
    {
        return view('admin.matakuliah.form', array_merge(
            ['mk' => new MataKuliah(['jenis' => 'wajib']), 'selectedPrasyarat' => []],
            $this->options($request),
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));
        $data['prodi_id'] = $this->targetProdiId($request);

        $mk = MataKuliah::create($data);
        $mk->prasyarat()->sync($request->input('prasyarat', []));

        return redirect()->route('admin.matakuliah.index')->with('status', 'Mata kuliah ditambahkan.');
    }

    public function edit(Request $request, MataKuliah $matakuliah): View
    {
        $this->authorize($request, $matakuliah);

        return view('admin.matakuliah.form', array_merge(
            ['mk' => $matakuliah, 'selectedPrasyarat' => $matakuliah->prasyarat()->pluck('mata_kuliah.id')->all()],
            $this->options($request, $matakuliah),
        ));
    }

    public function update(Request $request, MataKuliah $matakuliah): RedirectResponse
    {
        $this->authorize($request, $matakuliah);

        $data = $request->validate($this->rules($request, $matakuliah));
        if ($request->user()->isKaprodi()) {
            $data['prodi_id'] = $request->user()->prodi_id;
        }

        $matakuliah->update($data);
        $matakuliah->prasyarat()->sync($request->input('prasyarat', []));

        return redirect()->route('admin.matakuliah.index')->with('status', 'Mata kuliah diperbarui.');
    }

    public function destroy(Request $request, MataKuliah $matakuliah): RedirectResponse
    {
        $this->authorize($request, $matakuliah);

        $courses = $matakuliah->courses()->count();
        if ($courses > 0) {
            return back()->with('error', "Tidak bisa menghapus {$matakuliah->code} — masih ditautkan ke {$courses} kelas. Hapus/pindahkan kelasnya dulu, atau lepas tautan mata kuliah pada kelas tersebut.");
        }

        $code = $matakuliah->code;
        $matakuliah->delete();

        return back()->with('status', "Mata kuliah {$code} dihapus.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = MataKuliah::whereIn('id', $data['ids'])->withCount('courses')
            ->when($request->user()->isKaprodi(), fn ($q) => $q->where('prodi_id', $request->user()->prodi_id));

        $deleted = 0;
        $skipped = 0;
        foreach ($query->get() as $mk) {
            if ($mk->courses_count > 0) {
                $skipped++;

                continue;
            }
            $mk->delete();
            $deleted++;
        }

        return back()->with('status', "$deleted mata kuliah dihapus."
            .($skipped > 0 ? " $skipped dilewati karena masih ditautkan ke kelas." : ''));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $prodiByCode = Prodi::pluck('id', 'code');
        $forcedProdi = $request->user()->isKaprodi() ? $request->user()->prodi_id : null;

        $created = 0;
        $skipped = 0;
        foreach ($this->readCsvRows($request->file('file'), ['kode', 'code']) as $cols) {
            $code = $cols[0] ?? '';
            $name = $cols[1] ?? '';
            $sks = (int) ($cols[2] ?? 0);
            $semesterNo = ($cols[3] ?? '') !== '' ? (int) $cols[3] : null;
            $jenis = in_array(strtolower(trim($cols[4] ?? '')), ['pilihan'], true) ? 'pilihan' : 'wajib';
            $prodiCode = $cols[5] ?? '';
            $prodiId = $forcedProdi ?: ($prodiCode !== '' ? ($prodiByCode[$prodiCode] ?? null) : null);

            if ($code === '' || $name === '' || $sks < 1 || $sks > 10 || MataKuliah::where('code', $code)->exists()) {
                $skipped++;

                continue;
            }

            MataKuliah::create([
                'code' => $code, 'name' => $name, 'sks' => $sks,
                'semester_no' => $semesterNo, 'jenis' => $jenis, 'prodi_id' => $prodiId,
            ]);
            $created++;
        }

        return back()->with('status', "Import selesai: $created mata kuliah dibuat, $skipped dilewati.");
    }
}
