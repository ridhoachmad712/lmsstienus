<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\GradeScore;
use App\Models\SiakadGradeSync;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiakadGradeSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.legacy_siakad.grade_sync_enabled' => true,
            'database.connections.siakad' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('siakad');
        $this->createLegacySchema();
        $this->seedLegacyScale();
    }

    public function test_dosen_dapat_memetakan_jadwal_dan_sinkron_nilai_final(): void
    {
        [$lecturer, $course, $student] = $this->completedCourseWithGrade('MHS001', 80);
        // KRS mahasiswa boleh berasal dari prodi berbeda dengan prodi jadwal.
        $this->seedLegacyCourseAndKrs('MHS001', 'MN');

        $this->actingAs($lecturer)
            ->post(route('grades.siakad.map', $course), ['siakad_schedule_id' => 10])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->actingAs($lecturer)
            ->post(route('grades.siakad.sync', $course))
            ->assertRedirect()
            ->assertSessionHas('status');

        $khs = DB::connection('siakad')->table('khs_mhs')->where('nim_npm', 'MHS001')->first();
        $this->assertSame('80', $khs->nilai_akhir);
        $this->assertSame('3', $khs->bobot);
        $this->assertSame('B', $khs->grade);

        $sync = SiakadGradeSync::whereBelongsTo($course)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame(SiakadGradeSync::STATUS_SYNCED, $sync->status);
        $this->assertSame(1, $sync->attempts);
        $this->assertNotNull($sync->synced_at);
        $this->assertNotNull($course->fresh()->grades_finalized_at);

        // Retry idempoten: payload sama tidak ditulis/dianggap percobaan baru.
        $this->actingAs($lecturer)->post(route('grades.siakad.sync', $course))->assertSessionHas('status');
        $this->assertSame(1, $sync->fresh()->attempts);
    }

    public function test_mahasiswa_tanpa_krs_resmi_ditandai_gagal_tanpa_membuat_khs(): void
    {
        [$lecturer, $course] = $this->completedCourseWithGrade('MHS-TANPA-KRS', 88);
        $this->seedLegacySchedule();
        $course->update(['siakad_schedule_id' => 10]);

        $this->actingAs($lecturer)
            ->post(route('grades.siakad.sync', $course))
            ->assertRedirect()
            ->assertSessionHas('error');

        $sync = $course->gradeSyncs()->firstOrFail();
        $this->assertSame(SiakadGradeSync::STATUS_FAILED, $sync->status);
        $this->assertStringContainsString('tidak terdaftar', strtolower($sync->error_message));
        $this->assertSame(0, DB::connection('siakad')->table('khs_mhs')->count());
    }

    public function test_nilai_kelas_aktif_tidak_boleh_dikirim(): void
    {
        [$lecturer, $course] = $this->completedCourseWithGrade('MHS002', 75);
        $this->seedLegacyCourseAndKrs('MHS002');
        $course->update(['status' => Course::STATUS_ACTIVE, 'siakad_schedule_id' => 10]);

        $this->actingAs($lecturer)
            ->post(route('grades.siakad.sync', $course))
            ->assertRedirect()
            ->assertSessionHas('error', 'Kelas harus ditandai selesai sebelum nilai dikirim ke SIAKAD.');

        $this->assertDatabaseCount('siakad_grade_syncs', 0);
    }

    public function test_dosen_lain_tidak_dapat_memetakan_atau_mengirim_nilai(): void
    {
        [, $course] = $this->completedCourseWithGrade('MHS003', 75);
        $other = User::factory()->create(['role' => User::ROLE_DOSEN, 'nim_nip' => 'DOSEN-LAIN']);

        $this->actingAs($other)
            ->post(route('grades.siakad.map', $course), ['siakad_schedule_id' => 10])
            ->assertForbidden();
        $this->actingAs($other)
            ->post(route('grades.siakad.sync', $course))
            ->assertForbidden();
    }

    public function test_membuka_kembali_kelas_menandai_sinkronisasi_perlu_diulang(): void
    {
        [$lecturer, $course] = $this->completedCourseWithGrade('MHS004', 90);
        $this->seedLegacyCourseAndKrs('MHS004');
        $course->update(['siakad_schedule_id' => 10]);
        $this->actingAs($lecturer)->post(route('grades.siakad.sync', $course));

        $this->actingAs($lecturer)->patch(route('courses.complete', $course))->assertRedirect();

        $this->assertSame(Course::STATUS_ACTIVE, $course->fresh()->status);
        $this->assertNull($course->fresh()->grades_finalized_at);
        $this->assertSame(SiakadGradeSync::STATUS_STALE, $course->gradeSyncs()->firstOrFail()->status);
    }

    /** @return array{User, Course, User} */
    private function completedCourseWithGrade(string $nim, float $score): array
    {
        $lecturer = User::factory()->create([
            'role' => User::ROLE_DOSEN,
            'nim_nip' => 'DSN001',
        ]);
        $student = User::factory()->create([
            'role' => User::ROLE_MAHASISWA,
            'nim_nip' => $nim,
        ]);
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Akuntansi Integrasi',
            'code' => 'AKT101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_COMPLETED,
            'join_code' => Course::generateJoinCode(),
        ]);
        Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => Enrollment::STATUS_APPROVED,
            'enrolled_at' => now(),
        ]);
        $component = $course->gradeComponents()->create([
            'name' => 'Nilai Akhir',
            'type' => 'uas',
            'weight' => 100,
        ]);
        GradeScore::create([
            'grade_component_id' => $component->id,
            'user_id' => $student->id,
            'score' => $score,
        ]);

        return [$lecturer, $course, $student];
    }

    private function seedLegacyCourseAndKrs(string $nim, string $studentProdi = 'AK'): void
    {
        $this->seedLegacySchedule();
        DB::connection('siakad')->table('krs_mhs')->insert([
            'id_krs' => random_int(100, 99999),
            'kode_prodi' => $studentProdi,
            'id_jadwal' => 10,
            'nim_npm' => $nim,
            'id_thn_akademik' => 20,
        ]);
        DB::connection('siakad')->table('khs_mhs')->insert([
            'kode_prodi' => $studentProdi,
            'nim_npm' => $nim,
            'id_jadwal' => 10,
            'id_thn_akademik' => 20,
            'nilai_tgs' => '0',
            'nilai_uts' => '0',
            'nilai_uas' => '0',
            'nilai_akhir' => '-',
            'bobot' => '-',
            'grade' => '-',
        ]);
    }

    private function seedLegacySchedule(): void
    {
        DB::connection('siakad')->table('thn_akademik')->insertOrIgnore([
            'id_thn_akademik' => 20,
            'ket' => 'Ganjil',
            'thn_akademik' => 20261,
        ]);
        DB::connection('siakad')->table('jadwal_mengajar')->insertOrIgnore([
            'id_jadwal' => 10,
            'kode_prodi' => 'AK',
            'nip' => 'DSN001',
            'id_thn_akademik' => 20,
            'kode_mk' => 'AKT101',
        ]);
    }

    private function seedLegacyScale(): void
    {
        DB::connection('siakad')->table('tbl_grade')->insert([
            ['grade' => 'A', 'bobot' => '4.00', 'nilai_awal' => '85', 'nilai_akhir' => '100'],
            ['grade' => 'B', 'bobot' => '3.00', 'nilai_awal' => '70', 'nilai_akhir' => '84.99'],
            ['grade' => 'C', 'bobot' => '2.00', 'nilai_awal' => '55', 'nilai_akhir' => '69.99'],
            ['grade' => 'D', 'bobot' => '1.00', 'nilai_awal' => '40', 'nilai_akhir' => '54.99'],
            ['grade' => 'E', 'bobot' => '0', 'nilai_awal' => '0', 'nilai_akhir' => '39.99'],
        ]);
    }

    private function createLegacySchema(): void
    {
        Schema::connection('siakad')->create('thn_akademik', function (Blueprint $table) {
            $table->integer('id_thn_akademik')->primary();
            $table->string('ket', 10);
            $table->integer('thn_akademik');
        });
        Schema::connection('siakad')->create('jadwal_mengajar', function (Blueprint $table) {
            $table->integer('id_jadwal')->primary();
            $table->string('kode_prodi', 20);
            $table->string('nip', 20);
            $table->integer('id_thn_akademik');
            $table->string('kode_mk', 20);
        });
        Schema::connection('siakad')->create('krs_mhs', function (Blueprint $table) {
            $table->integer('id_krs')->primary();
            $table->string('kode_prodi', 20);
            $table->integer('id_jadwal');
            $table->string('nim_npm', 20);
            $table->integer('id_thn_akademik');
        });
        Schema::connection('siakad')->create('khs_mhs', function (Blueprint $table) {
            $table->string('kode_prodi', 20);
            $table->string('nim_npm', 20);
            $table->integer('id_jadwal');
            $table->integer('id_thn_akademik');
            $table->string('nilai_tgs', 10);
            $table->string('nilai_uts', 10);
            $table->string('nilai_uas', 10);
            $table->string('nilai_akhir', 10);
            $table->string('bobot', 10);
            $table->string('grade', 5);
        });
        Schema::connection('siakad')->create('tbl_grade', function (Blueprint $table) {
            $table->string('grade', 5);
            $table->string('bobot', 5);
            $table->string('nilai_awal', 10);
            $table->string('nilai_akhir', 10);
        });
    }
}
