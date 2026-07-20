<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Kurikulum;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KurikulumController extends Controller
{
    use ImportsCsv;

    private function authorizeKurikulum(Request $request, Kurikulum $k): void
    {
        if ($request->user()->isKaprodi()) {
            abort_unless($k->prodi_id === $request->user()->prodi_id, 403);
        }
    }

    private function targetProdiId(Request $request): ?int
    {
        return $request->user()->isKaprodi()
            ? $request->user()->prodi_id
            : ($request->integer('prodi_id') ?: null);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodi,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function index(Request $request): View
    {
        $items = Kurikulum::query()
            ->when($request->user()->isKaprodi(), fn ($q) => $q->where('prodi_id', $request->user()->prodi_id))
            ->with('prodi')
            ->withCount('mataKuliah')
            ->orderByDesc('year')
            ->paginate(20);

        return view('admin.kurikulum.index', ['items' => $items]);
    }

    public function create(): View
    {
        return view('admin.kurikulum.form', ['k' => new Kurikulum(), 'prodis' => Prodi::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['prodi_id'] = $this->targetProdiId($request);
        $data['is_active'] = $request->boolean('is_active');

        $k = Kurikulum::create($data);
        $this->syncActive($k);

        return redirect()->route('admin.kurikulum.index')->with('status', 'Kurikulum ditambahkan.');
    }

    public function edit(Request $request, Kurikulum $kurikulum): View
    {
        $this->authorizeKurikulum($request, $kurikulum);

        return view('admin.kurikulum.form', ['k' => $kurikulum, 'prodis' => Prodi::orderBy('name')->get()]);
    }

    public function update(Request $request, Kurikulum $kurikulum): RedirectResponse
    {
        $this->authorizeKurikulum($request, $kurikulum);

        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');
        if ($request->user()->isKaprodi()) {
            $data['prodi_id'] = $request->user()->prodi_id;
        }

        $kurikulum->update($data);
        $this->syncActive($kurikulum);

        return redirect()->route('admin.kurikulum.index')->with('status', 'Kurikulum diperbarui.');
    }

    public function destroy(Request $request, Kurikulum $kurikulum): RedirectResponse
    {
        $this->authorizeKurikulum($request, $kurikulum);

        if ($kurikulum->mataKuliah()->exists()) {
            return back()->with('error', 'Tidak bisa dihapus — masih ada mata kuliah pada kurikulum ini.');
        }

        $kurikulum->delete();

        return back()->with('status', 'Kurikulum dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = Kurikulum::whereIn('id', $data['ids'])->withCount('mataKuliah')
            ->when($request->user()->isKaprodi(), fn ($q) => $q->where('prodi_id', $request->user()->prodi_id));

        $deleted = 0;
        $skipped = 0;
        foreach ($query->get() as $k) {
            if ($k->mata_kuliah_count > 0) {
                $skipped++;

                continue;
            }
            $k->delete();
            $deleted++;
        }

        return back()->with('status', "$deleted kurikulum dihapus."
            .($skipped > 0 ? " $skipped dilewati karena masih memuat mata kuliah." : ''));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        // Peta kode prodi → id (untuk kolom prodi pada CSV; kaprodi dipaksa prodinya).
        $prodiByCode = Prodi::pluck('id', 'code');
        $forcedProdi = $request->user()->isKaprodi() ? $request->user()->prodi_id : null;

        $created = 0;
        $skipped = 0;
        foreach ($this->readCsvRows($request->file('file'), ['nama', 'name', 'tahun']) as $cols) {
            $name = $cols[0] ?? '';
            $year = (int) ($cols[1] ?? 0);
            $prodiCode = $cols[2] ?? '';
            $prodiId = $forcedProdi ?: ($prodiCode !== '' ? ($prodiByCode[$prodiCode] ?? null) : null);
            $active = in_array(strtolower(trim($cols[3] ?? '')), ['1', 'aktif', 'ya', 'true'], true);

            if ($name === '' || $year < 2000 || $year > 2100) {
                $skipped++;

                continue;
            }

            $k = Kurikulum::create([
                'name' => $name, 'year' => $year, 'prodi_id' => $prodiId, 'is_active' => $active,
            ]);
            $this->syncActive($k);
            $created++;
        }

        return back()->with('status', "Import selesai: $created kurikulum dibuat, $skipped dilewati.");
    }

    /** Satu prodi hanya boleh punya satu kurikulum aktif. */
    private function syncActive(Kurikulum $k): void
    {
        if ($k->is_active && $k->prodi_id) {
            Kurikulum::where('prodi_id', $k->prodi_id)
                ->where('id', '!=', $k->id)
                ->update(['is_active' => false]);
        }
    }
}
