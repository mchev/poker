<x-mail::message>
# Salut {{ $name }} !

Bonne nouvelle : assez de monde est dispo, **la prochaine soirée est calée**.

**Quand :** {{ $dateLabel }}

@if ($location)
**Où :** {{ $location }}
@endif

Tu viens ?

<x-mail::button :url="$url">
Je réponds
</x-mail::button>

À bientôt autour de la table,<br>
{{ config('app.name') }}
</x-mail::message>
