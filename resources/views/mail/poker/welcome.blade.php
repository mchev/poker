<x-mail::message>
# Salut {{ $name }} !

Bienvenue à la table.

Voici ton lien perso pour proposer une date, voter, et confirmer ta présence — pas besoin de mot de passe :

<x-mail::button :url="$url">
Rejoindre la table
</x-mail::button>

Garde ce lien de côté (et évite de le partager).

À la prochaine,<br>
{{ config('app.name') }}
</x-mail::message>
