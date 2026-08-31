<?php

use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KurikulumController;
use App\Http\Controllers\Admin\MataKuliahController;
use App\Http\Controllers\Admin\ProdiController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\TimeSlotController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssignmentGroupController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\GradeComponentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RubricController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SyllabusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Halaman depan hanya direktori publik. LMS dan SIAKAD berdiri sendiri.
Route::get('/', [PortalController::class, 'index'])->name('portal.index');
Route::redirect('/portal', '/');

// --- Guest ---
Route::middleware('guest')->prefix('lms')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    // Akses 1-klik mode demo (controller menolak dengan 404 jika DEMO_MODE non-aktif)
    Route::post('/demo/{role}', [LoginController::class, 'demo'])
        ->whereIn('role', ['admin', 'kaprodi', 'dosen', 'mahasiswa'])
        ->middleware('throttle:30,1')
        ->name('demo.login');
});

// --- Authenticated ---
Route::middleware('auth')->group(function () {
    Route::post('/lms/logout', [LoginController::class, 'destroy'])->name('logout');

    // LMS dipilih setelah pengguna berhasil login ke database LMS.
    Route::get('/lms', [PortalController::class, 'lms'])->name('portal.lms');

    // Akhiri mode samaran (dapat diakses oleh sesi yang sedang disamar)
    Route::post('/lms/stop-impersonating', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // Dashboard
    Route::get('/lms/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/lms/dashboard/dosen', [DashboardController::class, 'dosen'])
        ->middleware('role:dosen')->name('dashboard.dosen');
    Route::get('/lms/dashboard/mahasiswa', [DashboardController::class, 'mahasiswa'])
        ->middleware('role:mahasiswa')->name('dashboard.mahasiswa');

    // Panduan penggunaan (per role, dibaca dari auth() di view)
    Route::view('/lms/panduan', 'panduan')->name('panduan');

    // Kalender jadwal & pencarian global
    Route::get('/lms/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/lms/search', [SearchController::class, 'index'])->name('search');

    // Profil & kata sandi
    Route::get('/lms/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/lms/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/lms/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    // Jadwal per kelas (lihat kedua role; kelola di grup dosen)
    Route::get('/lms/courses/{course}/schedule', [ScheduleController::class, 'course'])->name('schedule.course');

    // Dosen memiliki kendali penuh atas kelas LMS yang dibuatnya.
    // Route statis ditempatkan sebelum /courses/{course} agar tidak dianggap sebagai ID kelas.
    Route::middleware('role:dosen')->group(function () {
        Route::get('/lms/courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('/lms/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/lms/courses/trash', [CourseController::class, 'trash'])->name('courses.trash');
        Route::patch('/lms/courses/{id}/restore', [CourseController::class, 'restore'])->name('courses.restore');
        Route::delete('/lms/courses/{id}/force', [CourseController::class, 'forceDestroy'])->name('courses.forceDestroy');
        Route::get('/lms/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('/lms/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/lms/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    });

    // Mahasiswa bergabung sendiri dengan kode kelas; tidak dibatasi program studi.
    Route::post('/lms/join', [EnrollmentController::class, 'join'])
        ->middleware(['role:mahasiswa', 'throttle:12,1'])->name('enrollments.join');

    // Kelas — index & show untuk kedua role (otorisasi di controller)
    Route::get('/lms/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/lms/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/lms/courses/{course}/materials', [CourseController::class, 'materials'])->name('courses.materials');

    // Materi — preview (inline) & download untuk kedua role
    Route::get('/lms/materials/{material}/preview', [MaterialController::class, 'preview'])->name('materials.preview');
    Route::get('/lms/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');

    // ===== Notifikasi =====
    Route::get('/lms/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/lms/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::get('/lms/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/lms/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // ===== Tugas & Kuis (kedua role; otorisasi di controller) =====
    Route::get('/lms/courses/{course}/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/lms/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/lms/assignments/{assignment}/submit', [SubmissionController::class, 'store'])
        ->middleware('throttle:20,1')->name('submissions.store');
    Route::get('/lms/submissions/{submission}/preview', [SubmissionController::class, 'preview'])->name('submissions.preview');
    Route::get('/lms/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');
    Route::delete('/lms/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');

    // Kelompok tugas (mahasiswa membentuk; dosen boleh mengatur — otorisasi di controller)
    Route::post('/lms/assignments/{assignment}/groups', [AssignmentGroupController::class, 'store'])->name('assignment-groups.store');
    Route::post('/lms/assignment-groups/{group}/members', [AssignmentGroupController::class, 'addMember'])->name('assignment-groups.addMember');
    Route::delete('/lms/assignment-groups/{group}/members/{member}', [AssignmentGroupController::class, 'removeMember'])->name('assignment-groups.removeMember');
    Route::delete('/lms/assignment-groups/{group}', [AssignmentGroupController::class, 'destroy'])->name('assignment-groups.destroy');

    // Kuis — kerjakan & review (mahasiswa + dosen review)
    Route::get('/lms/assignments/{assignment}/take', [QuizController::class, 'take'])->name('quizzes.take');
    Route::post('/lms/assignments/{assignment}/take', [QuizController::class, 'submit'])
        ->middleware('throttle:20,1')->name('quizzes.submit');
    Route::get('/lms/submissions/{submission}/review', [QuizController::class, 'review'])->name('quizzes.review');

    // ===== Penilaian (kedua role) =====
    Route::get('/lms/courses/{course}/grades', [GradeController::class, 'index'])->name('grades.index');

    // ===== Pengumuman (kedua role lihat) =====
    Route::get('/lms/courses/{course}/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

    // ===== Forum (kedua role) =====
    Route::get('/lms/courses/{course}/forum', [ForumController::class, 'index'])->name('forum.index');
    Route::post('/lms/courses/{course}/forum', [ForumController::class, 'storeThread'])->name('forum.threads.store');
    Route::get('/lms/forum/{thread}', [ForumController::class, 'show'])->name('forum.show');
    Route::post('/lms/forum/{thread}/replies', [ForumController::class, 'storeReply'])->name('forum.replies.store');
    Route::put('/lms/forum/{thread}', [ForumController::class, 'updateThread'])->name('forum.threads.update');
    Route::put('/lms/forum-replies/{reply}', [ForumController::class, 'updateReply'])->name('forum.replies.update');
    Route::delete('/lms/forum/{thread}', [ForumController::class, 'destroyThread'])->name('forum.threads.destroy');
    Route::delete('/lms/forum-replies/{reply}', [ForumController::class, 'destroyReply'])->name('forum.replies.destroy');

    // ===== Kehadiran (kedua role) =====
    Route::get('/lms/courses/{course}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/lms/attend/{token}', [AttendanceController::class, 'attend'])->name('attendance.attend');
    // Swa-presensi mahasiswa (pertemuan Mandiri)
    Route::post('/lms/meetings/{meeting}/self-attend', [AttendanceController::class, 'selfAttend'])
        ->middleware('throttle:20,1')->name('attendance.selfAttend');

    // ===== Silabus / RPS (kedua role lihat & unduh) =====
    Route::get('/lms/courses/{course}/syllabus', [SyllabusController::class, 'show'])->name('syllabus.show');
    Route::get('/lms/courses/{course}/syllabus/pdf', [SyllabusController::class, 'pdf'])->name('syllabus.pdf');

    // --- Khusus dosen (mengelola isi kelas yang diampu) ---
    Route::middleware('role:dosen')->group(function () {
        Route::patch('/lms/courses/{course}/complete', [CourseController::class, 'toggleComplete'])->name('courses.complete');
        Route::patch('/lms/courses/{course}/join-code', [EnrollmentController::class, 'regenerateJoinCode'])->name('enrollments.regenerateJoinCode');
        Route::get('/lms/enrollments/template', [EnrollmentController::class, 'template'])->name('enrollments.template');

        // Enrollment mahasiswa
        Route::get('/lms/courses/{course}/students', [CourseController::class, 'students'])->name('courses.students');
        Route::post('/lms/courses/{course}/students', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::post('/lms/courses/{course}/students/import', [EnrollmentController::class, 'import'])->name('enrollments.import');
        Route::delete('/lms/courses/{course}/students/{user}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

        // Jadwal kelas LMS dikelola dosen pemilik kelas.
        Route::post('/lms/courses/{course}/schedule', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::delete('/lms/schedule/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

        // Pertemuan
        Route::post('/lms/courses/{course}/meetings', [MeetingController::class, 'store'])->name('meetings.store');
        Route::put('/lms/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
        Route::delete('/lms/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

        // Materi
        Route::post('/lms/meetings/{meeting}/materials', [MaterialController::class, 'store'])->name('materials.store');
        Route::put('/lms/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
        Route::delete('/lms/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

        // Tugas & Kuis — CRUD
        Route::get('/lms/courses/{course}/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
        Route::post('/lms/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/lms/assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
        Route::put('/lms/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/lms/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::post('/lms/submissions/{submission}/grade', [SubmissionController::class, 'grade'])->name('submissions.grade');

        // Rubrik penilaian (kriteria per tugas)
        Route::post('/lms/assignments/{assignment}/rubric', [RubricController::class, 'store'])->name('rubric.store');
        Route::put('/lms/rubric-criteria/{criterion}', [RubricController::class, 'update'])->name('rubric.update');
        Route::delete('/lms/rubric-criteria/{criterion}', [RubricController::class, 'destroy'])->name('rubric.destroy');
        Route::get('/lms/assignments/{assignment}/download-all', [SubmissionController::class, 'downloadAll'])->name('submissions.downloadAll');
        Route::post('/lms/submissions/{submission}/reopen', [SubmissionController::class, 'reopen'])->name('submissions.reopen');

        // Kuis — kelola soal & nilai esai
        Route::get('/lms/assignments/{assignment}/questions', [QuizController::class, 'questions'])->name('quizzes.questions');
        Route::get('/lms/assignments/{assignment}/questions/export', [QuizController::class, 'exportQuestions'])->name('quizzes.questions.export');
        Route::post('/lms/assignments/{assignment}/questions/import', [QuizController::class, 'importQuestions'])->name('quizzes.questions.import');
        Route::post('/lms/assignments/{assignment}/questions', [QuizController::class, 'storeQuestion'])->name('quizzes.questions.store');
        Route::put('/lms/quiz-questions/{question}', [QuizController::class, 'updateQuestion'])->name('quizzes.questions.update');
        Route::delete('/lms/quiz-questions/{question}', [QuizController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');
        Route::post('/lms/submissions/{submission}/grade-essays', [QuizController::class, 'gradeEssays'])->name('quizzes.gradeEssays');

        // Komponen nilai + input nilai manual
        Route::post('/lms/courses/{course}/grades/manual', [GradeController::class, 'saveManual'])->name('grades.saveManual');
        Route::post('/lms/courses/{course}/grade-components', [GradeComponentController::class, 'store'])->name('grade-components.store');
        Route::put('/lms/grade-components/{component}', [GradeComponentController::class, 'update'])->name('grade-components.update');
        Route::delete('/lms/grade-components/{component}', [GradeComponentController::class, 'destroy'])->name('grade-components.destroy');

        // Pengumuman
        Route::post('/lms/courses/{course}/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/lms/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        // Forum — pin
        Route::patch('/lms/forum/{thread}/pin', [ForumController::class, 'pin'])->name('forum.pin');

        // Absensi — sesi & edit manual
        Route::get('/lms/meetings/{meeting}/attendance', [AttendanceController::class, 'session'])->name('attendance.session');
        Route::post('/lms/meetings/{meeting}/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
        Route::post('/lms/meetings/{meeting}/attendance/close', [AttendanceController::class, 'close'])->name('attendance.close');
        Route::post('/lms/meetings/{meeting}/attendance', [AttendanceController::class, 'updateStatus'])->name('attendance.update');

        // Silabus / RPS — edit
        Route::get('/lms/courses/{course}/syllabus/edit', [SyllabusController::class, 'edit'])->name('syllabus.edit');
        Route::put('/lms/courses/{course}/syllabus', [SyllabusController::class, 'update'])->name('syllabus.update');

        // AI (Claude)
        Route::post('/lms/materials/{material}/summarize', [AiController::class, 'summarizeMaterial'])->name('ai.material.summarize');
        Route::post('/lms/meetings/{meeting}/materials/generate', [AiController::class, 'generateMaterial'])->name('ai.material.generate');
        Route::post('/lms/assignments/{assignment}/generate-questions', [AiController::class, 'generateQuestions'])->name('ai.questions.generate');

        // Analitik
        Route::get('/lms/courses/{course}/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/lms/api/course/{course}/analytics', [AnalyticsController::class, 'data'])->name('analytics.data');

        // Laporan perkuliahan (ringkasan + PDF)
        Route::get('/lms/courses/{course}/report', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/lms/courses/{course}/report/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

        // Ekspor
        Route::get('/lms/courses/{course}/export/nilai-excel', [ExportController::class, 'nilaiExcel'])->name('export.nilai.excel');
        Route::get('/lms/courses/{course}/export/absensi-excel', [ExportController::class, 'absensiExcel'])->name('export.absensi.excel');
        Route::get('/lms/courses/{course}/export/absensi-pdf', [ExportController::class, 'absensiPdf'])->name('export.absensi.pdf');
        Route::get('/lms/courses/{course}/export/nilai-pdf', [ExportController::class, 'nilaiPdf'])->name('export.nilai.pdf');
    });

    // Pengawasan LMS untuk admin/kaprodi.
    Route::get('/lms/admin/courses', [AdminCourseController::class, 'index'])
        ->middleware('role:admin,kaprodi')->name('admin.courses.index');

    // --- Beranda & mahasiswa (admin & kaprodi; kaprodi ter-scope prodi di controller) ---
    Route::middleware('role:admin,kaprodi')->prefix('lms/admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [AdminStudentController::class, 'create'])->name('students.create');
        Route::post('/students', [AdminStudentController::class, 'store'])->name('students.store');
        Route::post('/students/import', [AdminStudentController::class, 'import'])->name('students.import');
        Route::post('/students/bulk/reset-password', [AdminStudentController::class, 'bulkResetPassword'])->name('students.bulkReset');
        Route::post('/students/bulk/destroy', [AdminStudentController::class, 'bulkDestroy'])->name('students.bulkDestroy');
        Route::get('/students/{student}/edit', [AdminStudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [AdminStudentController::class, 'update'])->name('students.update');
        Route::post('/students/{student}/reset-password', [AdminStudentController::class, 'resetPassword'])->name('students.resetPassword');
        Route::delete('/students/{student}', [AdminStudentController::class, 'destroy'])->name('students.destroy');
        // Unduh template CSV Data Master (semua entitas)
        Route::get('/master/template/{entity}', [TemplateController::class, 'download'])->name('master.template');

        // Kurikulum (kaprodi ter-scope prodi di controller)
        Route::get('/kurikulum', [KurikulumController::class, 'index'])->name('kurikulum.index');
        Route::get('/kurikulum/create', [KurikulumController::class, 'create'])->name('kurikulum.create');
        Route::post('/kurikulum', [KurikulumController::class, 'store'])->name('kurikulum.store');
        Route::post('/kurikulum/import', [KurikulumController::class, 'import'])->name('kurikulum.import');
        Route::post('/kurikulum/bulk/destroy', [KurikulumController::class, 'bulkDestroy'])->name('kurikulum.bulkDestroy');
        Route::get('/kurikulum/{kurikulum}/edit', [KurikulumController::class, 'edit'])->name('kurikulum.edit');
        Route::put('/kurikulum/{kurikulum}', [KurikulumController::class, 'update'])->name('kurikulum.update');
        Route::delete('/kurikulum/{kurikulum}', [KurikulumController::class, 'destroy'])->name('kurikulum.destroy');

        // Katalog mata kuliah (kaprodi ter-scope prodi di controller)
        Route::get('/matakuliah', [MataKuliahController::class, 'index'])->name('matakuliah.index');
        Route::get('/matakuliah/create', [MataKuliahController::class, 'create'])->name('matakuliah.create');
        Route::post('/matakuliah', [MataKuliahController::class, 'store'])->name('matakuliah.store');
        Route::post('/matakuliah/import', [MataKuliahController::class, 'import'])->name('matakuliah.import');
        Route::post('/matakuliah/bulk/destroy', [MataKuliahController::class, 'bulkDestroy'])->name('matakuliah.bulkDestroy');
        Route::get('/matakuliah/{matakuliah}/edit', [MataKuliahController::class, 'edit'])->name('matakuliah.edit');
        Route::put('/matakuliah/{matakuliah}', [MataKuliahController::class, 'update'])->name('matakuliah.update');
        Route::delete('/matakuliah/{matakuliah}', [MataKuliahController::class, 'destroy'])->name('matakuliah.destroy');
    });

    // --- Pengelolaan kampus (admin saja) ---
    Route::middleware('role:admin')->prefix('lms/admin')->name('admin.')->group(function () {
        // Kelola akun dosen & kaprodi
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::post('/staff/import', [StaffController::class, 'import'])->name('staff.import');
        Route::post('/staff/bulk/destroy', [StaffController::class, 'bulkDestroy'])->name('staff.bulkDestroy');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.resetPassword');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/grade-scale', [SettingController::class, 'editGradeScale'])->name('gradeScale.edit');
        Route::put('/grade-scale', [SettingController::class, 'updateGradeScale'])->name('gradeScale.update');
        Route::get('/ai', [SettingController::class, 'editAi'])->name('ai.edit');
        Route::put('/ai', [SettingController::class, 'updateAi'])->name('ai.update');

        Route::get('/semesters', [SemesterController::class, 'index'])->name('semesters.index');
        Route::post('/semesters', [SemesterController::class, 'store'])->name('semesters.store');
        Route::put('/semesters/active', [SemesterController::class, 'updateActive'])->name('semesters.updateActive');
        Route::delete('/semesters/{semester}', [SemesterController::class, 'destroy'])->name('semesters.destroy');

        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [BackupController::class, 'run'])->name('backups.run');
        Route::post('/backups/upload', [BackupController::class, 'upload'])->name('backups.upload');
        Route::get('/backups/{name}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::post('/backups/{name}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        Route::delete('/backups/{name}', [BackupController::class, 'destroy'])->name('backups.destroy');

        Route::get('/activity', [AdminActivityController::class, 'index'])->name('activity.index');

        // Mode samaran (spy) — admin masuk sebagai dosen/mahasiswa
        Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');

        // Data Master — Prodi, Ruangan, Sesi Kuliah (admin saja)
        Route::get('/prodi', [ProdiController::class, 'index'])->name('prodi.index');
        Route::post('/prodi', [ProdiController::class, 'store'])->name('prodi.store');
        Route::post('/prodi/import', [ProdiController::class, 'import'])->name('prodi.import');
        Route::post('/prodi/bulk/destroy', [ProdiController::class, 'bulkDestroy'])->name('prodi.bulkDestroy');
        Route::put('/prodi/{prodi}', [ProdiController::class, 'update'])->name('prodi.update');
        Route::delete('/prodi/{prodi}', [ProdiController::class, 'destroy'])->name('prodi.destroy');

        Route::get('/ruangan', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/ruangan', [RoomController::class, 'store'])->name('rooms.store');
        Route::post('/ruangan/import', [RoomController::class, 'import'])->name('rooms.import');
        Route::post('/ruangan/bulk/destroy', [RoomController::class, 'bulkDestroy'])->name('rooms.bulkDestroy');
        Route::put('/ruangan/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/ruangan/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        Route::get('/sesi-kuliah', [TimeSlotController::class, 'index'])->name('timeslots.index');
        Route::post('/sesi-kuliah', [TimeSlotController::class, 'store'])->name('timeslots.store');
        Route::post('/sesi-kuliah/import', [TimeSlotController::class, 'import'])->name('timeslots.import');
        Route::post('/sesi-kuliah/bulk/destroy', [TimeSlotController::class, 'bulkDestroy'])->name('timeslots.bulkDestroy');
        Route::put('/sesi-kuliah/{timeslot}', [TimeSlotController::class, 'update'])->name('timeslots.update');
        Route::delete('/sesi-kuliah/{timeslot}', [TimeSlotController::class, 'destroy'])->name('timeslots.destroy');
    });

});

// Kompatibilitas bookmark LMS lama; tidak pernah mengalihkan ke SIAKAD.
Route::fallback(function (Request $request) {
    abort_unless($request->isMethod('GET'), 404);

    $path = $request->path();
    $target = match (true) {
        $path === 'login' => 'lms/login',
        $path === 'dashboard' || $path === 'portal/lms' => 'lms',
        str_starts_with($path, 'dashboard/dosen') => 'lms/'.$path,
        str_starts_with($path, 'dashboard/mahasiswa') => 'lms/'.$path,
        str_starts_with($path, 'admin/courses') => 'lms/'.$path,
        str_starts_with($path, 'admin/') || $path === 'admin' => 'lms/'.$path,
        $path === 'courses-create' => 'lms/courses/create',
        $path === 'courses-trash' => 'lms/courses/trash',
        preg_match('#^courses/([^/]+)/edit$#', $path, $matches) === 1 => 'lms/courses/'.$matches[1].'/edit',
        collect(['courses', 'materials', 'assignments', 'assignment-groups', 'submissions',
            'forum', 'forum-replies', 'attend', 'meetings', 'rubric-criteria', 'quiz-questions', 'grade-components',
            'announcements', 'calendar', 'api/course'])
            ->contains(fn ($prefix) => $path === $prefix || str_starts_with($path, $prefix.'/')) => 'lms/'.$path,
        default => null,
    };

    abort_unless($target, 404);

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    $query = $request->getQueryString();

    return redirect('/'.$target.($query ? '?'.$query : ''), 301);
});
