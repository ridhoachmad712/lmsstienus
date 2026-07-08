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

    public function test_dosen_atur_jadwal_kelas(): void
    {
        $dosen = $this->user(User::ROLE_DOSEN);
        $course = $this->course($dosen);

        $this->actingAs($dosen)->post(route('schedule.store', $course), [
            'day' => 1, 'start_time' => '08:00', 'end_time' => '10:00', 'room' => 'R201',
        ])->assertRedirect();

        $this->assertDatabaseHas('class_schedules', ['course_id' => $course->id, 'day' => 1, 'room' => 'R201']);
    }

    public function test_jadwal_pribadi_bisa_diakses(): void
    {
        $this->actingAs($this->user(User::ROLE_MAHASISWA))->get(route('schedule.index'))->assertOk();
        $this->actingAs($this->user(User::ROLE_DOSEN))->get(route('schedule.index'))->assertOk();
    }

    public function test_dosen_lain_tak_bisa_atur_jadwal(): void
    {
        $owner = $this->user(User::ROLE_DOSEN);
        $other = $this->user(User::ROLE_DOSEN);
        $course = $this->course($owner);

        $this->actingAs($other)->post(route('schedule.store', $course), [
            'day' => 1, 'start_time' => '08:00', 'end_time' => '10:00',
        ])->assertForbidden();
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

        // Sekarang mahasiswa dapat mengakses kelas
        $this->actingAs($mhs)->get(route('courses.show', $course))->assertOk();
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

    public function test_wali_lain_tak_bisa_setujui_krs(): void
    {
        $wali = $this->user(User::ROLE_DOSEN);
        $lain = $this->user(User::ROLE_DOSEN);
        $mhs = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'advisor_id' => $wali->id]);

        $this->actingAs($lain)->post(route('perwalian.krs.approve', $mhs))->assertForbidden();
    }
}
