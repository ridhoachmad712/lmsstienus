<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MataKuliahController extends Controller
{
    /** Kaprodi hanya melihat/menyentuh MK prodinya. */
    private function authorize(Request $request, MataKuliah $mk): void
    {
        if ($request->user()->isKaprodi()) {
            abort_unless($mk->prodi_id === $request->user()->prodi_id, 403);
        }
    }

    /** Prodi tujuan: kaprodi → prodinya; admin → pilihan form. */
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
        ];
    }

    public function index(Request $request): View
    {
        $items = MataKuliah::query()
            ->when($request->user()->isKaprodi(), fn ($q) => $q->where('prodi_id', $request->user()->prodi_id))
            ->with('prodi')
            ->withCount('courses')
            ->orderBy('code')
            ->paginate(20);

        return view('admin.matakuliah.index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        return view('admin.matakuliah.form', [
            'mk' => new MataKuliah(),
            'prodis' => Prodi::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));
        $data['prodi_id'] = $this->targetProdiId($request);

        MataKuliah::create($data);

        return redirect()->route('admin.matakuliah.index')->with('status', 'Mata kuliah ditambahkan.');
    }

    public function edit(Request $request, MataKuliah $matakuliah): View
    {
        $this->authorize($request, $matakuliah);

        return view('admin.matakuliah.form', [
            'mk' => $matakuliah,
            'prodis' => Prodi::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, MataKuliah $matakuliah): RedirectResponse
    {
        $this->authorize($request, $matakuliah);

        $data = $request->validate($this->rules($request, $matakuliah));
        if ($request->user()->isKaprodi()) {
            $data['prodi_id'] = $request->user()->prodi_id;
        }

        $matakuliah->update($data);

        return redirect()->route('admin.matakuliah.index')->with('status', 'Mata kuliah diperbarui.');
    }

    public function destroy(Request $request, MataKuliah $matakuliah): RedirectResponse
    {
        $this->authorize($request, $matakuliah);

        if ($matakuliah->courses()->exists()) {
            return back()->with('error', 'Tidak bisa dihapus — masih ada kelas yang menautkan mata kuliah ini.');
        }

        $matakuliah->delete();

        return back()->with('status', 'Mata kuliah dihapus.');
    }
}
