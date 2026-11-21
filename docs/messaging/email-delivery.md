# Email delivery: senders, DNS and testing

Messaging slice 2 (2026-11-21). For the server admin who sets up mail for Fira Link.

## Senders

Every catalogue email goes out as **Fira Link**, from the address of its message class:

| Class | From | Reply-to | Used for |
|---|---|---|---|
| Security | `security@<domain>` (`MAIL_FROM_SECURITY`) | none | Codes, password reset, password, email and phone changes |
| Transactional, product, marketing | `no-reply@<domain>` (`MAIL_FROM_NO_REPLY`) | `support@<domain>` (`MAIL_SUPPORT_ADDRESS`) | Everything else |

`<domain>` is the domain of `MAIL_FROM_ADDRESS` unless the variables above are set. All three mailboxes must
exist (or be aliases), and the mail provider must be allowed to send for them. After changing `.env`, run
`php artisan config:clear`.

## DNS records (add them at the domain's DNS host)

Replace `<provider include>` and the DKIM values with what the mail provider (SMTP service) gives you.

| Record | Name | Value |
|---|---|---|
| SPF (TXT) | `@` | `v=spf1 include:<provider include> -all` (one SPF record only; merge with an existing one) |
| DKIM (TXT or CNAME) | `<selector>._domainkey` | The key the provider shows. Turn on DKIM signing in the provider |
| DMARC (TXT) | `_dmarc` | Start with `v=DMARC1; p=none; rua=mailto:dmarc@<domain>; fo=1`. After 2 to 4 weeks of clean reports: `p=quarantine`, then `p=reject` |

Check them with the provider's checker, then send a test email to a Gmail address and open "Show original":
SPF, DKIM and DMARC must all say **PASS**.

## Links and images

- Every link in an email points to `APP_URL` (the official site). Anything else is removed when the email is
  built and logged as `messages.link_dropped`. Set `APP_URL` to the public `https://` address in production.
- The logo is `public/assets/email/fira-link-logo@2x.png`, made from the current logo (`logo.png`) on white so it
  stays readable when a mail client darkens the page. It loads from `APP_URL`, so the site must serve
  `/assets/email/` publicly.

## Local testing with Mailpit

Mailpit catches every email on your computer and shows it in a browser; nothing reaches real inboxes.

```
docker run -d --name mailpit -p 8025:8025 -p 1025:1025 axllent/mailpit
```

In the local `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`, `MAIL_USERNAME=null`,
`MAIL_PASSWORD=null`, `MAIL_SCHEME=null`. Open http://localhost:8025.

Without sending anything: Admin → `/admin/messages` (admins with two-factor), or
`php artisan messages:preview <type> --locale=am --channel=mail --out=preview.html`.

## Client check (before each group goes live)

Send each converted message from staging to: Gmail (web and Android app), Outlook desktop (Windows), Apple Mail
(iPhone), Yahoo Mail. For each, in English and Amharic: logo visible, Amharic letters correct (not boxes), button
works and the plain link under it works, the code is easy to copy, images off still reads, dark mode readable.
