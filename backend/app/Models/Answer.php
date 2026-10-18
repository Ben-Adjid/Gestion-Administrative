<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Réponse à une question : officielle (équipe) ou communautaire (publiée après modération). */
class Answer extends Model
{
    protected $fillable = ['question_id', 'body', 'admin_id', 'author_name', 'is_official', 'status', 'ip_hash'];

    protected $casts = ['is_official' => 'boolean'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
