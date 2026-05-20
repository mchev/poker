<?php

namespace App\Enums;

enum SchedulingRoundStatus: string
{
    case Polling = 'polling';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
}
