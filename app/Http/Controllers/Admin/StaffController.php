<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Kelola akun staf: dosen & kaprodi (khusus admin). */
class StaffController extends Controller
{
    private const ROLES = [User::ROLE_DOSEN, User::ROLE_KAPRODI];

    private function ensureStaff(User $user): void
    {
        abort_unless(in_array($user->role, self::ROLES, true), 404);
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = in_array($request->query('role'), self::ROLES, true) ? $request->query('role') : null;
        $prodiId = $request->integer('prodi') ?: null;

        $staff = User::whereIn('role', self::ROLES)
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('name', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%")
                ->orWhere('nim_nip', 'like', "%$q%")))
            ->when($role, fn ($x) => $x->where('role', $role))
            ->when($prodiId, fn ($x) => $x->where('prodi_id', $prodiId))
            ->with('prodi')
            ->withCount('teachingCourses')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.staff.index', [
            'staff' => $staff, 'q' => $q, 'role' => $role, 'prodiId' => $prodiId,
            'prodis' => Prodi::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.form', [
            'user' => new User(['role' => User::ROLE_DOSEN]),
            'prodis' => Prodi::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['email'] = strtolower($data['email']);

        // Password (plain) di-hash otomatis oleh cast 'hashed' di model User.
        User::create($data);

        return redirect()->route('admin.staff.index')->with('status', 'Akun '.$data['role'].' berhasil dibuat.');
    }

    public function edit(User $staff): View
    {
        $this->ensureStaff($staff);

        return view('admin.staff.form', [
            'user' => $staff,
            'prodis' => Prodi::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $this->ensureStaff($staff);

        $data = $this->validated($request, $staff);
        $data['email'] = strtolower($data['email']);
        $staff->update($data);

        return redirect()->route('admin.staff.index')->with('status', 'Akun diperbarui.');
    }

    public function resetPassword(User $staff): RedirectResponse
    {
        $this->ensureStaff($staff);
        $new = $staff->nim_nip ?: 'password';
        $staff->update(['password' => $new]); // di-hash oleh cast

        return back()->with('status', "Kata sandi {$staff->name} direset menjadi: {$new}");
    }

    public function destroy(User $staff): RedirectResponse
    {
        $this->ensureStaff($staff);

        if ($staff->teachingCourses()->exists()) {
            return back()->with('error', 'Tidak bisa dihapus — akun ini masih mengampu kelas. Pindahkan/hapus kelasnya dulu.');
        }

        $staff->delete();

        return back()->with('status', 'Akun dihapus.');
    }

    private function validated(Request $request, ?User $staff = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff?->id)],
            'role' => ['required', Rule::in(self::ROLES)],
            'prodi_id' => ['required', 'integer', 'exists:prodi,id'],
            'nim_nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nim_nip')->ignore($staff?->id)],
        ];

        if (! $staff) {
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        return $request->validate($rules);
    }
}
