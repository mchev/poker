---
name: poker-scheduling
description: "Organisation de soirées poker sans compte utilisateur. Activates when working on participant tokens, date polling, availability votes, tournament confirmation thresholds, Brevo notification emails, or the public Poker/Index page."
---

# Poker Scheduling

## Product Rules

- No Fortify login required for participants — identify via a 64-char `token` (query param or `poker_token` cookie).
- Emails and meeting location are **never** exposed on the public Inertia page; location only appears in confirmation emails (`POKER_LOCATION`).
- Auto-confirm a tournament when a proposed date reaches `POKER_MIN_PARTICIPANTS` (default 4) **yes** votes.
- After a confirmed tournament date passes, close the round and open a new polling round (hourly command + on page load).
- Only aggregate counts are shown in the UI, never participant names/emails tied to votes.

## Key Files

| Area | Path |
|------|------|
| Service (business logic) | `app/Services/PokerSchedulingService.php` |
| Public controller | `app/Http/Controllers/PokerController.php` |
| Page | `resources/js/pages/Poker/Index.vue` |
| Config | `config/poker.php` |
| Mails | `app/Mail/*`, `resources/views/mail/poker/*` |
| Scheduler | `poker:complete-past-tournaments` (hourly) |

## Email (Brevo)

Transactional e-mails use Laravel's **Brevo API transport** (`symfony/brevo-mailer`):

```
MAIL_MAILER=brevo
MAIL_FROM_ADDRESS=your-verified-sender@domain.com
MAIL_FROM_NAME="${APP_NAME}"

BREVO_API_KEY=your-brevo-api-key
BREVO_LIST_ID=65
```

`MAIL_FROM_ADDRESS` must be a sender verified in Brevo (Senders & Domains).

Participant contacts are synced to list `#65` on subscribe with the `FNAME` attribute. Subscription still works if Brevo sync fails (logged as warning).

Alternative: SMTP via `smtp-relay.brevo.com` with `MAIL_MAILER=smtp`.

Set `POKER_LOCATION` for the private venue line in confirmation emails only.

## Testing

Run `php artisan test --compact tests/Feature/PokerSchedulingTest.php` after changes.

## UX Notes

- Large touch targets (`h-11` / `h-12`) for participants without smartphones.
- Personal link in email works on any device; cookie keeps session on the same browser.
- French copy in UI and emails.
