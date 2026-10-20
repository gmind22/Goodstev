# Namecheap hosting: forms and submissions

The contact form on `contact.html` and the FAQ form on `faq.html` both post to `assets/inc/sendmail.php`.

## Email delivery

The handler sends each valid submission to `info@goodstev.com` by default. If Namecheap cPanel provides environment variables, set `GOODSTEV_CONTACT_RECIPIENT` to override the recipient.

PHP `mail()` is the default transport. For better authenticated delivery, configure all of these environment variables for PHP:

- `GOODSTEV_SMTP_HOST`: the outgoing mail server shown in Namecheap cPanel Email Accounts > Connect Devices (for cPanel email, use the server hostname Namecheap gives you)
- `GOODSTEV_SMTP_USERNAME`: the full sending mailbox address
- `GOODSTEV_SMTP_PASSWORD`: that mailbox's password
- `GOODSTEV_SMTP_SECURE`: `ssl` for port 465, or `tls` for port 587
- `GOODSTEV_SMTP_PORT`: `465` for SSL or `587` for TLS

The sender address is set to the recipient address, so create/use `info@goodstev.com` as a mailbox hosted by Namecheap for `mail()` delivery, or use it as the authenticated SMTP mailbox. Do not put mailbox passwords in a public file or commit them to the project.

## Server backup

Every valid form submission is appended as one JSON record to `assets/inc/form-backups/submissions.jsonl` on the hosting account. The directory includes an Apache access rule to prevent public downloads. View or download the file from cPanel File Manager; keep a separate backup copy when maintaining the site. The PHP process needs write permission to `assets/inc` so it can create and append to the backup file.

The form response distinguishes successful email plus backup from partial failures. If email works but backup storage is unwritable, the visitor sees a warning; if backup succeeds but email fails, the submission remains saved on the server.

## Upload

Upload the contents of `docs/` into the domain's document root (commonly `public_html`) so `index.html` and `assets/` are at the root. Keep the PHP files and PHPMailer folder in their current relative locations. Use a supported PHP version in cPanel and verify by submitting both forms after deployment.
