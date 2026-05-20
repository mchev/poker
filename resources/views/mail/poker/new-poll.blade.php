<x-mail::message>
# Salut {{ $name }} !

La dernière soirée est passée… on relance une Poker party ?

<x-mail::button :url="$url">
Proposer une date et voter
</x-mail::button>

Dès que {{ config('poker.min_participants') }} personnes sont dispo le même soir, on envoie un e-mail de confirmation à tout le monde.

À bientôt,<br>
{{ config('app.name') }}
</x-mail::message>
