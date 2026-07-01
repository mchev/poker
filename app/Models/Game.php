<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Game extends Model
{
    use HasFactory;

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class);
    }

    public function proposedDates(): BelongsToMany
    {
        return $this->belongsToMany(ProposedDate::class);
    }
}
