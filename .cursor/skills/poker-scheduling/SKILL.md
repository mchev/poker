---
name: poker-scheduling
description: "Organisation de soirées poker sans compte utilisateur. Activates when working on participant tokens, date polling, availability votes, tournament confirmation thresholds, Brevo notification emails, or the public Poker/Index page."
---

# Poker Scheduling

## Product Rules

- No Fortify login required for participants — identify via a 64-char `token` (query param or `poker_token` cookie).
- **Email is the source of truth** — re-subscribing with the same address reuses the existing participant and token (votes and proposals are preserved). Emails are normalized (lowercase, trimmed).
- Location and optional notes are shown on the public page for proposed/confirmed dates; participants can edit location (polling or confirmed) and add a note once confirmed.
- **Beginners welcome** defaults to `true` on new proposals (`beginners_welcome`).
- Auto-confirm a proposed date when it reaches `POKER_MIN_PARTICIPANTS` (default 4) **yes** votes (`confirmed_at` on the date). The poll stays open: other future dates remain visible and votable, and new dates can still be proposed.
- When several dates are confirmed in one batch, participants receive **one digest e-mail** (not one per date).
- Poll dates are sorted by **yes count descending**, then by start time.
- Confirmed dates expose **calendar links** (`.ics` download + Google Calendar URL).
- A round closes when a **confirmed** date has taken place (`starts_at` in the past). Future proposed dates carry over to the next polling round. No email is sent when the poll continues.
- Voter **names** are shown on poll cards and confirmed cards (yes / maybe / no lists).

## Key Files

| Area | Path |
|------|------|
| Service (business logic) | `app/Services/PokerSchedulingService.php` |
| Mail dispatch + local safety | `app/Support/PokerMailDispatcher.php` |
| Calendar export | `app/Support/ProposedDateCalendar.php` |
| Public controller | `app/Http/Controllers/PokerController.php` |
| Page | `resources/js/pages/Poker/Index.vue` |
| Config | `config/poker.php` |
| Mails | `app/Mail/*`, `resources/views/mail/poker/*` |
| Scheduler | `poker:complete-past-tournaments` (hourly) |

## Email (Brevo)

Transactional e-mails use Laravel's **Brevo API transport** (`symfony/brevo-mailer`). All poker mailables implement `ShouldQueue` and are dispatched via `PokerMailDispatcher`.

```
MAIL_MAILER=brevo
MAIL_FROM_ADDRESS=your-verified-sender@domain.com
MAIL_FROM_NAME="${APP_NAME}"

BREVO_API_KEY=your-brevo-api-key
BREVO_LIST_ID=65
```

`MAIL_FROM_ADDRESS` must be a sender verified in Brevo (Senders & Domains).

### Local / dev safety (critical)

The **local `.env` often uses the same `BREVO_API_KEY` as production**. Never send test notifications to the full participant list.

- In `local`, `PokerMailDispatcher` redirects **participant** e-mails to `POKER_LOCAL_MAIL_REDIRECT` (default `martin@pegase.io`).
- `BrevoContactService::syncParticipant()` is **skipped** in `local` (no writes to list `#65`).
- Tests must always use `Mail::fake()` — never rely on real sends.
- For manual local checks, only use `martin@pegase.io` (or override `POKER_LOCAL_MAIL_REDIRECT`).
- Set `POKER_REDIRECT_MAIL_IN_LOCAL=false` only if you fully understand the risk.

Admin notification (`AdminParticipantSubscribedMail`) is not redirected.

Participant contacts are synced to list `#65` on subscribe (non-local) with the `FNAME` attribute.

Set `POKER_LOCATION` as fallback when a date has no location (e-mails + calendar).

## Testing

Run `php artisan test --compact tests/Feature/PokerSchedulingTest.php` after changes.

Use `Mail::assertQueued()` for poker mailables (they implement `ShouldQueue`).

## Demo data (local only)

Reset the poker UI with realistic scenarios (calés, sondage, historique) :

```bash
php artisan db:seed --class=PokerDemoSeeder
```

- **Local only** — refuses to run in other environments.
- Wipes participants, votes, dates, and rounds (not Fortify users).
- Main test account : **Martin** (`martin@pegase.io`) with a fixed token printed in the console.
- Covers : 2 soirées calées (7 et 4 partants), 3 créneaux en sondage (presque validé, débutants, peu de votes), 1 soirée passée dans l’historique.

## UX Notes

- Large touch targets (`h-11` / `h-12`) for participants without smartphones.
- Personal link in email works on any device; cookie keeps session on the same browser.
- French copy in UI and emails.
