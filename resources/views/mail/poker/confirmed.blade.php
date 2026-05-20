<x-mail::message>
# Salut {{ $name }} !

Bonne nouvelle : **c’est calé**.

**Quand :** {{ $dateLabel }}

@if ($location)
**Où :** {{ $location }}
@endif

@if ($theme)
**Thème :** {{ $theme }}
@endif

Tu viens à cette Poker party ?

<x-mail::button :url="$url">
Je réponds
</x-mail::button>

À bientôt autour de la table,<br>
{{ config('app.name') }}
</x-mail::message>
