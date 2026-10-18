<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Question publique d'un visiteur, publiée après modération. */
class Question extends Model
{
    protected $fillable = ['demarche_id', 'body', 'author_name', 'author_email', 'language', 'status', 'ip_hash'];

    public function demarche(): BelongsTo
    {
        return $this->belongsTo(Demarche::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderByDesc('is_official')->orderBy('created_at');
    }

    public function publishedAnswers(): HasMany
    {
        return $this->answers()->where('status', 'published');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
