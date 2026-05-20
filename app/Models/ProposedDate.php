<?php

namespace App\Models;

use Database\Factories\ProposedDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['scheduling_round_id', 'starts_at', 'proposed_by_participant_id'])]
class ProposedDate extends Model
{
    /** @use HasFactory<ProposedDateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SchedulingRound, $this>
     */
    public function schedulingRound(): BelongsTo
    {
        return $this->belongsTo(SchedulingRound::class);
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'proposed_by_participant_id');
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
