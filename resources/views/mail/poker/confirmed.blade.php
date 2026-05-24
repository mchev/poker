<x-mail::message>
# Salut {{ $name }} !

@if (count($dates) === 1)
Bonne nouvelle : **c’est calé**.
@else
Bonne nouvelle : **{{ count($dates) }} soirées viennent d’être calées**.
@endif

@foreach ($dates as $date)
@if (count($dates) > 1)
---

@endif
**Quand :** {{ $date['label'] }}

@if ($date['location'])
**Où :** {{ $date['location'] }}
@endif

@if ($date['theme'])
**Thème :** {{ $date['theme'] }}
@endif

@if ($date['beginnersWelcome'])
**Débutant·e·s :** les bienvenu·e·s sont accepté·e·s sur ce créneau.
@endif

@endforeach

Tu viens à {{ count($dates) === 1 ? 'cette Poker party' : 'ces soirées' }} ?

<x-mail::button :url="$url">
Je réponds
</x-mail::button>

À bientôt autour de la table,<br>
{{ config('app.name') }}
</x-mail::message>
