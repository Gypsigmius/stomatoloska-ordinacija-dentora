# Dentora Clinic: confirmation email for the contact form

The patient gets this email automatically after sending the contact form.

## Files
- `dentora-potvrda-upita.html`: the email (HTML, 17 KB, well under Gmail's 102 KB clipping limit)
- `dentora-potvrda-upita.txt`: plain-text version (send it as the text part of the same email)
- `slike/dentora-email-dr-bozana-1200x800.jpg`: hero photo (65 KB, 2× for sharp retina display)
- `preview.html`: the same email filled with sample data, for viewing in a browser

## Before going live
1. Upload `slike/dentora-email-dr-bozana-1200x800.jpg` to
   `https://dentora-clinic.hr/wp-content/uploads/email/` (or change the `src` in the HTML to wherever it is uploaded).
   Email clients do not support AVIF or WebP, which is why this one image is a JPG.
2. The logo is already loaded from the live site: `https://dentora-clinic.hr/wp-content/uploads/2026/05/logo_zlatni.png`.
3. Replace the merge tags with your form plugin's field tags:

| In the email     | Field             | Contact Form 7 example | WPForms example     |
|------------------|-------------------|------------------------|---------------------|
| `{{ime_prezime}}`| Ime i prezime     | `[ime-prezime]`        | `{field_id="1"}`    |
| `{{telefon}}`    | Telefon           | `[telefon]`            | `{field_id="2"}`    |
| `{{email}}`      | E-mail            | `[email]`              | `{field_id="5"}`    |
| `{{tretman}}`    | Tretman (select)  | `[tretman]`            | `{field_id="3"}`    |
| `{{poruka}}`     | Poruka            | `[poruka]`             | `{field_id="4"}`    |

## Suggested settings
- Subject: `Zaprimili smo vaš upit – Dentora Clinic`
- From: `Dentora Clinic <info@dentora-clinic.hr>`; Reply-To: `info@dentora-clinic.hr` (the email tells patients they can simply reply)
- Recipient: the email address the patient entered in the form. The demo form now has an E-mail field for this.
- Send through SMTP (e.g. WP Mail SMTP with the clinic's mailbox, SPF/DKIM set) so it lands in the inbox, not spam.
- In Contact Form 7 use "Mail (2)" with "Use HTML content type" on; paste the HTML into the message body.

## Compatibility
- Built with tables and inline styles: Gmail (web, Android, iOS), Outlook (Windows 2016–365, Mac, Outlook.com, new Outlook), Yahoo Mail, Apple Mail.
- Outlook on Windows gets rounded buttons through VML and a fixed 600 px width through MSO conditional comments.
- Responsive with a media query, and still readable without one (Gmail with non-Google accounts strips `<style>`): no horizontal scrolling at 320 px even without the media query.
- Web-safe fonts (Georgia for headings, Arial for text) so the email looks the same everywhere.
- Light colour scheme declared; dark mode clients may still invert colours.
- Images have alt text and fixed sizes, so the layout holds when images are blocked.
- Recommended final check before launch: send a test through Litmus or Email on Acid, or to real Gmail/Outlook/Yahoo test inboxes.
