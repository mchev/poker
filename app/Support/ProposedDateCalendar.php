<?php

namespace App\Support;

use App\Models\ProposedDate;
use Illuminate\Support\Carbon;

final class ProposedDateCalendar
{
    private const int DEFAULT_DURATION_HOURS = 4;

    public static function googleCalendarUrl(ProposedDate $date): string
    {
        $timezone = config('app.timezone');
        $start = $date->starts_at->copy()->timezone($timezone);
        $end = $start->copy()->addHours(self::DEFAULT_DURATION_HOURS);

        $params = http_build_query([
            'action' => 'TEMPLATE',
            'text' => self::eventTitle($date),
            'dates' => $start->utc()->format('Ymd\THis\Z').'/'.$end->utc()->format('Ymd\THis\Z'),
            'details' => self::eventDescription($date),
            'location' => $date->location ?? config('poker.location', ''),
        ]);

        return 'https://calendar.google.com/calendar/render?'.$params;
    }

    public static function icsContent(ProposedDate $date): string
    {
        $timezone = config('app.timezone');
        $start = $date->starts_at->copy()->timezone($timezone);
        $end = $start->copy()->addHours(self::DEFAULT_DURATION_HOURS);
        $now = Carbon::now($timezone);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.config('app.name').'//Poker//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:poker-date-'.$date->id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.$now->utc()->format('Ymd\THis\Z'),
            'DTSTART;TZID='.$timezone.':'.$start->format('Ymd\THis'),
            'DTEND;TZID='.$timezone.':'.$end->format('Ymd\THis'),
            'SUMMARY:'.self::escapeIcs(self::eventTitle($date)),
            'DESCRIPTION:'.self::escapeIcs(self::eventDescription($date)),
            'LOCATION:'.self::escapeIcs($date->location ?? config('poker.location', '')),
            'URL:'.self::escapeIcs(route('home')),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    private static function eventTitle(ProposedDate $date): string
    {
        $title = 'Poker party';

        if (filled($date->theme)) {
            $title .= ' — '.$date->theme;
        }

        return $title;
    }

    private static function eventDescription(ProposedDate $date): string
    {
        $parts = [
            $date->starts_at
                ->locale('fr')
                ->translatedFormat('l j F Y \à H\hi'),
        ];

        if ($date->beginners_welcome) {
            $parts[] = 'Débutant·e·s les bienvenu·e·s.';
        }

        $parts[] = route('home');

        return implode("\n", $parts);
    }

    private static function escapeIcs(string $value): string
    {
        return str_replace(
            ["\r\n", "\n", "\r", ',', ';'],
            ['\n', '\n', '\n', '\,', '\;'],
            $value,
        );
    }
}
