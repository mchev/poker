<x-mail::message>
# Salut {{ $name }} !

La dernière soirée est passée… On relance une date pour la prochaine ?

<x-mail::button :url="$url">
Proposer une date et voter
</x-mail::button>

Dès que {{ config('poker.min_participants') }} personnes sont dispo le même soir, on t’envoie un mail à tout le monde.

See you,<br>
{{ config('app.name') }}
</x-mail::message>
