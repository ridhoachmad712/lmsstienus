<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kurikulum;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KurikulumController extends Controller
{
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
