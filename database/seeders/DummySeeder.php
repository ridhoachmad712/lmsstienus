<?php

namespace Database\Seeders;

use App\Models\AcademicEvent;
use App\Models\CampusAnnouncement;
use App\Models\Course;
use App\Models\CourseEvaluation;
use App\Models\Enrollment;
use App\Models\GradeScore;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Room;
use App\Models\Setting;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data dummy skala kampus: 136 mahasiswa, 22 dosen, 50 mata kuliah.
 * Tanpa Faker (jalan di produksi --no-dev).
 *
 * Jalankan: php artisan migrate:fresh --seeder=Database\\Seeders\\DummySeeder --force
 */
class DummySeeder extends Seeder
{
    private const N_MAHASISWA = 136;
    private const N_DOSEN = 22;
    private const N_MK = 50;

    private array $depan = [
        'Budi', 'Siti', 'Andi', 'Dewi', 'Rizky', 'Putri', 'Fajar', 'Ayu', 'Dimas', 'Nur',
        'Rina', 'Bayu', 'Lina', 'Yusuf', 'Sri', 'Arif', 'Citra', 'Galih', 'Wulan', 'Hendra',
        'Maya', 'Reza', 'Indah', 'Agus', 'Fitri', 'Eko', 'Novi', 'Taufik', 'Sinta', 'Rahmat',
        'Dian', 'Bagus', 'Ratna', 'Ilham', 'Vina', 'Doni', 'Yuni', 'Adit', 'Mega', 'Fikri',
        'Hesti', 'Rangga', 'Salsa', 'Teguh', 'Wati', 'Zaki', 'Alfian', 'Cahyo', 'Lestari', 'Umar',
    ];

    private array $belakang = [
        'Santoso', 'Wijaya', 'Pratama', 'Lestari', 'Ramadhan', 'Anggraini', 'Nugroho', 'Wulandari', 'Saputra', 'Aisyah',
        'Marlina', 'Hidayat', 'Wahyuni', 'Rahman', 'Dewanti', 'Gunawan', 'Sari', 'Fahlevi', 'Permata', 'Setiawan',
        'Handayani', 'Prasetyo', 'Rahmawati', 'Hakim', 'Utami', 'Kurniawan', 'Maharani', 'Firmansyah', 'Puspita', 'Nasution',
        'Siregar', 'Halim', 'Yulianti', 'Suryana', 'Oktaviani', 'Wibowo', 'Andini', 'Hermawan', 'Fitria', 'Ardiansyah',
    ];

    private function nama(int $i): string
    {
        return $this->depan[$i % count($this->depan)].' '.$this->belakang[($i * 3 + 5) % count($this->belakang)];
    }

    public function run(): void
    {
        // Periode aktif
        Setting::put('academic_year', '2025');
        Setting::put('semester', 'Ganjil');
        Setting::put('active_periods', json_encode(['2025-Ganjil']));

        // --- Prodi + Kurikulum aktif ---
        $prodis = collect([
            Prodi::create(['name' => 'Akuntansi', 'code' => 'AK']),
            Prodi::create(['name' => 'Manajemen', 'code' => 'MN']),
        ]);
        $kurikulum = $prodis->mapWithKeys(fn ($p) => [$p->id => Kurikulum::create([
            'prodi_id' => $p->id, 'name' => 'Kurikulum '.$p->code.' 2021', 'year' => 2021, 'is_active' => true,
        ])]);

        // --- Admin + Kaprodi ---
        User::create(['name' => 'Administrator', 'email' => 'admin@test.com', 'password' => Hash::make('password'), 'role' => User::ROLE_ADMIN, 'nim_nip' => '0000000001']);
        foreach ($prodis as $p) {
            User::create([
                'name' => 'Kaprodi '.$p->name, 'email' => 'kaprodi.'.strtolower($p->code).'@test.com',
                'password' => Hash::make('password'), 'role' => User::ROLE_KAPRODI, 'prodi_id' => $p->id,
                'nim_nip' => '10000000'.$p->id,
            ]);
        }

        // --- 22 Dosen (round-robin prodi) ---
        $dosen = collect();
        for ($i = 1; $i <= self::N_DOSEN; $i++) {
            $p = $prodis[$i % $prodis->count()];
            $dosen->push(User::create([
                'name' => 'Dr. '.$this->nama($i * 2).', M.M.',
                'email' => sprintf('dosen%02d@test.com', $i),
                'password' => Hash::make('password'),
                'role' => User::ROLE_DOSEN, 'prodi_id' => $p->id,
                'nidn' => '09'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'nim_nip' => '198'.str_pad((string) $i, 15, '0', STR_PAD_LEFT),
            ]));
        }
        $dosenByProdi = $dosen->groupBy('prodi_id');

        // --- 50 Mata Kuliah (25 per prodi) ---
        $mkNames = [
            'Pengantar', 'Dasar', 'Teori', 'Aplikasi', 'Analisis', 'Manajemen', 'Sistem', 'Etika', 'Metodologi', 'Praktikum',
            'Statistika', 'Keuangan', 'Pemasaran', 'Operasional', 'Strategik', 'Perpajakan', 'Audit', 'Anggaran', 'Investasi', 'Kewirausahaan',
            'Ekonomi Mikro', 'Ekonomi Makro', 'Bisnis Digital', 'Riset', 'Komunikasi',
        ];
        $mkAll = collect();
        foreach ($prodis as $p) {
            for ($n = 1; $n <= self::N_MK / 2; $n++) {
                $sem = (($n - 1) % 8) + 1;
                $mkAll->push(MataKuliah::create([
                    'prodi_id' => $p->id,
                    'kurikulum_id' => $kurikulum[$p->id]->id,
                    'code' => $p->code.str_pad((string) ($sem * 100 + $n), 3, '0', STR_PAD_LEFT),
                    'name' => $mkNames[($n - 1) % count($mkNames)].' '.$p->name.' '.(intdiv($n - 1, count($mkNames)) + 1),
                    'sks' => [2, 3, 3, 4][$n % 4],
                    'semester_no' => $sem,
                    'jenis' => $n % 5 === 0 ? 'pilihan' : 'wajib',
                ]));
            }
        }

        // --- Data Master: Ruangan & Sesi ---
        $rooms = collect(['R101', 'R102', 'R201', 'R202', 'Lab A', 'Lab B'])
            ->map(fn ($r, $i) => Room::create(['code' => 'RG'.($i + 1), 'name' => $r, 'capacity' => [30, 40, 45, 40, 25, 25][$i]]));
        $slots = collect([
            ['Sesi 1', '08:00', '09:40'], ['Sesi 2', '10:00', '11:40'], ['Sesi 3', '13:00', '14:40'],
            ['Sesi 4', '15:00', '16:40'], ['Sesi 5', '19:00', '20:40'],
        ])->map(fn ($s, $i) => TimeSlot::create(['name' => $s[0], 'start_time' => $s[1], 'end_time' => $s[2], 'sort' => $i + 1]));

        // --- Agenda akademik + pengumuman ---
        foreach ([
            ['title' => 'Pengisian KRS', 'type' => 'krs', 'start_date' => '2025-08-01', 'end_date' => '2025-08-07'],
            ['title' => 'Awal Perkuliahan', 'type' => 'kuliah', 'start_date' => '2025-08-11', 'end_date' => null],
            ['title' => 'UTS', 'type' => 'uts', 'start_date' => '2025-10-06', 'end_date' => '2025-10-11'],
            ['title' => 'UAS', 'type' => 'uas', 'start_date' => '2025-12-08', 'end_date' => '2025-12-13'],
        ] as $ev) {
            AcademicEvent::create($ev + ['year' => 2025, 'semester' => 'Ganjil']);
        }
        CampusAnnouncement::create([
            'created_by' => User::where('role', User::ROLE_ADMIN)->value('id'),
            'title' => 'Selamat Datang Semester Ganjil 2025/2026', 'body' => 'Registrasi ulang & pengisian KRS dibuka sesuai kalender akademik.',
            'pinned' => true,
        ]);

        // --- 136 Mahasiswa ---
        $students = collect();
        for ($i = 1; $i <= self::N_MAHASISWA; $i++) {
            $p = $prodis[$i % $prodis->count()];
            $angkatan = 2021 + ($i % 4); // 2021–2024
            $wali = $dosenByProdi[$p->id][$i % $dosenByProdi[$p->id]->count()];
            $students->push(User::create([
                'name' => $this->nama($i),
                'email' => sprintf('mhs%03d@test.com', $i),
                'password' => Hash::make('password'),
                'role' => User::ROLE_MAHASISWA,
                'prodi_id' => $p->id,
                'kurikulum_id' => $kurikulum[$p->id]->id,
                'advisor_id' => $wali->id,
                'entry_year' => $angkatan,
                'student_status' => $i % 25 === 0 ? 'cuti' : 'aktif',
                'gender' => $i % 2 === 0 ? 'L' : 'P',
                'nim_nip' => $angkatan.'01'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'phone' => '0812'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
            ]));
        }
        $mhsByProdi = $students->where('student_status', 'aktif')->groupBy('prodi_id');

        $mkByProdi = $mkAll->groupBy('prodi_id');
        $gradedStudentIds = collect();

        // Buat kelas (aktif & selesai) per prodi.
        foreach ($prodis as $p) {
            $prodiDosen = $dosenByProdi[$p->id];
            $prodiMhs = $mhsByProdi[$p->id] ?? collect();
            $prodiMk = $mkByProdi[$p->id]->values();

            // 10 kelas AKTIF (2025 Ganjil) + 4 kelas SELESAI (2024 Genap) per prodi.
            foreach (range(0, 13) as $k) {
                $completed = $k >= 10;
                $mk = $prodiMk[$k % $prodiMk->count()];
                $pengampu = $prodiDosen[$k % $prodiDosen->count()];

                $course = Course::create([
                    'user_id' => $pengampu->id, 'prodi_id' => $p->id, 'mata_kuliah_id' => $mk->id,
                    'name' => $mk->name, 'code' => $mk->code, 'class_name' => 'Kelas '.chr(65 + ($k % 3)),
                    'join_code' => Course::generateJoinCode(),
                    'semester' => $completed ? 'Genap' : 'Ganjil', 'year' => $completed ? 2024 : 2025,
                    'quota' => 40, 'default_meeting_type' => 'tatap_muka',
                    'status' => $completed ? Course::STATUS_COMPLETED : Course::STATUS_ACTIVE,
                    'description' => 'Kelas '.$mk->name.'.',
                ]);

                // Jadwal (sesi + ruang) — hanya kelas aktif.
                if (! $completed) {
                    $slot = $slots[$k % $slots->count()];
                    $room = $rooms[$k % $rooms->count()];
                    $course->schedules()->create([
                        'day' => ($k % 5) + 1, 'time_slot_id' => $slot->id,
                        'start_time' => $slot->start_time, 'end_time' => $slot->end_time,
                        'room_id' => $room->id, 'room' => $room->name,
                    ]);
                }

                // Enroll ~30 mahasiswa prodi (rotasi agar variatif).
                $peserta = $prodiMhs->slice(($k * 7) % max(1, $prodiMhs->count()))->take(30);
                if ($peserta->count() < 15) {
                    $peserta = $prodiMhs->take(30);
                }
                foreach ($peserta as $st) {
                    $course->students()->attach($st->id, ['enrolled_at' => now()->subMonths($completed ? 8 : 1)]);
                }

                // Komponen nilai.
                $comps = [
                    $course->gradeComponents()->create(['name' => 'Tugas', 'type' => 'tugas', 'weight' => 30]),
                    $course->gradeComponents()->create(['name' => 'UTS', 'type' => 'uts', 'weight' => 30]),
                    $course->gradeComponents()->create(['name' => 'UAS', 'type' => 'uas', 'weight' => 40]),
                ];

                // Pertemuan + materi (2 pertemuan).
                foreach ([1, 2] as $mNo) {
                    $meeting = $course->meetings()->create([
                        'number' => $mNo, 'topic' => 'Pertemuan '.$mNo.' — '.$mk->name,
                        'date' => now()->subWeeks(3 - $mNo), 'description' => 'Materi pertemuan '.$mNo.'.',
                    ]);
                    $meeting->materials()->create(['title' => 'Slide '.$mNo, 'type' => \App\Models\Material::TYPE_LINK, 'url' => 'https://contoh.test/slide'.$mNo]);
                }

                // Kelas SELESAI → nilai penuh (isi transkrip/IPK).
                if ($completed) {
                    foreach ($peserta as $st) {
                        foreach ($comps as $comp) {
                            GradeScore::create(['grade_component_id' => $comp->id, 'user_id' => $st->id, 'score' => rand(58, 96)]);
                        }
                        $gradedStudentIds->push($st->id);
                    }
                } else {
                    // Kelas aktif → sebagian EDOM terisi.
                    foreach ($peserta->take(12) as $st) {
                        CourseEvaluation::create([
                            'course_id' => $course->id, 'user_id' => $st->id,
                            'answers' => [rand(3, 4), rand(3, 4), rand(2, 4), rand(3, 4), rand(2, 4)],
                            'comment' => rand(0, 2) === 0 ? 'Pengajaran baik.' : null,
                        ]);
                    }
                }
            }
        }

        // --- KRS: 8 mahasiswa punya pengajuan menunggu wali (di kelas aktif acak) ---
        $activeCourses = Course::where('status', Course::STATUS_ACTIVE)->get();
        foreach ($students->where('student_status', 'aktif')->take(8) as $idx => $st) {
            $c = $activeCourses[$idx % $activeCourses->count()];
            if (! Enrollment::where('course_id', $c->id)->where('user_id', $st->id)->exists()) {
                Enrollment::create([
                    'course_id' => $c->id, 'user_id' => $st->id,
                    'status' => Enrollment::STATUS_SUBMITTED, 'submitted_at' => now(),
                ]);
            }
        }

        // --- Cache IPK untuk mahasiswa yang punya nilai (kelas selesai) ---
        User::whereIn('id', $gradedStudentIds->unique()->all())->get()->each->refreshAcademicCache();

        $this->command->info('DummySeeder selesai: '.self::N_MAHASISWA.' mahasiswa, '.self::N_DOSEN.' dosen, '.self::N_MK.' mata kuliah, '.Course::count().' kelas.');
        $this->command->info('Login admin: admin@test.com / password (semua akun sandinya: password)');
    }
}
