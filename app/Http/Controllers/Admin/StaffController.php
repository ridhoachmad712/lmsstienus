<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
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
    use ImportsCsv;

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
            ->with('prodi', 'headedProdi')
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
        $data['must_change_password'] = true;

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
        $staff->update(['password' => $new, 'must_change_password' => true]); // di-hash oleh cast

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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = User::whereIn('id', $data['ids'])
            ->whereIn('role', self::ROLES)
            ->withCount('teachingCourses');

        $deleted = 0;
        $skipped = 0;
        foreach ($query->get() as $staff) {
            if ($staff->teaching_courses_count > 0) {
                $skipped++;

                continue;
            }
            $staff->delete();
            $deleted++;
        }

        return back()->with('status', "$deleted akun dihapus."
            .($skipped > 0 ? " $skipped dilewati karena masih mengampu kelas." : ''));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $prodiByCode = Prodi::pluck('id', 'code');

        $created = 0;
        $skipped = 0;
        foreach ($this->readCsvRows($request->file('file'), ['nama', 'name', 'email']) as $cols) {
            $name = $cols[0] ?? '';
            $email = strtolower($cols[1] ?? '');
            $role = in_array(strtolower(trim($cols[2] ?? '')), [User::ROLE_KAPRODI], true) ? User::ROLE_KAPRODI : User::ROLE_DOSEN;
            $prodiId = ($cols[3] ?? '') !== '' ? ($prodiByCode[$cols[3]] ?? null) : null;
            $nip = $cols[4] ?? '';
            $nidn = $cols[5] ?? '';

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $prodiId || User::where('email', $email)->exists()) {
                $skipped++;

                continue;
            }

            User::create([
                'name' => $name !== '' ? $name : $email,
                'email' => $email,
                'role' => $role,
                'prodi_id' => $prodiId,
                'nim_nip' => $nip !== '' ? $nip : null,
                'nidn' => $nidn !== '' ? $nidn : null,
                'password' => Hash::make($nip !== '' ? $nip : 'password'),
                'must_change_password' => true,
            ]);
            $created++;
        }

        return back()->with('status', "Import selesai: $created akun dibuat, $skipped dilewati (email duplikat / prodi tidak dikenal).");
    }

    private function validated(Request $request, ?User $staff = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff?->id)],
            'role' => ['required', Rule::in(self::ROLES)],
            'prodi_id' => ['required', 'integer', 'exists:prodi,id'],
            'nim_nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nim_nip')->ignore($staff?->id)],
            'nidn' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'in:L,P'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
        ];

        if (! $staff) {
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        return $request->validate($rules);
    }
}
