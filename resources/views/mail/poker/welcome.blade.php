<x-mail::message>
# Salut {{ $name }} !

Tu es inscrit·e pour nos soirées poker entre potes.

Voici ton lien perso pour voter sur les dates et dire si tu viens — pas besoin de mot de passe :

<x-mail::button :url="$url">
Rejoindre la table
</x-mail::button>

Garde ce lien pour toi. Les mails des autres ne sont jamais affichés sur le site.

À la prochaine,<br>
{{ config('app.name') }}
</x-mail::message>
