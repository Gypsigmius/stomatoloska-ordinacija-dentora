# Dentora Clinic: three personalised confirmation emails

| File | Style | Size |
|---|---|---|
| `dentora-email-A-minimal.html` | Minimal: logo, thank you, the treatment, phone and message, signature. No photos. | 7 KB |
| `dentora-email-B-pismo-doktorice.html` | Personal letter from dr. Božana with her round portrait and the full enquiry | 11 KB |
| `dentora-email-C-editorial.html` | Dark header, treatment and phone highlighted, "Kako se pripremiti" tips before the first visit | 13 KB |

`preview-*.html` show each email filled with sample data. `dentora-email-3-verzije-pregled.jpg` shows all three side by side.

## Personalisation (merge tags)
`{{ime_prezime}}`, `{{telefon}}`, `{{email}}`, `{{tretman}}`, `{{poruka}}`, the same tags as the first email.
Version A uses `{{ime_prezime}}`, `{{tretman}}`, `{{telefon}}` and `{{poruka}}`. B and C use all five.

## Images to upload to `/wp-content/uploads/email/`
- `slike/dentora-email-dr-bozana-portret-240.jpg` (9 KB), used by version B
- The logos already load from the live site: `logo_zlatni.png` (A, B) and `logo_bijeli.png` (C, on the dark header).

## Compatibility (same build as the first email)
- Tables and inline styles; Outlook on Windows gets VML rounded buttons and a fixed 600 px width.
- Gmail, Outlook (Windows, Mac, Outlook.com, new Outlook), Yahoo and Apple Mail.
- Checked at 700, 375 and 320 px, including with the `<style>` block removed (as Gmail does for non-Google accounts): no horizontal scrolling, no broken images.
- In Outlook on Windows the round portrait in version B shows as a square, because Outlook ignores border-radius on images.
- `dentora-email-tekstualna-verzija.txt` is the plain-text part to send along with any of the three.
