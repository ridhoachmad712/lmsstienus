<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'prodi_id', 'kurikulum_id', 'advisor_id', 'nim_nip', 'phone', 'avatar', 'email_notifications',
    'gender', 'birth_place', 'birth_date', 'address', 'entry_year', 'student_status', 'nidn', 'jabatan',
    'ipk_cache', 'sks_cache', 'ips_cache'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_KAPRODI = 'kaprodi';
    public const ROLE_DOSEN = 'dosen';
    public const ROLE_MAHASISWA = 'mahasiswa';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_notifications' => 'boolean',
            'birth_date' => 'date',
            'ipk_cache' => 'float',
            'ips_cache' => 'float',
            'sks_cache' => 'integer',
        ];
    }

    /** Status akademik mahasiswa. */
    public const STUDENT_STATUSES = ['aktif', 'cuti', 'lulus', 'keluar'];

    public function genderLabel(): string
    {
        return match ($this->gender) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '—',
        };
    }

    public function prodi(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function kurikulum(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Kurikulum::class);
    }

    /** Dosen pembimbing akademik (wali) mahasiswa ini. */
    public function advisor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    /** Mahasiswa bimbingan (bila user ini dosen wali). */
    public function advisees(): HasMany
    {
        return $this->hasMany(User::class, 'advisor_id');
    }

    // --- Role helpers ---

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKaprodi(): bool
    {
        return $this->role === self::ROLE_KAPRODI;
    }

    /** Admin atau kaprodi (staf pengelola, bukan dosen/mahasiswa). */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isKaprodi();
    }

    public function isDosen(): bool
    {
        return $this->role === self::ROLE_DOSEN;
    }

    public function isMahasiswa(): bool
    {
        return $this->role === self::ROLE_MAHASISWA;
    }

    /** URL foto profil, atau null bila belum ada. */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar) : null;
    }

    public function initial(): string
    {
        return strtoupper(mb_substr($this->name, 0, 1));
    }

    // --- Relationships ---

    /** Kelas yang diampu (sebagai dosen). */
    public function teachingCourses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /** Kelas yang diikuti (enrollment DISETUJUI saja — dasar akses LMS). */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withTimestamps()
            ->withPivot('status', 'enrolled_at')
            ->wherePivot('status', Enrollment::STATUS_APPROVED);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** Notifikasi in-app (override relasi bawaan trait Notifiable). */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    /**
     * Hitung ulang & simpan cache akademik (IPK, SKS kumulatif, IPS terakhir).
     * Dipanggil lazy saat dibutuhkan & saat sebuah kelas diselesaikan/dibuka.
     */
    public function refreshAcademicCache(): void
    {
        $t = (new \App\Services\Transcript())->forStudent($this);

        $ips = null;
        foreach ($t['periods'] as $p) {
            if (! is_null($p['ips'] ?? null)) {
                $ips = $p['ips'];
            }
        }

        $this->forceFill([
            'ipk_cache' => $t['ipk'],
            'sks_cache' => $t['total_sks'],
            'ips_cache' => $ips,
        ])->saveQuietly();
    }
}
