<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\GradeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function course(User $lecturer): Course
    {
        return Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Keamanan Sistem',
            'code' => 'SEC101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
    }

    public function test_login_menerima_nim_dan_memaksa_penggantian_password_awal(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_MAHASISWA,
            'nim_nip' => '20260099',
            'password' => 'sandi-awal',
            'must_change_password' => true,
        ]);

        $this->post(route('login'), ['login' => '20260099', 'password' => 'sandi-awal'])
            ->assertRedirect(route('portal.lms'));
        $this->get(route('portal.lms'))->assertRedirect(route('profile.edit'));

        $this->put(route('profile.password'), [
            'current_password' => 'sandi-awal',
            'password' => 'Sandi-Baru-2026!',
            'password_confirmation' => 'Sandi-Baru-2026!',
        ])->assertSessionHas('status');

        $student->refresh();
        $this->assertFalse($student->must_change_password);
        $this->assertTrue(Hash::check('Sandi-Baru-2026!', $student->password));
    }

    public function test_materi_disimpan_privat_dan_hanya_bisa_diakses_peserta(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $lecturer = User::factory()->create(['role' => User::ROLE_DOSEN]);
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
        $outsider = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
        $course = $this->course($lecturer);
        $meeting = $course->meetings()->create(['number' => 1, 'topic' => 'Pengantar', 'type' => 'tatap_muka']);
        Enrollment::create(['course_id' => $course->id, 'user_id' => $student->id, 'status' => Enrollment::STATUS_APPROVED]);

        $this->actingAs($lecturer)->post(route('materials.store', $meeting), [
            'title' => 'Modul privat',
            'type' => 'file',
            'file' => UploadedFile::fake()->create('modul.pdf', 20, 'application/pdf'),
        ])->assertSessionHas('status');

        $material = $meeting->materials()->firstOrFail();
        Storage::disk('local')->assertExists($material->path);
        Storage::disk('public')->assertMissing($material->path);
        $this->actingAs($student)->get(route('materials.download', $material))->assertOk();
        $this->actingAs($outsider)->get(route('materials.download', $material))->assertForbidden();
    }

    public function test_pengumpulan_belum_dinilai_mengunci_komponen_nilai(): void
    {
        $lecturer = User::factory()->create(['role' => User::ROLE_DOSEN]);
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
        $course = $this->course($lecturer);
        Enrollment::create(['course_id' => $course->id, 'user_id' => $student->id, 'status' => Enrollment::STATUS_APPROVED]);
        $component = $course->gradeComponents()->create(['name' => 'Tugas', 'type' => 'tugas', 'weight' => 100]);
        $assignment = $course->assignments()->create([
            'grade_component_id' => $component->id,
            'title' => 'Esai',
            'type' => Assignment::TYPE_TUGAS,
            'mode' => Assignment::MODE_INDIVIDU,
            'submission_mode' => Assignment::SUBMISSION_TEXT,
            'max_score' => 100,
            'published' => true,
        ]);
        $assignment->submissions()->create([
            'user_id' => $student->id,
            'answer_text' => 'Jawaban yang masih perlu diperiksa.',
            'status' => 'ontime',
            'submitted_at' => now(),
            'score' => null,
        ]);

        $grades = (new GradeCalculator)->forCourse($course);
        $row = $grades['rows']->first();
        $this->assertNull($row['components'][$component->id]);
        $this->assertSame([$component->id], $row['pending_components']);
        $this->assertSame(1, $grades['summary']['pending_students']);
    }

    public function test_library_excel_baru_tetap_menghasilkan_ekspor_nilai(): void
    {
        $lecturer = User::factory()->create(['role' => User::ROLE_DOSEN]);
        $course = $this->course($lecturer);

        $this->actingAs($lecturer)->get(route('export.nilai.excel', $course))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=nilai-keamanan-sistem.xlsx');
    }
}
