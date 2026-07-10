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

    // ===================== Backup / Restore =====================

    public function test_backup_area_hanya_admin(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $kaprodi = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);

        $this->actingAs($this->user(User::ROLE_ADMIN))->get(route('admin.backups.index'))
            ->assertOk()->assertSee('Unggah Berkas Backup');
        $this->actingAs($kaprodi)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($this->user(User::ROLE_DOSEN))->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->post(route('admin.backups.upload'))->assertForbidden();
    }

    public function test_upload_backup_tolak_ekstensi_salah(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->post(route('admin.backups.upload'), [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('dump.txt', 4),
        ])->assertRedirect()->assertSessionHas('error');
    }

    public function test_upload_backup_diterima_masuk_daftar(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $dir = storage_path('app/backups');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($dir);
        $before = glob($dir.'/upload_*.sql') ?: [];

        $this->actingAs($admin)->post(route('admin.backups.upload'), [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('dump.sql', 4),
        ])->assertRedirect()->assertSessionHas('status');

        $after = glob($dir.'/upload_*.sql') ?: [];
        $this->assertGreaterThan(count($before), count($after));

        foreach (array_diff($after, $before) as $f) {
            @unlink($f);
        }
    }

    public function test_restore_lintas_tipe_ditolak(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $dir = storage_path('app/backups');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($dir);
        // Koneksi test = sqlite; backup .sql (MySQL) harus ditolak, DB tak tersentuh.
        $name = 'test_dummy_'.uniqid().'.sql';
        file_put_contents($dir.'/'.$name, "-- dummy\nSELECT 1;\n");

        $this->actingAs($admin)->post(route('admin.backups.restore', $name))
            ->assertRedirect()->assertSessionHas('error');

        @unlink($dir.'/'.$name);
        // Data lama masih utuh (admin masih ada).
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_split_statements_hormati_kutip_dan_komentar(): void
    {
        $sql = "-- header komentar\n".
            "SET NAMES utf8mb4;\n".
            "INSERT INTO `t` (`a`) VALUES ('ada;titik-koma\ndan newline');\n".
            "INSERT INTO `t` (`a`) VALUES ('x');\n";

        $stmts = \App\Http\Controllers\Admin\BackupController::splitStatements($sql);

        $this->assertCount(3, $stmts); // komentar diabaikan; SET + 2 INSERT
        $this->assertStringContainsString('ada;titik-koma', $stmts[1]);
        $this->assertStringStartsWith('SET NAMES', $stmts[0]);
    }

    public function test_navbar_admin_active_state_benar(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        // Di beranda: item Dashboard aktif, tak ada dropdown yang aktif.
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('nav-item dropdown active', false);

        // Di halaman Mahasiswa: dropdown Akademik aktif.
        $this->actingAs($admin)->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('nav-item dropdown active', false);
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

    public function test_kaprodi_hanya_lihat_matakuliah_prodinya(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kaprodiAk = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        \App\Models\MataKuliah::create(['prodi_id' => $ak->id, 'code' => 'AK101', 'name' => 'Akuntansi Dasar', 'sks' => 3]);
        \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN101', 'name' => 'Pengantar Manajemen', 'sks' => 3]);

        $res = $this->actingAs($kaprodiAk)->get(route('admin.matakuliah.index'));
        $res->assertOk();
        $res->assertSee('AK101');
        $res->assertDontSee('MN101');
    }

    public function test_kaprodi_tak_bisa_edit_matakuliah_prodi_lain(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kaprodiAk = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        $mkMn = \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN101', 'name' => 'X', 'sks' => 3]);

        $this->actingAs($kaprodiAk)->get(route('admin.matakuliah.edit', $mkMn))->assertForbidden();
    }

    public function test_kelola_staf_hanya_admin(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $admin = $this->user(User::ROLE_ADMIN);
        $kaprodi = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        $dosen = $this->user(User::ROLE_DOSEN);

        $this->actingAs($admin)->get(route('admin.staff.index'))->assertOk();
        $this->actingAs($kaprodi)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($dosen)->get(route('admin.staff.index'))->assertForbidden();
    }

    public function test_admin_bisa_buat_dosen(): void
    {
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'Dosen Baru', 'email' => 'dosenbaru@x.test',
            'role' => 'dosen', 'prodi_id' => $mn->id, 'password' => 'rahasia123',
        ])->assertRedirect(route('admin.staff.index'));

        $u = User::where('email', 'dosenbaru@x.test')->first();
        $this->assertNotNull($u);
        $this->assertSame('dosen', $u->role);
        $this->assertSame($mn->id, $u->prodi_id);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('rahasia123', $u->password));
    }

    public function test_registrasi_mandiri_dihapus(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_pengawasan_kelas_scope_kaprodi(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kaprodiAk = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        $dosenAk = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $ak->id]);
        $dosenMn = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);
        Course::create(['user_id' => $dosenAk->id, 'prodi_id' => $ak->id, 'name' => 'Akuntansi Biaya', 'code' => 'AKB1', 'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE, 'join_code' => Course::generateJoinCode()]);
        Course::create(['user_id' => $dosenMn->id, 'prodi_id' => $mn->id, 'name' => 'Riset Pemasaran', 'code' => 'MNR1', 'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE, 'join_code' => Course::generateJoinCode()]);

        $res = $this->actingAs($kaprodiAk)->get(route('admin.courses.index'));
        $res->assertOk();
        $res->assertSee('Akuntansi Biaya');
        $res->assertDontSee('Riset Pemasaran');
    }

    public function test_pengawasan_kelas_bukan_untuk_dosen_mahasiswa(): void
    {
        $this->actingAs($this->user(User::ROLE_DOSEN))->get(route('admin.courses.index'))->assertForbidden();
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->get(route('admin.courses.index'))->assertForbidden();
    }

    public function test_admin_buat_mahasiswa_dengan_biodata(): void
    {
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Mhs Biodata', 'email' => 'mhsbio@x.test', 'nim_nip' => '2350001', 'prodi_id' => $mn->id,
            'gender' => 'L', 'entry_year' => 2023, 'student_status' => 'aktif',
            'birth_place' => 'Makassar', 'address' => 'Jl. Test',
        ])->assertRedirect(route('admin.students.index'));

        $u = User::where('email', 'mhsbio@x.test')->first();
        $this->assertSame(User::ROLE_MAHASISWA, $u->role);
        $this->assertSame($mn->id, $u->prodi_id);
        $this->assertSame(2023, $u->entry_year);
        $this->assertSame('aktif', $u->student_status);
        $this->assertSame('L', $u->gender);
    }

    public function test_kaprodi_kurikulum_scope(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kaprodiAk = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        \App\Models\Kurikulum::create(['prodi_id' => $ak->id, 'name' => 'Kurikulum Akun', 'year' => 2021, 'is_active' => true]);
        \App\Models\Kurikulum::create(['prodi_id' => $mn->id, 'name' => 'Kurikulum Manaj', 'year' => 2021, 'is_active' => true]);

        $res = $this->actingAs($kaprodiAk)->get(route('admin.kurikulum.index'));
        $res->assertOk();
        $res->assertSee('Kurikulum Akun');
        $res->assertDontSee('Kurikulum Manaj');
    }

    public function test_admin_buat_mk_dengan_kurikulum_dan_prasyarat(): void
    {
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kur = \App\Models\Kurikulum::create(['prodi_id' => $mn->id, 'name' => 'K2021', 'year' => 2021, 'is_active' => true]);
        $prereq = \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN101', 'name' => 'Dasar', 'sks' => 3]);
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->post(route('admin.matakuliah.store'), [
            'code' => 'MN201', 'name' => 'Lanjut', 'sks' => 3, 'prodi_id' => $mn->id, 'kurikulum_id' => $kur->id,
            'semester_no' => 3, 'jenis' => 'wajib', 'prasyarat' => [$prereq->id],
        ])->assertRedirect(route('admin.matakuliah.index'));

        $mk = \App\Models\MataKuliah::where('code', 'MN201')->first();
        $this->assertSame($kur->id, $mk->kurikulum_id);
        $this->assertSame(3, $mk->semester_no);
        $this->assertSame('wajib', $mk->jenis);
        $this->assertTrue($mk->prasyarat->pluck('id')->contains($prereq->id));
    }

    public function test_kurikulum_aktif_tunggal_per_prodi(): void
    {
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $admin = $this->user(User::ROLE_ADMIN);
        $k1 = \App\Models\Kurikulum::create(['prodi_id' => $mn->id, 'name' => 'K2018', 'year' => 2018, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.kurikulum.store'), [
            'name' => 'K2023', 'year' => 2023, 'prodi_id' => $mn->id, 'is_active' => '1',
        ])->assertRedirect(route('admin.kurikulum.index'));

        $this->assertFalse($k1->fresh()->is_active);
    }

    public function test_transkrip_ipk_dihitung_benar(): void
    {
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $mk = \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN301', 'name' => 'Statistik', 'sks' => 3]);
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id]);
        $course = Course::create([
            'user_id' => $dosen->id, 'prodi_id' => $mn->id, 'mata_kuliah_id' => $mk->id,
            'name' => 'Statistik A', 'code' => 'MN301A', 'semester' => 'Ganjil', 'year' => 2025,
            'status' => Course::STATUS_COMPLETED, 'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $comp = $course->gradeComponents()->create(['name' => 'Nilai', 'type' => 'uas', 'weight' => 100]);
        \App\Models\GradeScore::create(['grade_component_id' => $comp->id, 'user_id' => $student->id, 'score' => 80]);

        $t = (new \App\Services\Transcript())->forStudent($student->fresh());
        $this->assertSame(3, $t['total_sks']);
        $this->assertEqualsWithDelta(3.5, $t['ipk'], 0.01); // nilai 80 → B+ → 3.5
    }

    public function test_mahasiswa_lihat_transkrip_sendiri(): void
    {
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->get(route('transkrip.mine'))->assertOk();
    }

    public function test_ringkasan_akademik_mahasiswa(): void
    {
        \App\Models\Semester::setActiveKeys(['2026-Ganjil']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $mk = \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN301', 'name' => 'Statistik', 'sks' => 3]);
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id, 'entry_year' => 2025]);
        $course = Course::create([
            'user_id' => $dosen->id, 'prodi_id' => $mn->id, 'mata_kuliah_id' => $mk->id,
            'name' => 'Statistik A', 'code' => 'MN301A', 'semester' => 'Ganjil', 'year' => 2025,
            'status' => Course::STATUS_COMPLETED, 'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $comp = $course->gradeComponents()->create(['name' => 'Nilai', 'type' => 'uas', 'weight' => 100]);
        \App\Models\GradeScore::create(['grade_component_id' => $comp->id, 'user_id' => $student->id, 'score' => 80]);

        $a = (new \App\Services\AcademicSummary())->forStudent($student->fresh());
        $this->assertEqualsWithDelta(3.5, $a['ipk'], 0.01);   // 80 → B+ → 3.5
        $this->assertSame(3, $a['sks_kumulatif']);
        $this->assertSame(3, $a['semester_ke']);              // (2026-2025)*2 + Ganjil(1)
        $this->assertSame('aktif', $a['status']);

        $this->actingAs($student)->get(route('dashboard.mahasiswa'))
            ->assertOk()->assertSee('IPK')->assertSee('Perkuliahan');
    }

    public function test_kaprodi_transkrip_scope(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kaprodiAk = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        $mhsAk = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $ak->id]);
        $mhsMn = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id]);

        $this->actingAs($kaprodiAk)->get(route('admin.students.transkrip', $mhsAk))->assertOk();
        $this->actingAs($kaprodiAk)->get(route('admin.students.transkrip', $mhsMn))->assertForbidden();
    }

    public function test_admin_atur_jadwal_kelas(): void
    {
        $dosen = $this->user(User::ROLE_DOSEN);
        $admin = $this->user(User::ROLE_ADMIN);
        $course = $this->course($dosen);
        $slot = \App\Models\TimeSlot::create(['name' => 'Sesi 1', 'start_time' => '08:00', 'end_time' => '10:00', 'sort' => 1]);
        $room = \App\Models\Room::create(['code' => 'R201', 'name' => 'Ruang 201']);

        $this->actingAs($admin)->post(route('schedule.store', $course), [
            'day' => 1, 'time_slot_id' => $slot->id, 'room_id' => $room->id,
        ])->assertRedirect();

        // Jam & ruang diturunkan dari master sesi/ruangan
        $this->assertDatabaseHas('class_schedules', [
            'course_id' => $course->id, 'day' => 1, 'time_slot_id' => $slot->id,
            'room_id' => $room->id, 'start_time' => '08:00', 'end_time' => '10:00', 'room' => 'Ruang 201',
        ]);
    }

    public function test_data_master_admin_only(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $kaprodi = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);

        // Admin dapat mengelola Prodi/Ruangan/Sesi
        $this->actingAs($admin)->get(route('admin.prodi.index'))->assertOk()->assertSee('Program Studi');
        $this->actingAs($admin)->post(route('admin.rooms.store'), ['name' => 'Lab Komputer', 'code' => 'LK1', 'capacity' => 30])
            ->assertRedirect();
        $this->assertDatabaseHas('rooms', ['name' => 'Lab Komputer', 'capacity' => 30]);
        $this->actingAs($admin)->post(route('admin.timeslots.store'), ['name' => 'Sesi 2', 'start_time' => '10:00', 'end_time' => '11:40'])
            ->assertRedirect();
        $this->assertDatabaseHas('time_slots', ['name' => 'Sesi 2', 'start_time' => '10:00']);

        // Kaprodi tak boleh akses master admin-only
        $this->actingAs($kaprodi)->get(route('admin.rooms.index'))->assertForbidden();
        $this->actingAs($kaprodi)->get(route('admin.prodi.index'))->assertForbidden();
        $this->actingAs($kaprodi)->get(route('admin.timeslots.index'))->assertForbidden();
    }

    public function test_master_tolak_hapus_bila_dipakai(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $ak->id]);

        $this->actingAs($admin)->delete(route('admin.prodi.destroy', $ak))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('prodi', ['id' => $ak->id]);
    }

    public function test_jadwal_pribadi_bisa_diakses(): void
    {
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->get(route('schedule.index'))->assertOk();
        $this->actingAs($this->user(User::ROLE_DOSEN))->get(route('schedule.index'))->assertOk();
    }

    public function test_bentrok_ruang_ditolak(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $courseA = $this->course($this->user(User::ROLE_DOSEN));
        $courseB = $this->course($this->user(User::ROLE_DOSEN)); // dosen beda, periode sama (2026 Ganjil)
        $slot = \App\Models\TimeSlot::create(['name' => 'Sesi 1', 'start_time' => '08:00', 'end_time' => '10:00', 'sort' => 1]);
        $room = \App\Models\Room::create(['name' => 'Ruang 201']);

        $this->actingAs($admin)->post(route('schedule.store', $courseA), ['day' => 1, 'time_slot_id' => $slot->id, 'room_id' => $room->id])->assertRedirect();

        // Kelas lain di ruang & sesi & hari yang sama → ditolak
        $this->actingAs($admin)->post(route('schedule.store', $courseB), ['day' => 1, 'time_slot_id' => $slot->id, 'room_id' => $room->id])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('class_schedules', ['course_id' => $courseB->id]);
    }

    public function test_notifikasi_krs_ajukan_dan_setujui(): void
    {
        $wali = $this->user(User::ROLE_DOSEN);
        $course = $this->krsCourse();
        \App\Models\Setting::put('krs_open', '1');
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'advisor_id' => $wali->id]);

        $this->actingAs($mhs)->post(route('krs.add', $course));
        $this->actingAs($mhs)->post(route('krs.submit'));
        // Wali dapat notifikasi pengajuan
        $this->assertDatabaseHas('notifications', ['user_id' => $wali->id, 'type' => 'krs']);

        $this->actingAs($wali)->post(route('perwalian.krs.approve', $mhs));
        // Mahasiswa dapat notifikasi disetujui
        $this->assertDatabaseHas('notifications', ['user_id' => $mhs->id, 'type' => 'krs']);
    }

    public function test_approve_krs_lewati_kelas_penuh(): void
    {
        \App\Models\Semester::setActiveKeys(['2026-Ganjil']);
        \App\Models\Setting::put('krs_open', '1');
        $wali = $this->user(User::ROLE_DOSEN);
        $course = $this->krsCourse();
        $course->update(['quota' => 1]);
        $m1 = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'advisor_id' => $wali->id]);
        $m2 = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'advisor_id' => $wali->id]);

        // Keduanya menyusun draft dulu (belum penuh), baru mengajukan — submit tak cek kuota.
        $this->actingAs($m1)->post(route('krs.add', $course));
        $this->actingAs($m2)->post(route('krs.add', $course));
        $this->actingAs($m1)->post(route('krs.submit'));
        $this->actingAs($m2)->post(route('krs.submit'));

        $this->actingAs($wali)->post(route('perwalian.krs.approve', $m1)); // isi 1 kursi
        $this->actingAs($wali)->post(route('perwalian.krs.approve', $m2))->assertSessionHas('error');

        // m1 disetujui, m2 tetap diajukan (kelas penuh)
        $this->assertDatabaseHas('enrollments', ['course_id' => $course->id, 'user_id' => $m1->id, 'status' => \App\Models\Enrollment::STATUS_APPROVED]);
        $this->assertDatabaseHas('enrollments', ['course_id' => $course->id, 'user_id' => $m2->id, 'status' => \App\Models\Enrollment::STATUS_SUBMITTED]);
    }

    public function test_dosen_tak_bisa_atur_jadwal(): void
    {
        $owner = $this->user(User::ROLE_DOSEN);
        $course = $this->course($owner);

        // Penjadwalan kini wewenang admin — dosen pemilik pun tak bisa.
        $this->actingAs($owner)->post(route('schedule.store', $course), [
            'day' => 1, 'start_time' => '08:00', 'end_time' => '10:00',
        ])->assertForbidden();
    }

    public function test_dosen_tak_bisa_buka_kelas(): void
    {
        $dosen = $this->user(User::ROLE_DOSEN);

        $this->actingAs($dosen)->get(route('courses.create'))->assertForbidden();
        $this->actingAs($dosen)->post(route('courses.store'), [
            'user_id' => $dosen->id, 'name' => 'X', 'code' => 'X1',
            'semester' => 'Ganjil', 'year' => 2026, 'default_meeting_type' => 'tatap_muka',
        ])->assertForbidden();
    }

    public function test_admin_buka_kelas_pilih_dosen(): void
    {
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $admin = $this->user(User::ROLE_ADMIN);
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);

        $this->actingAs($admin)->get(route('courses.create'))->assertOk()->assertSee('Dosen Pengampu');

        $this->actingAs($admin)->post(route('courses.store'), [
            'user_id' => $dosen->id, 'name' => 'Manajemen Strategik', 'code' => 'MN401',
            'semester' => 'Ganjil', 'year' => 2026, 'quota' => 40, 'default_meeting_type' => 'tatap_muka',
        ])->assertRedirect();

        $course = Course::where('code', 'MN401')->first();
        $this->assertNotNull($course);
        $this->assertSame($dosen->id, $course->user_id);   // pengampu = dosen terpilih
        $this->assertSame($mn->id, $course->prodi_id);     // prodi mengikuti dosen
        $this->assertSame(40, $course->quota);
    }

    public function test_perwalian_dosen_lihat_bimbingan_dan_transkrip(): void
    {
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $wali = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id, 'advisor_id' => $wali->id, 'name' => 'Anak Wali']);
        $lain = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id, 'name' => 'Bukan Bimbingan']);

        $res = $this->actingAs($wali)->get(route('perwalian.index'));
        $res->assertOk();
        $res->assertSee('Anak Wali');
        $res->assertDontSee('Bukan Bimbingan');

        $this->actingAs($wali)->get(route('perwalian.transkrip', $mhs))->assertOk();
        $this->actingAs($wali)->get(route('perwalian.transkrip', $lain))->assertForbidden();
    }

    public function test_kalender_akademik_lihat_dan_kelola(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        \App\Models\Semester::setActiveKeys(['2026-Ganjil']);

        // Halaman tetap OK saat belum ada agenda sama sekali (regresi: merge koleksi kosong).
        $this->actingAs($admin)->get(route('academic.calendar'))->assertOk();

        // Admin tambah agenda
        $this->actingAs($admin)->post(route('academic.calendar.store'), [
            'title' => 'Pengisian KRS', 'type' => 'krs', 'start_date' => '2026-08-01',
            'end_date' => '2026-08-07', 'year' => 2026, 'semester' => 'Ganjil',
        ])->assertRedirect();
        $this->assertDatabaseHas('academic_events', ['title' => 'Pengisian KRS', 'type' => 'krs']);

        // Admin melihat halaman kelola (form Tambah Agenda ter-render)
        $this->actingAs($admin)->get(route('academic.calendar', ['periode' => '2026-Ganjil']))
            ->assertOk()->assertSee('Tambah Agenda')->assertSee('Pengisian KRS');

        // Semua peran bisa melihat; mahasiswa tak bisa kelola
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->get(route('academic.calendar', ['periode' => '2026-Ganjil']))
            ->assertOk()->assertSee('Pengisian KRS');
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->post(route('academic.calendar.store'), [
            'title' => 'X', 'type' => 'libur', 'start_date' => '2026-08-01', 'year' => 2026, 'semester' => 'Ganjil',
        ])->assertForbidden();
    }

    public function test_kalender_tampil_agenda_akademik(): void
    {
        \App\Models\AcademicEvent::create([
            'title' => 'Pekan UTS', 'type' => 'uts',
            'start_date' => '2026-08-03', 'end_date' => '2026-08-08',
            'year' => 2026, 'semester' => 'Ganjil',
        ]);

        // Agenda kampus tampil di kalender untuk semua pengguna (walau tanpa kelas).
        $this->actingAs($this->user(User::ROLE_MAHASISWA))
            ->get(route('calendar', ['month' => '2026-08']))
            ->assertOk()->assertSee('Pekan UTS');
    }

    public function test_cache_ipk_dihitung_dan_dipakai_rekap(): void
    {
        \App\Models\Semester::setActiveKeys(['2026-Ganjil']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $mk = \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN301', 'name' => 'Statistik', 'sks' => 3]);
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id, 'name' => 'Mhs Cache']);
        $course = Course::create([
            'user_id' => $dosen->id, 'prodi_id' => $mn->id, 'mata_kuliah_id' => $mk->id,
            'name' => 'Statistik A', 'code' => 'MN301A', 'semester' => 'Ganjil', 'year' => 2025,
            'status' => Course::STATUS_COMPLETED, 'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $comp = $course->gradeComponents()->create(['name' => 'Nilai', 'type' => 'uas', 'weight' => 100]);
        \App\Models\GradeScore::create(['grade_component_id' => $comp->id, 'user_id' => $student->id, 'score' => 80]);

        $this->assertNull($student->fresh()->ipk_cache);

        // Hitung cache
        $student->refreshAcademicCache();
        $this->assertEqualsWithDelta(3.5, $student->fresh()->ipk_cache, 0.01); // 80 → B+ → 3.5
        $this->assertSame(3, $student->fresh()->sks_cache);

        // Rekap admin memakai cache (lazy) & menampilkan IPK
        $this->actingAs($this->user(User::ROLE_ADMIN))->get(route('admin.academic.index'))
            ->assertOk()->assertSee('Mhs Cache')->assertSee('3.50');
    }

    public function test_rekap_akademik_akses_dan_scope(): void
    {
        $ak = Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $kaprodiAk = User::factory()->create(['role' => User::ROLE_KAPRODI, 'prodi_id' => $ak->id]);
        User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $ak->id, 'name' => 'Mhsakun Rekap']);
        User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id, 'name' => 'Mhsmanaj Rekap']);

        $res = $this->actingAs($kaprodiAk)->get(route('admin.academic.index'));
        $res->assertOk()->assertSee('Rekap Akademik')->assertSee('Mhsakun Rekap')->assertDontSee('Mhsmanaj Rekap');

        // Bukan untuk dosen/mahasiswa
        $this->actingAs($this->user(User::ROLE_DOSEN))->get(route('admin.academic.index'))->assertForbidden();
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->get(route('admin.academic.index'))->assertForbidden();
    }

    public function test_perwalian_tampil_ringkasan_akademik(): void
    {
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $wali = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);
        User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id, 'advisor_id' => $wali->id, 'name' => 'Anak Wali', 'entry_year' => 2024]);

        $this->actingAs($wali)->get(route('perwalian.index'))->assertOk()->assertSee('IPK')->assertSee('Anak Wali');
    }

    // ===================== Mode Samaran (spy) =====================

    public function test_admin_masuk_sebagai_mahasiswa(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA]);

        $this->actingAs($admin)->post(route('admin.impersonate.start', $mhs))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('impersonator_id', $admin->id);

        $this->assertAuthenticatedAs($mhs->fresh());
    }

    public function test_samaran_bisa_diakhiri_kembali_ke_admin(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA]);

        $this->actingAs($mhs)->withSession(['impersonator_id' => $admin->id])
            ->post(route('impersonate.stop'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('impersonator_id');

        $this->assertAuthenticatedAs($admin->fresh());
    }

    public function test_admin_tak_bisa_menyamar_admin_lain(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $other = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->post(route('admin.impersonate.start', $other))->assertForbidden();
    }

    public function test_non_admin_tak_bisa_menyamar(): void
    {
        $dosen = $this->user(User::ROLE_DOSEN);
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA]);

        $this->actingAs($dosen)->post(route('admin.impersonate.start', $mhs))->assertForbidden();
    }

    public function test_dosen_tidak_bisa_akses_kelas_dosen_lain(): void
    {
        $owner = $this->user(User::ROLE_DOSEN);
        $other = $this->user(User::ROLE_DOSEN);
        $course = $this->course($owner);

        $this->actingAs($owner)->get(route('courses.show', $course))->assertOk();
        $this->actingAs($other)->get(route('courses.show', $course))->assertForbidden();
    }

    // ===================== KRS (S4) =====================

    /** Siapkan periode aktif + kelas ber-SKS untuk skenario KRS. */
    private function krsCourse(int $sks = 3, ?User $dosen = null): Course
    {
        \App\Models\Semester::setActiveKeys(['2026-Ganjil']);
        $prodi = Prodi::firstOrCreate(['code' => 'MN'], ['name' => 'Manajemen']);
        $mk = \App\Models\MataKuliah::create(['prodi_id' => $prodi->id, 'code' => 'MN'.rand(100, 999), 'name' => 'MK Uji', 'sks' => $sks]);
        $dosen ??= User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $prodi->id]);

        return Course::create([
            'user_id' => $dosen->id, 'prodi_id' => $prodi->id, 'mata_kuliah_id' => $mk->id,
            'name' => 'Kelas KRS', 'code' => 'KRS'.$mk->id, 'semester' => 'Ganjil', 'year' => 2026,
            'status' => Course::STATUS_ACTIVE, 'join_code' => Course::generateJoinCode(),
        ]);
    }

    public function test_admin_buka_tutup_krs(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        // Panel KRS tampil di dashboard admin + halaman Kelola Semester
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Pengisian KRS')->assertSee('Akademik');
        $this->actingAs($admin)->get(route('admin.semesters.index'))->assertOk()->assertSee('Pengisian KRS');

        $this->actingAs($admin)->put(route('admin.semesters.krs'), ['krs_open' => '1', 'krs_max_sks' => 22])
            ->assertRedirect();

        $this->assertTrue(\App\Http\Controllers\KrsController::krsOpen());
        $this->assertSame(22, \App\Http\Controllers\KrsController::maxSks());
    }

    public function test_mahasiswa_susun_dan_ajukan_krs(): void
    {
        $course = $this->krsCourse();
        \App\Models\Setting::put('krs_open', '1');
        $wali = $this->user(User::ROLE_DOSEN);
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'advisor_id' => $wali->id]);

        // Halaman KRS ter-render
        $this->actingAs($mhs)->get(route('krs.index'))->assertOk()->assertSee('Kelas KRS');

        // Tambah ke KRS (draft)
        $this->actingAs($mhs)->post(route('krs.add', $course))->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'course_id' => $course->id, 'user_id' => $mhs->id, 'status' => \App\Models\Enrollment::STATUS_DRAFT,
        ]);

        // Belum disetujui → tak boleh masuk kelas
        $this->actingAs($mhs)->get(route('courses.show', $course))->assertForbidden();

        // Ajukan ke wali
        $this->actingAs($mhs)->post(route('krs.submit'))->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'course_id' => $course->id, 'user_id' => $mhs->id, 'status' => \App\Models\Enrollment::STATUS_SUBMITTED,
        ]);
    }

    public function test_krs_tutup_tak_bisa_tambah(): void
    {
        $course = $this->krsCourse();
        \App\Models\Setting::put('krs_open', '0');
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA]);

        $this->actingAs($mhs)->post(route('krs.add', $course))->assertForbidden();
        $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id, 'user_id' => $mhs->id]);
    }

    public function test_krs_batas_sks(): void
    {
        $course = $this->krsCourse(sks: 3);
        \App\Models\Setting::put('krs_open', '1');
        \App\Models\Setting::put('krs_max_sks', '2'); // batas < sks kelas
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA]);

        $this->actingAs($mhs)->post(route('krs.add', $course))->assertRedirect();
        // Melebihi batas → tidak tersimpan
        $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id, 'user_id' => $mhs->id]);
    }

    public function test_wali_setujui_krs_beri_akses_kelas(): void
    {
        $wali = $this->user(User::ROLE_DOSEN);
        $course = $this->krsCourse();
        \App\Models\Setting::put('krs_open', '1');
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'advisor_id' => $wali->id]);

        $this->actingAs($mhs)->post(route('krs.add', $course));
        $this->actingAs($mhs)->post(route('krs.submit'));

        // Halaman tinjau KRS wali ter-render + daftar bimbingan
        $this->actingAs($wali)->get(route('perwalian.index'))->assertOk();
        $this->actingAs($wali)->get(route('perwalian.krs', $mhs))->assertOk()->assertSee('Kelas KRS');

        // Wali menyetujui
        $this->actingAs($wali)->post(route('perwalian.krs.approve', $mhs))->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'course_id' => $course->id, 'user_id' => $mhs->id,
            'status' => \App\Models\Enrollment::STATUS_APPROVED, 'approved_by' => $wali->id,
        ]);

        // Sekarang mahasiswa dapat mengakses kelas & kelas otomatis muncul di LMS
        $this->actingAs($mhs)->get(route('courses.show', $course))->assertOk();
        $this->actingAs($mhs)->get(route('courses.index'))->assertOk()->assertSee($course->name);
    }

    public function test_gabung_kelas_manual_dihapus(): void
    {
        // Alur join-by-code sudah tidak ada (enrolmen lewat KRS).
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('enrollments.join'));
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->get('/join')->assertNotFound();
    }

    public function test_lms_tampil_info_krs_menunggu(): void
    {
        $course = $this->krsCourse();
        \App\Models\Setting::put('krs_open', '1');
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
        $this->actingAs($mhs)->post(route('krs.add', $course)); // draft, belum disetujui

        // Kelas belum muncul sebagai kelas aktif, tapi ada info KRS menunggu
        $this->actingAs($mhs)->get(route('courses.index'))->assertOk()->assertSee('belum disetujui');
    }

    public function test_krs_deteksi_bentrok_jadwal(): void
    {
        \App\Models\Setting::put('krs_open', '1');
        $c1 = $this->krsCourse();
        $c2 = $this->krsCourse();
        // Jadwal beririsan: keduanya Senin 08:00–10:00
        $c1->schedules()->create(['day' => 1, 'start_time' => '08:00', 'end_time' => '10:00', 'room' => 'R1']);
        $c2->schedules()->create(['day' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'room' => 'R2']);
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA]);

        $this->actingAs($mhs)->post(route('krs.add', $c1));
        $this->actingAs($mhs)->post(route('krs.add', $c2));

        $this->actingAs($mhs)->get(route('krs.index'))->assertOk()->assertSee('Jadwal bentrok');
    }

    public function test_krs_prasyarat_belum_lulus_ditolak(): void
    {
        \App\Models\Semester::setActiveKeys(['2026-Ganjil']);
        \App\Models\Setting::put('krs_open', '1');
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $dasar = \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN101', 'name' => 'Dasar', 'sks' => 3]);
        $lanjut = \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN201', 'name' => 'Lanjut', 'sks' => 3]);
        $lanjut->prasyarat()->attach($dasar->id);
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id]);
        $course = Course::create([
            'user_id' => $dosen->id, 'prodi_id' => $mn->id, 'mata_kuliah_id' => $lanjut->id,
            'name' => 'Lanjut A', 'code' => 'MN201A', 'semester' => 'Ganjil', 'year' => 2026,
            'status' => Course::STATUS_ACTIVE, 'join_code' => Course::generateJoinCode(),
        ]);

        $this->actingAs($student)->post(route('krs.add', $course))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id, 'user_id' => $student->id]);
    }

    public function test_krs_plafon_sks_ikuti_admin(): void
    {
        \App\Models\Semester::setActiveKeys(['2026-Ganjil']);
        \App\Models\Setting::put('krs_max_sks', '18');
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA]); // baru, tanpa IPS → paket 24
        // Plafon admin (18) memotong jatah.
        $this->assertSame(18, (new \App\Http\Controllers\KrsController())->quotaFor($student));
    }

    public function test_cetak_krs_pdf(): void
    {
        $course = $this->krsCourse();
        \App\Models\Setting::put('krs_open', '1');
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
        $this->actingAs($student)->post(route('krs.add', $course));

        $res = $this->actingAs($student)->get(route('krs.mine.pdf'));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
    }

    public function test_cetak_khs_pdf(): void
    {
        \App\Models\Semester::setActiveKeys(['2026-Ganjil']);
        $mn = Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);
        $mk = \App\Models\MataKuliah::create(['prodi_id' => $mn->id, 'code' => 'MN301', 'name' => 'Statistik', 'sks' => 3]);
        $dosen = User::factory()->create(['role' => User::ROLE_DOSEN, 'prodi_id' => $mn->id]);
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'prodi_id' => $mn->id]);
        $course = Course::create([
            'user_id' => $dosen->id, 'prodi_id' => $mn->id, 'mata_kuliah_id' => $mk->id,
            'name' => 'Statistik A', 'code' => 'MN301A', 'semester' => 'Ganjil', 'year' => 2025,
            'status' => Course::STATUS_COMPLETED, 'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $comp = $course->gradeComponents()->create(['name' => 'Nilai', 'type' => 'uas', 'weight' => 100]);
        \App\Models\GradeScore::create(['grade_component_id' => $comp->id, 'user_id' => $student->id, 'score' => 80]);

        $res = $this->actingAs($student)->get(route('khs.mine.pdf', ['period' => '2025-Ganjil']));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));

        // Periode tak dikenal → 404
        $this->actingAs($student)->get(route('khs.mine.pdf', ['period' => '1999-Ganjil']))->assertNotFound();
    }

    public function test_wali_lain_tak_bisa_setujui_krs(): void
    {
        $wali = $this->user(User::ROLE_DOSEN);
        $lain = $this->user(User::ROLE_DOSEN);
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'advisor_id' => $wali->id]);

        $this->actingAs($lain)->post(route('perwalian.krs.approve', $mhs))->assertForbidden();
    }
}
