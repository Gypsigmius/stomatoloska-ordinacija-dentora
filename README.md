# Stomatološka ordinacija Dentora: redizajn web stranice

Redesign of [dentora-clinic.hr](https://dentora-clinic.hr), a dental clinic in Zagreb (Dankovečka ul. 6).
Responsive website, a WordPress plugin for the enquiry forms, and confirmation emails for patients.

## Contents

| Folder | What it is |
|---|---|
| `site/` | The website: `src/index.template.html` (HTML, CSS and JS in one file), `img/` (optimised AVIF/WebP photos), `build.py` |
| `plugin/dentora-upiti/` | WordPress plugin **Dentora upiti**: receives the forms, emails the clinic, sends the patient a confirmation, saves enquiries in admin, shows the site on the homepage |
| `email/potvrda-v1/` | First confirmation email (with the doctor's photo) |
| `email/verzije/` | Three newer email versions: A minimal, B letter from dr. Božana, C editorial, plus `build_emails.py` that generates them |
| `tests/` | Plugin unit tests (26), browser end-to-end test of the forms, site QA script |
| `docs/` | Web design review guide and the review reports |

## The website

- Home page: hero with dr. Božana (sticky background), the three core treatments (aligners, veneers, whitening), before/after slider, treatment quiz, smile makeover package, team, real Google reviews, other services, digital clinic, contact.
- Treatment pages (`#aligneri`, `#ljuskice`, `#izbjeljivanje`) and service pages (`#restaurativna`, `#oralna-kirurgija`, `#protetika`, `#parodontologija`, `#djecja`, `#ortodoncija`) as hash routes in the same file.
- Booking popup, mobile drawer menu, fixed mobile booking bar, smooth scrolling.
- QA: no horizontal scroll at 320–1920 px, tap targets ≥ 44 px, text ≥ 16 px on phones, WCAG AA contrast, keyboard focus trap in the popup.

### Build

```bash
python site/build.py            # -> site/dist/dentora-demo/ and site/dist/dentora-demo.zip
python site/build.py --single   # also a single HTML file with embedded images
```

`site/dist/` is not committed; build it when you need to upload.

## Deploy (WordPress)

1. Install `plugin/dentora-upiti` (zip the folder, then Plugins → Add New → Upload).
2. Upload `site/dist/dentora-demo/` into the WordPress root (next to `wp-admin`).
3. With the plugin's **Naslovnica** option on (Upiti → Postavke), the site appears at the domain root.
4. Emails go out through `wp_mail()`, so the existing SMTP plugin sends them.

Details: `plugin/dentora-upiti/readme.txt`.

## Tests

```bash
php tests/plugin/test.php                          # plugin unit tests (WordPress stand-in)
php -S 127.0.0.1:8099 tests/plugin/router.php &    # local server: site + plugin endpoint
node tests/plugin/e2e.js                           # fills and sends the real forms in Chromium
node tests/qa/qa.js                                # layout/accessibility QA at 6 widths × 10 pages
```

The browser tests need Playwright with Chromium.

## Open items with the client

- Dr. Jelić Rubil's title: the site uses "dr. med. dent."
- Whitening photo `5.webp` is missing (3 before/after cases instead of 4)
- Before/after photos for veneers and aligners
- Custom WordPress theme (planned): layout fixed, content editable
