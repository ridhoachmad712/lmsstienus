<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProdiController extends Controller
{
    use ImportsCsv;

    public function index(): View
    {
        $prodis = Prodi::withCount(['users', 'courses'])->with('kaprodi')->orderBy('name')->get();

        // Kandidat kaprodi per prodi: dosen/kaprodi yang berada di prodi tsb.
        $candidates = User::whereIn('role', [User::ROLE_DOSEN, User::ROLE_KAPRODI])
            ->orderBy('name')
            ->get(['id', 'name', 'prodi_id', 'role'])
            ->groupBy('prodi_id');

        return view('admin.master.prodi', compact('prodis', 'candidates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:prodi,code'],
        ]);

        Prodi::create($data);
        Activity::log('create', "Menambah prodi {$data['name']} ({$data['code']})");

        return back()->with('status', "Prodi {$data['name']} ditambahkan.");
    }

    public function update(Request $request, Prodi $prodi): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:prodi,code,'.$prodi->id],
            'kaprodi_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $prodi->update(['name' => $data['name'], 'code' => $data['code']]);
        $this->assignKaprodi($prodi, $data['kaprodi_id'] ?? null);

        return back()->with('status', 'Prodi diperbarui.');
    }

    /**
     * Tetapkan (atau lepas) kaprodi prodi. Kaprodi ditempelkan ke akun dosen:
     * satu user hanya boleh mengepalai satu prodi, dan prodi_id-nya diselaraskan
     * ke prodi yang dikepalai agar cakupan (scope) kaprodi konsisten.
     */
    private function assignKaprodi(Prodi $prodi, ?int $userId): void
    {
        if ((int) $prodi->kaprodi_id === (int) $userId) {
            return; // tidak berubah
        }

        if ($userId) {
            $user = User::find($userId);
            // Hanya dosen/kaprodi yang boleh menjabat.
            if (! $user || ! in_array($user->role, [User::ROLE_DOSEN, User::ROLE_KAPRODI], true)) {
                return;
            }
            // Lepas jabatan user ini dari prodi lain (satu orang, satu prodi).
            Prodi::where('kaprodi_id', $user->id)->where('id', '!=', $prodi->id)->update(['kaprodi_id' => null]);
            // Selaraskan home prodi user ke prodi yang dikepalai.
            if ($user->prodi_id !== $prodi->id) {
                $user->update(['prodi_id' => $prodi->id]);
            }
            $prodi->update(['kaprodi_id' => $user->id]);
            Activity::log('update', "Menetapkan {$user->name} sebagai Kaprodi {$prodi->name}");
        } else {
            $prodi->update(['kaprodi_id' => null]);
            Activity::log('update', "Melepas jabatan Kaprodi {$prodi->name}");
        }
    }

    public function destroy(Prodi $prodi): RedirectResponse
    {
        $users = $prodi->users()->count();
        $courses = $prodi->courses()->count();
        if ($users > 0 || $courses > 0) {
            return back()->with('error', "Tidak bisa menghapus {$prodi->name} — masih dipakai {$users} pengguna & {$courses} kelas.");
        }

        $name = $prodi->name;
        $prodi->delete();
        Activity::log('delete', "Menghapus prodi {$name}");

        return back()->with('status', "Prodi {$name} dihapus.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = 0;
        $skipped = 0;
        foreach (Prodi::whereIn('id', $data['ids'])->withCount(['users', 'courses'])->get() as $prodi) {
            if ($prodi->users_count > 0 || $prodi->courses_count > 0) {
                $skipped++;

                continue;
            }
            $prodi->delete();
            $deleted++;
        }
        Activity::log('delete', "Hapus massal prodi: {$deleted} dihapus, {$skipped} dilewati");

        return back()->with('status', "$deleted prodi dihapus."
            .($skipped > 0 ? " $skipped dilewati karena masih dipakai pengguna/kelas." : ''));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $created = 0;
        $skipped = 0;
        foreach ($this->readCsvRows($request->file('file'), ['kode', 'code']) as $cols) {
            $code = $cols[0] ?? '';
            $name = $cols[1] ?? '';
            if ($code === '' || $name === '' || Prodi::where('code', $code)->exists()) {
                $skipped++;

                continue;
            }
            Prodi::create(['code' => $code, 'name' => $name]);
            $created++;
        }
        Activity::log('create', "Import prodi: {$created} dibuat, {$skipped} dilewati");

        return back()->with('status', "Import selesai: $created prodi dibuat, $skipped dilewati.");
    }
}
