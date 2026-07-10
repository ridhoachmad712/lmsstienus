<?php

namespace App\Http\Controllers;

use App\Models\CampusAnnouncement;
use App\Models\Notification;
use App\Models\Prodi;
use App\Models\User;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Papan pengumuman kampus/prodi: lihat (semua), kelola (admin & kaprodi). */
class CampusAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $announcements = CampusAnnouncement::forUser($user)
            ->with(['creator', 'prodi'])
            ->paginate(10);

        return view('pengumuman.index', [
            'announcements' => $announcements,
            'canManage' => $user->isStaff(),
            'prodis' => $user->isAdmin() ? Prodi::orderBy('name')->get() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStaff(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodi,id'],
            'pinned' => ['nullable', 'boolean'],
        ]);

        // Kaprodi hanya boleh menyasarkan ke prodinya; admin bebas (null = seluruh kampus).
        $prodiId = $user->isKaprodi() ? $user->prodi_id : ($data['prodi_id'] ?? null);

        $announcement = CampusAnnouncement::create([
            'created_by' => $user->id,
            'prodi_id' => $prodiId,
            'title' => $data['title'],
            'body' => $data['body'],
            'pinned' => (bool) ($data['pinned'] ?? false),
        ]);

        $this->notifyAudience($announcement);
        Activity::log('create', 'Menerbitkan pengumuman: '.$announcement->title);

        return back()->with('status', 'Pengumuman diterbitkan.');
    }

    public function destroy(Request $request, CampusAnnouncement $announcement): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStaff(), 403);
        // Kaprodi hanya boleh menghapus pengumuman prodinya sendiri.
        if ($user->isKaprodi()) {
            abort_unless($announcement->prodi_id === $user->prodi_id, 403);
        }

        $announcement->delete();

        return back()->with('status', 'Pengumuman dihapus.');
    }

    /** Kirim notifikasi in-app ke audiens (mahasiswa & dosen terkait). */
    private function notifyAudience(CampusAnnouncement $a): void
    {
        $ids = User::whereIn('role', [User::ROLE_MAHASISWA, User::ROLE_DOSEN])
            ->when($a->prodi_id, fn ($q) => $q->where('prodi_id', $a->prodi_id))
            ->pluck('id');

        $now = now();
        $rows = $ids->map(fn ($id) => [
            'user_id' => $id,
            'type' => 'pengumuman',
            'title' => 'Pengumuman: '.$a->title,
            'message' => $a->audienceLabel(),
            'link' => route('pengumuman.index'),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            Notification::insert($rows);
        }
    }
}
