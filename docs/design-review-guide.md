# Web design review guide — how senior web designers audit a site

You are reviewing a client website like a senior web designer / front-end lead would before launch.
Judge against the standards below. Be specific: every finding names the page/route, the viewport width,
the element (CSS selector or visible text), what is wrong, why it matters, and the concrete fix (CSS value, copy, layout change).

## 0. The site under review
- Dentora Clinic (dental clinic, Zagreb). Croatian language. Brand: warm cream background, gold accent, Playfair Display headings + DM Sans body.
- Single-file static demo with hash routes. Open it with Playwright (Chromium is preinstalled, do NOT run `playwright install`):
  `site/dist/dentora-demo/index.html` (run `python site/build.py` first)
- Routes to cover (append to the URL): `` (home), `#aligneri`, `#ljuskice`, `#izbjeljivanje`, `#restaurativna`, `#oralna-kirurgija`, `#protetika`, `#parodontologija`, `#djecja`, `#ortodoncija`.
- Interactive pieces: mobile menu (`#burger` opens `#drawer`), booking popup (any `[data-open-form]` button opens `#formModal`), quiz (`#kviz`, buttons `.qopt`), before/after slider (`.cmp`), treatment tabs (`[role=tab]`), review cards (`.rv`, "Prikaži više").
- Google Fonts cannot load in this sandbox (no internet), so text renders in a fallback serif/sans. Do not report the font face itself; do judge sizes, line-height, hierarchy and spacing.
- Elements with class `pre` / `pre2` are scroll-reveal start states; before screenshots run
  `document.querySelectorAll('.pre,.pre2').forEach(e=>e.classList.remove('pre','pre2'))` and set `img[loading]` to eager.
- Do NOT edit the site files. Write only to your own output folder.

## 1. Page flow (story of the page)
A clinic homepage should answer, in order: Who are you and why trust you (hero) → What can you do for me (core treatments) → Proof (before/after, reviews) → Help me choose (quiz) → Who will treat me (team) → Everything else (other services) → How do I book (contact). Check:
- Hero states the value in one line + one primary CTA above the fold at 1440×900 AND 390×844.
- A booking CTA is reachable at most every ~1.5 screens of scroll; it always does the same thing.
- Each section has one job; no two adjacent sections look the same (alternate background/rhythm).
- Section order supports the decision; flag anything that interrupts the story.
- Every page ends with a clear next step (CTA, related pages); no dead ends.

## 2. Width, grid and spacing
- Content container: max ~1200–1320 px, centered; side gutter ≥ 16 px on phones, 24–40 px on tablets.
- Running text measure: 45–75 characters per line (ideal ~65). Flag paragraphs wider than ~80ch or narrower than ~35ch on desktop.
- Consistent vertical rhythm: section padding roughly 64–120 px desktop, 48–72 px mobile; spacing uses a scale (4/8-based), not random values.
- Alignment: left edges of headings, text and cards line up on one grid; no orphaned single card in a row; equal card heights in a row.
- Check 320, 375, 390, 414, 768, 1024, 1280, 1440, 1920 widths. At 1920 the layout must not look stretched or empty.

## 3. Typography and hierarchy
- One H1 per view; heading sizes step down clearly (H1 > H2 > H3); no heading looks like body text or vice versa.
- Body text ≥ 16 px on mobile (inputs ≥ 16 px to avoid iOS zoom), line-height 1.5–1.75; headings line-height ~0.95–1.2.
- Long headings must not leave a single word on its own line (use balance); no text clipped or overflowing.
- Small uppercase labels (eyebrows) ≥ 11 px with letter-spacing; not overused.

## 4. Mobile version (most traffic for a clinic is mobile)
- No horizontal scroll at any width (document.scrollWidth must equal viewport width).
- Single column; reading order on mobile = importance order (image/text order makes sense when stacked).
- Tap targets ≥ 44×44 px with ≥ 8 px between them (buttons, menu links, tabs, slider handle, close buttons).
- Primary CTA visible within the first screen; phone number tappable; booking form usable with the on-screen keyboard.
- Images: correct crop for portrait screens (faces not cut), no giant images pushing content below the fold, no tiny unreadable thumbnails.
- Sticky header must not cover anchored section titles; menu drawer must open/close smoothly, trap nothing, close on link tap.
- Grids collapse sensibly (3 → 2 → 1); horizontal scrollers show that they scroll (peek of next card).

## 5. Visual consistency and polish
- Buttons: same height, radius, padding, text style everywhere for the same role (primary/secondary).
- Cards of the same type share padding, radius, shadow and internal alignment.
- Colors only from the palette; enough contrast (WCAG AA: 4.5:1 body text, 3:1 large text and UI). Check gold text on cream and white text on gold.
- Image treatment consistent (radius, aspect ratios); no stretched/distorted or low-resolution images.
- Motion is subtle and purposeful; respects prefers-reduced-motion.

## 6. Conversion and trust (dental clinic specific)
- Trust signals early: real doctor photo/name, Google rating, real reviews, before/after.
- Copy is specific and benefit-led; no placeholder text, no lorem, no "demo" leftovers visible to visitors (except the known demo form message).
- Contact info (address, phone, hours) easy to find; forms short (name, phone, treatment, message).

## 7. Technical quality
- No console errors; no broken images (naturalWidth 0); every image has meaningful alt (or empty alt if decorative).
- Images sized close to display size; modern formats (AVIF/WebP); hero image not lazy-loaded; below-fold images lazy.
- Keyboard: visible focus states; Esc closes popup/drawer; all controls reachable with Tab.
- Hash routes: each view starts at top, has a breadcrumb/back path, and the menu highlights correctly.

## Output format
Write a Markdown report with:
1. A score 1–10 per area (Flow, Width/Grid, Typography, Mobile, Consistency, Conversion, Technical).
2. Findings sorted by severity: **Critical** (broken / blocks use), **High** (clearly hurts UX or looks unprofessional), **Medium**, **Low/polish**.
   Each finding: route · viewport · element · problem · why · fix.
3. "Keep" list: things that are excellent and must not be changed.
Do not pad the report; only include findings you verified on screen or by measurement.
