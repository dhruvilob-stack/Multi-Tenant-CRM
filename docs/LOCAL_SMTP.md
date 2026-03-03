# Local SMTP Setup (Organization Mail)

Use a local SMTP catcher (recommended: Mailpit) so users can mail each other inside local network environments.

## 1) Run Mailpit

If binary installed:

```bash
mailpit --smtp 0.0.0.0:1025 --ui 0.0.0.0:8025
```

Or Docker:

```bash
docker run -d --name mailpit -p 1025:1025 -p 8025:8025 axllent/mailpit
```

## 2) Configure Laravel `.env`

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=no-reply@local.crm
MAIL_FROM_NAME="Multi Tenant CRM"
```

Then clear config cache:

```bash
php artisan config:clear
```

## 3) Access mailbox UI

Open:

- `http://127.0.0.1:8025`

## 4) In-app inbox

All sent mails are also stored internally in:

- Inbox Mail
- Send Mail
- Templates
- Trash

under Filament navigation group `Inbox Mail`.
