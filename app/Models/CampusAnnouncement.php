<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pengumuman kampus/prodi (broadcast). prodi_id null = seluruh kampus. */
#[Fillable(['created_by', 'prodi_id', 'title', 'body', 'pinned'])]
class CampusAnnouncement extends Model
{
    protected $casts = ['pinned' => 'boolean'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** Pengumuman yang relevan untuk seorang pengguna: kampus-wide + prodinya. */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->whereNull('prodi_id');
            if ($user->prodi_id) {
                $q->orWhere('prodi_id', $user->prodi_id);
            }
        })->orderByDesc('pinned')->latest();
    }

    public function audienceLabel(): string
    {
        return $this->prodi ? 'Prodi '.$this->prodi->name : 'Seluruh Kampus';
    }
}
