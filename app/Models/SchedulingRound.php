<?php

namespace App\Models;

use App\Enums\SchedulingRoundStatus;
use Database\Factories\SchedulingRoundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['status', 'confirmed_proposed_date_id'])]
class SchedulingRound extends Model
{
    /** @use HasFactory<SchedulingRoundFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SchedulingRoundStatus::class,
        ];
    }

    /**
     * @return HasMany<ProposedDate, $this>
     */
    public function proposedDates(): HasMany
    {
        return $this->hasMany(ProposedDate::class);
    }

    /**
     * @return BelongsTo<ProposedDate, $this>
     */
    public function confirmedDate(): BelongsTo
    {
        return $this->belongsTo(ProposedDate::class, 'confirmed_proposed_date_id');
    }

    public function isPolling(): bool
    {
        return $this->status === SchedulingRoundStatus::Polling;
    }

    public function isConfirmed(): bool
    {
        return $this->status === SchedulingRoundStatus::Confirmed;
    }
}
