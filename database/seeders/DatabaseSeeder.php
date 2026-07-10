<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        // --- Program Studi ---
        $prodiAk = \App\Models\Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']);
        $prodiMn = \App\Models\Prodi::create(['name' => 'Manajemen', 'code' => 'MN']);

        // --- Admin kampus ---
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'nim_nip' => '000000000000000000',
        ]);

        // --- Ketua Prodi (per prodi) ---
        User::create([
            'name' => 'Kaprodi Akuntansi',
            'email' => 'kaprodi.ak@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_KAPRODI,
            'prodi_id' => $prodiAk->id,
            'nim_nip' => '100000000000000001',
        ]);
        User::create([
            'name' => 'Kaprodi Manajemen',
            'email' => 'kaprodi.mn@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_KAPRODI,
            'prodi_id' => $prodiMn->id,
            'nim_nip' => '100000000000000002',
        ]);

        // --- Dosen ---
        $dosen = User::create([
            'name' => 'Dr. Andi Wijaya, S.E., M.M.',
            'email' => 'dosen@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_DOSEN,
            'prodi_id' => $prodiMn->id,
            'nim_nip' => '198501012010011001',
            'phone' => '081234567890',
        ]);

        // --- 30 Mahasiswa (dibagi rata ke 2 prodi) ---
        $students = collect();
        for ($i = 1; $i <= 30; $i++) {
            $nim = '210901' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $students->push(User::create([
                'name' => $faker->name(),
                'email' => sprintf('mhs%03d@test.com', $i),
                'password' => Hash::make('password'),
                'role' => User::ROLE_MAHASISWA,
                'prodi_id' => $i % 2 === 0 ? $prodiMn->id : $prodiAk->id,
                'nim_nip' => $nim,
                'phone' => $faker->phoneNumber(),
            ]));
        }

        // --- Periode aktif = 2025 Ganjil (selaras dengan kelas di bawah) ---
        \App\Models\Setting::put('academic_year', '2025');
        \App\Models\Setting::put('semester', 'Ganjil');
        \App\Models\Setting::put('active_periods', json_encode(['2025-Ganjil']));

        // --- Dosen wali + biodata angkatan/status untuk semua mahasiswa ---
        User::whereIn('id', $students->pluck('id'))
            ->update(['advisor_id' => $dosen->id, 'entry_year' => 2024, 'student_status' => 'aktif']);

        // --- Data Master: Ruangan & Sesi Kuliah ---
        $rooms = collect([
            ['code' => 'R201', 'name' => 'Ruang 201', 'capacity' => 40],
            ['code' => 'LAB1', 'name' => 'Lab Komputer', 'capacity' => 30],
        ])->map(fn ($r) => \App\Models\Room::create($r));
        $slots = collect([
            ['name' => 'Sesi 1', 'start_time' => '08:00', 'end_time' => '09:40', 'sort' => 1],
            ['name' => 'Sesi 2', 'start_time' => '10:00', 'end_time' => '11:40', 'sort' => 2],
            ['name' => 'Sesi 3', 'start_time' => '13:00', 'end_time' => '14:40', 'sort' => 3],
        ])->map(fn ($s) => \App\Models\TimeSlot::create($s));

        // --- Agenda akademik (2025 Ganjil) ---
        foreach ([
            ['title' => 'Pengisian KRS', 'type' => 'krs', 'start_date' => '2025-08-01', 'end_date' => '2025-08-07'],
            ['title' => 'Awal Perkuliahan', 'type' => 'kuliah', 'start_date' => '2025-08-11', 'end_date' => null],
            ['title' => 'UTS', 'type' => 'uts', 'start_date' => '2025-10-06', 'end_date' => '2025-10-11'],
            ['title' => 'UAS', 'type' => 'uas', 'start_date' => '2025-12-08', 'end_date' => '2025-12-13'],
        ] as $ev) {
            \App\Models\AcademicEvent::create($ev + ['year' => 2025, 'semester' => 'Ganjil']);
        }

        // --- Pengumuman kampus contoh ---
        \App\Models\CampusAnnouncement::create([
            'created_by' => User::where('role', User::ROLE_ADMIN)->value('id'),
            'prodi_id' => null,
            'title' => 'Registrasi Ulang Semester Ganjil 2025/2026',
            'body' => 'Seluruh mahasiswa wajib melakukan registrasi ulang sebelum pengisian KRS.',
            'pinned' => true,
        ]);

        // --- 2 Kelas, masing-masing 15 mahasiswa ---
        $courseData = [
            ['name' => 'Manajemen Keuangan', 'code' => 'MNJ2103'],
            ['name' => 'Manajemen Pemasaran', 'code' => 'MNJ2105'],
        ];

        foreach ($courseData as $idx => $cd) {
            $mk = \App\Models\MataKuliah::create([
                'prodi_id' => $prodiMn->id,
                'code' => $cd['code'],
                'name' => $cd['name'],
                'sks' => 3,
            ]);

            $course = Course::create([
                'user_id' => $dosen->id,
                'prodi_id' => $prodiMn->id,
                'mata_kuliah_id' => $mk->id,
                'name' => $cd['name'],
                'code' => $cd['code'],
                'join_code' => Course::generateJoinCode(),
                'semester' => 'Ganjil',
                'year' => 2025,
                'description' => 'Mata kuliah ' . $cd['name'] . ' untuk Prodi Manajemen FEB UNM.',
                'status' => Course::STATUS_ACTIVE,
            ]);

            // enroll 15 mahasiswa (kelas 1: 0-14, kelas 2: 15-29)
            $slice = $students->slice($idx * 15, 15);
            foreach ($slice as $student) {
                $course->students()->attach($student->id, ['enrolled_at' => now()]);
            }

            // Jadwal pakai Sesi & Ruangan (master)
            $slot = $slots[$idx % $slots->count()];
            $room = $rooms[$idx % $rooms->count()];
            $course->schedules()->create([
                'day' => $idx + 1,
                'time_slot_id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'room_id' => $room->id,
                'room' => $room->name,
            ]);

            // 3 pertemuan + materi
            for ($m = 1; $m <= 3; $m++) {
                $meeting = $course->meetings()->create([
                    'number' => $m,
                    'topic' => $cd['name'],
                    'date' => now()->subWeeks(3 - $m),
                    'description' => $faker->sentence(10),
                ]);

                $meeting->materials()->create([
                    'title' => "Slide Pertemuan {$m}",
                    'type' => Material::TYPE_LINK,
                    'url' => 'https://drive.google.com/contoh-slide-' . $m,
                ]);

                if ($m === 1) {
                    $meeting->materials()->create([
                        'title' => 'Video Pengantar',
                        'type' => Material::TYPE_VIDEO,
                        'url' => 'https://youtube.com/watch?v=contoh',
                    ]);
                }
            }

            // --- Komponen nilai (Tugas 30, UTS 30, UAS 40) ---
            $compTugas = $course->gradeComponents()->create(['name' => 'Tugas', 'type' => 'tugas', 'weight' => 30]);
            $course->gradeComponents()->create(['name' => 'UTS', 'type' => 'uts', 'weight' => 30]);
            $course->gradeComponents()->create(['name' => 'UAS', 'type' => 'uas', 'weight' => 40]);

            // --- Tugas (1 sudah dinilai sebagian) ---
            $tugas = $course->assignments()->create([
                'meeting_id' => $course->meetings()->where('number', 1)->value('id'),
                'grade_component_id' => $compTugas->id,
                'title' => 'Tugas 1 — Analisis Kasus',
                'description' => 'Kerjakan analisis kasus pada bab 1.',
                'type' => Assignment::TYPE_TUGAS,
                'deadline' => now()->addDays(7),
                'max_score' => 100,
            ]);

            // 5 mahasiswa pertama submit; 3 di antaranya sudah dinilai
            foreach ($slice->take(5)->values() as $k => $student) {
                $tugas->submissions()->create([
                    'user_id' => $student->id,
                    'file_path' => null,
                    'status' => 'ontime',
                    'submitted_at' => now()->subDay(),
                    'score' => $k < 3 ? rand(70, 95) : null,
                    'feedback' => $k < 3 ? 'Kerja bagus, perhatikan referensi.' : null,
                ]);
            }

            // --- Kuis dengan 2 soal PG + 1 esai ---
            $kuis = $course->assignments()->create([
                'meeting_id' => $course->meetings()->where('number', 2)->value('id'),
                'title' => 'Kuis 1',
                'description' => 'Kuis singkat materi awal.',
                'type' => Assignment::TYPE_KUIS,
                'deadline' => now()->addDays(3),
                'duration_minutes' => 15,
                'max_score' => 100,
            ]);
            $kuis->questions()->create([
                'type' => QuizQuestion::TYPE_PG, 'question' => '2 + 2 = ?',
                'options' => ['A' => '3', 'B' => '4', 'C' => '5'], 'correct_answer' => 'B', 'points' => 1,
            ]);
            $kuis->questions()->create([
                'type' => QuizQuestion::TYPE_PG, 'question' => 'Ibukota Sulawesi Selatan?',
                'options' => ['A' => 'Makassar', 'B' => 'Manado', 'C' => 'Palu'], 'correct_answer' => 'A', 'points' => 1,
            ]);
            $kuis->questions()->create([
                'type' => QuizQuestion::TYPE_ESSAY, 'question' => 'Jelaskan pengertian manajemen menurut Anda.',
                'points' => 2,
            ]);

            // --- Pengumuman ---
            $course->announcements()->create([
                'user_id' => $dosen->id,
                'title' => 'Selamat datang di '.$course->name,
                'content' => 'Silakan pelajari materi pertemuan 1 dan kerjakan Tugas 1 sebelum deadline.',
            ]);

            // --- Forum thread + 1 balasan ---
            $thread = $course->forumThreads()->create([
                'user_id' => $slice->first()->id,
                'title' => 'Pertanyaan tentang Tugas 1',
                'content' => 'Apakah analisis kasus boleh dikerjakan berkelompok?',
                'pinned' => false,
            ]);
            $thread->replies()->create([
                'user_id' => $dosen->id,
                'content' => 'Tugas 1 dikerjakan individu ya.',
            ]);

            // --- RPS / Silabus ---
            $course->syllabus()->create([
                'description' => 'Mata kuliah ini membahas konsep dasar '.$cd['name'].'.',
                'cpl' => "Mampu menerapkan konsep keilmuan secara bertanggung jawab.\nMampu mengambil keputusan berdasarkan analisis data.",
                'cpmk' => "Memahami konsep dasar.\nMampu menerapkan dalam kasus nyata.\nMenganalisis permasalahan terkait.",
                'sub_cpmk' => "Menjelaskan terminologi dasar.\nMenyelesaikan studi kasus sederhana.",
                'references' => "Buku Ajar ".$cd['name']." (2024).\nJurnal Manajemen FEB UNM.",
                'assessment' => "Tugas 30%, UTS 30%, UAS 40%. Kehadiran minimal 75%.",
                'rules' => "Wajib hadir minimal 75%. Keterlambatan tugas dikurangi nilai.",
            ]);

            // --- Absensi: 2 pertemuan pertama sudah ada sesi ---
            foreach ($course->meetings()->orderBy('number')->take(2)->get() as $meeting) {
                foreach ($slice->values() as $idx => $student) {
                    // mayoritas hadir; mahasiswa terakhir sering alpa (kehadiran < 75%)
                    $status = 'hadir';
                    if ($idx === $slice->count() - 1) {
                        $status = 'alpa';
                    } elseif ($idx % 7 === 0) {
                        $status = ['izin', 'sakit'][$meeting->number % 2];
                    }
                    $meeting->attendances()->create([
                        'user_id' => $student->id,
                        'status' => $status,
                        'method' => $status === 'hadir' ? 'qr' : 'manual',
                    ]);
                }
            }
        }

        $this->command->info('Seeder selesai: 1 dosen, 30 mahasiswa, 2 kelas.');
        $this->command->info('Login dosen: dosen@test.com / password');
        $this->command->info('Login mahasiswa: mhs001@test.com / password');
    }
}
