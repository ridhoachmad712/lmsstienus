<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProdiController extends Controller
{
    public function index(): View
    {
        $prodis = Prodi::withCount(['users', 'courses'])->orderBy('name')->get();

        return view('admin.master.prodi', compact('prodis'));
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
        ]);

        $prodi->update($data);

        return back()->with('status', 'Prodi diperbarui.');
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
}
