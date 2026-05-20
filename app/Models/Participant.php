<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'token'])]
#[Hidden(['email', 'token'])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Participant $participant): void {
            if (blank($participant->token)) {
                $participant->token = Str::random(64);
            }
        });
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * @return HasMany<ProposedDate, $this>
     */
    public function proposedDates(): HasMany
    {
        return $this->hasMany(ProposedDate::class, 'proposed_by_participant_id');
    }
}
