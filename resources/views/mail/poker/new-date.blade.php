<x-mail::message>
# Salut {{ $name }} !

{{ $proposedByName }} vient d’ajouter un nouveau créneau au sondage :

**{{ $dateLabel }}**

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
Voir / voter
</x-mail::button>

À bientôt autour de la table,<br>
{{ config('app.name') }}
</x-mail::message>
