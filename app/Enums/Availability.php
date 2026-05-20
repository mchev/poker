<?php

namespace App\Enums;

enum Availability: string
{
    case Yes = 'yes';
    case No = 'no';
    case Maybe = 'maybe';
}
