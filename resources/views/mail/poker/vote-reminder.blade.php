<x-mail::message>
# Salut {{ $name }} !

@if ($manual)
**{{ $dateLabel }}** a besoin de votes pour être calé : **{{ $yesCount }}/{{ $threshold }}**.
@else
**{{ $dateLabel }}** approche et ce créneau n’a pas encore assez de partants pour être calé : **{{ $yesCount }}/{{ $threshold }}**.
@endif

@if ($missingCount > 0)
Il manque encore **{{ $missingCount }}** {{ $missingCount > 1 ? 'partants' : 'partant' }} pour valider la soirée.
@endif

Tu n’as pas encore indiqué ta dispo pour ce créneau.

@if ($location)
**Où :** {{ $location }}
@endif

@if ($theme)
**Thème :** {{ $theme }}
@endif

@if (filled($games))
**Jeux :** {{ implode(', ', $games) }}
@endif

@if ($beginnersWelcome)
**Débutant·e·s :** les bienvenu·e·s sont accepté·e·s sur ce créneau.
@endif

<x-mail::button :url="$url">
Voter maintenant
</x-mail::button>

À bientôt autour de la table,<br>
{{ config('app.name') }}
</x-mail::message>
