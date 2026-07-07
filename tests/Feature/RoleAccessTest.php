<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function course(User $owner): Course
    {
        return Course::create([
            'user_id' => $owner->id,
            'name' => 'Kelas Uji',
            'code' => 'UJI'.$owner->id,
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
    }

    public function test_admin_bisa_akses_area_admin(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.students.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
    }

    public function test_dosen_tidak_bisa_akses_area_admin(): void
    {
        $dosen = $this->user(User::ROLE_DOSEN);

        $this->actingAs($dosen)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($dosen)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($dosen)->get(route('admin.students.index'))->assertForbidden();
    }

    public function test_mahasiswa_tidak_bisa_akses_area_admin(): void
    {
        $mhs = $this->user(User::ROLE_MAHASISWA);

        $this->actingAs($mhs)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_kaprodi_lihat_dashboard_tapi_bukan_pengelolaan(): void
    {
        $kaprodi = $this->user(User::ROLE_KAPRODI);

        $this->actingAs($kaprodi)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($kaprodi)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_admin_bisa_lihat_kelas_dosen_mana_pun(): void
    {
        $dosen = $this->user(User::ROLE_DOSEN);
        $admin = $this->user(User::ROLE_ADMIN);
        $course = $this->course($dosen);

        // Gate::before memberi admin akses ke kelas milik dosen mana pun.
        $this->actingAs($admin)->get(route('courses.show', $course))->assertOk();
    }

    public function test_kaprodi_hanya_lihat_mahasiswa_prodinya(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kaprodiAk = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $ak->id, 'name' => 'Mhsakun Satu']);
        User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id, 'name' => 'Mhsmanaj Dua']);

        $res = $this->actingAs($kaprodiAk)->get(route('admin.students.index'));
        $res->assertOk();
        $res->assertSee('Mhsakun Satu');
        $res->assertDontSee('Mhsmanaj Dua');
    }

    public function test_kaprodi_tak_bisa_edit_mahasiswa_prodi_lain(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kaprodiAk = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        $mhsMn = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id]);

        $this->actingAs($kaprodiAk)->get(route('admin.students.edit', $mhsMn))->assertForbidden();
    }

    public function test_dosen_tidak_bisa_akses_kelas_dosen_lain(): void
    {
        $owner = $this->user(User::ROLE_DOSEN);
        $other = $this->user(User::ROLE_DOSEN);
        $course = $this->course($owner);

        $this->actingAs($owner)->get(route('courses.show', $course))->assertOk();
        $this->actingAs($other)->get(route('courses.show', $course))->assertForbidden();
    }
}
