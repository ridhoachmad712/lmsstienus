<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?int $prodiId = null): User
    {
        return User::factory()->create(['role' => $role, 'prodi_id' => $prodiId]);
    }

    private function course(User $owner, ?int $prodiId = null): Course
    {
        return Course::create([
            'user_id' => $owner->id,
            'prodi_id' => $prodiId,
            'name' => 'Kelas Uji',
            'code' => 'UJI'.$owner->id,
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
    }

    public function test_halaman_depan_publik_menautkan_dua_aplikasi(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Satu halaman untuk layanan')
            ->assertSee('href="'.url('/siakad').'"', false)
            ->assertSee('href="'.route('portal.lms').'"', false);
    }

    public function test_lms_memiliki_login_dan_prefix_mandiri(): void
    {
        $this->get('/lms')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke LMS');

        $this->assertSame('/lms/login', route('login', absolute: false));
        $this->assertSame('/lms', route('portal.lms', absolute: false));
        $this->assertStringStartsWith('/lms/', route('courses.index', absolute: false));
        $this->assertSame('/lms/admin', route('admin.dashboard', absolute: false));
    }

    public function test_laravel_tidak_menyediakan_integrasi_siakad(): void
    {
        $this->assertNull(config('database.connections.siakad'));
        $this->assertNull(config('services.legacy_siakad'));
        $this->assertFalse(Route::has('portal.siakad'));
        $this->assertFalse(Route::has('krs.index'));
        $this->assertFalse(Route::has('transkrip.mine'));
        $this->assertFalse(Route::has('grades.sync.siakad'));
        $this->assertFalse(Schema::hasTable('siakad_grade_syncs'));
        $this->assertFalse(Schema::hasColumn('courses', 'siakad_schedule_id'));
    }

    public function test_login_lms_menggunakan_akun_lms_dan_masuk_ke_lms(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MAHASISWA,
            'password' => 'password',
        ]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('portal.lms'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_lms_kembali_ke_halaman_depan(): void
    {
        $user = $this->user(User::ROLE_DOSEN);

        $this->actingAs($user)->post(route('logout'))
            ->assertRedirect(route('portal.index'));
        $this->assertGuest();
    }

    public function test_admin_bisa_mengakses_area_pengelolaan_lms(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pengawasan Kelas')
            ->assertDontSee('Pengisian KRS');
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.semesters.index'))
            ->assertOk()
            ->assertSee('Periode Akademik')
            ->assertDontSee('Evaluasi Dosen (EDOM)');
    }

    public function test_dosen_dan_mahasiswa_tidak_bisa_mengakses_area_admin(): void
    {
        $this->actingAs($this->user(User::ROLE_DOSEN))
            ->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($this->user(User::ROLE_MAHASISWA))
            ->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_kalender_lms_hanya_menampilkan_agenda_pembelajaran(): void
    {
        $student = $this->user(User::ROLE_MAHASISWA);

        $this->actingAs($student)->get(route('calendar'))
            ->assertOk()
            ->assertSee('Pertemuan bulan ini')
            ->assertSee('Deadline bulan ini')
            ->assertDontSee('Kalender akademik');
    }

    public function test_dosen_bisa_membuat_dan_memiliki_kelas_lms(): void
    {
        $dosen = $this->user(User::ROLE_DOSEN);

        $this->actingAs($dosen)->post(route('courses.store'), [
            'name' => 'Manajemen Strategis',
            'code' => 'MNJ401',
            'semester' => 'Ganjil',
            'year' => 2026,
            'default_meeting_type' => 'tatap_muka',
        ])->assertRedirect();

        $course = Course::where('code', 'MNJ401')->firstOrFail();
        $this->assertTrue($course->lecturer->is($dosen));
        $this->assertNotEmpty($course->join_code);
    }

    public function test_mahasiswa_lintas_prodi_bisa_gabung_dengan_kode_kelas(): void
    {
        $akuntansi = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $manajemen = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $dosen = $this->user(User::ROLE_DOSEN, $akuntansi->id);
        $mahasiswa = $this->user(User::ROLE_MAHASISWA, $manajemen->id);
        $course = $this->course($dosen, $akuntansi->id);

        $this->actingAs($mahasiswa)->post(route('enrollments.join'), [
            'join_code' => $course->join_code,
        ])->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('enrollments', [
            'course_id' => $course->id,
            'user_id' => $mahasiswa->id,
            'status' => Enrollment::STATUS_APPROVED,
        ]);
    }

    public function test_bookmark_lms_lama_dialihkan_ke_prefix_lms(): void
    {
        $student = $this->user(User::ROLE_MAHASISWA);

        $this->actingAs($student)->get('/courses')
            ->assertMovedPermanently()
            ->assertRedirect('/lms/courses');
        $this->get('/login')->assertRedirect('/lms/login');
    }
}
