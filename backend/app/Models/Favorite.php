<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    // user_id est toujours posé par la relation $user->favorites(), jamais depuis la requête.
    protected $fillable = ['demarche_id', 'note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function demarche(): BelongsTo
    {
        return $this->belongsTo(Demarche::class);
    }
}
