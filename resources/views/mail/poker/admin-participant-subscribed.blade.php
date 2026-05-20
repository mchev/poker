<x-mail::message>
# Nouvelle inscription

Un nouveau joueur vient de s’inscrire à la Poker party.

**Nom :** {{ $participant->name }}

**E-mail :** {{ $participant->email }}

{{ config('app.name') }}
</x-mail::message>

