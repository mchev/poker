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

<x-mail::button :url="$url">
Voir / voter
</x-mail::button>

À bientôt autour de la table,<br>
{{ config('app.name') }}
</x-mail::message>

