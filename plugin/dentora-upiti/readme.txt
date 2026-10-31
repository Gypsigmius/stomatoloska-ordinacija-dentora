=== Dentora upiti ===
Version: 1.1.0
Requires at least: 5.8
Requires PHP: 7.4

Receives enquiries from the Dentora site forms (booking popup, quiz, treatment pages), emails them to the clinic and sends the patient a confirmation email.

== Installation ==
1. WordPress admin → Plugins → Add New → Upload Plugin → choose dentora-upiti.zip → Install → Activate.
2. A new "Upiti" item appears in the admin menu. Open Upiti → Postavke and check:
   - E-mail klinike: where enquiries arrive (default info@dentora-clinic.hr)
   - Potvrda pacijentu: on/off
   - Izgled potvrde: A Minimal / B Pismo dr. Božane / C Editorial / the first version with the photo
   - Email subjects
3. Upload the website demo (dentora-demo.zip) to the same domain. Its forms send to /wp-json/dentora/v1/upit.

== How sending works ==
The plugin calls wp_mail(). The SMTP plugin you already have (WP Mail SMTP, FluentSMTP, Post SMTP…) takes over from there, so no SMTP settings are needed in this plugin.

Two emails per enquiry:
- To the clinic: all fields. Reply-To is the patient, so "Reply" answers the patient directly.
- To the patient: the selected template, personalised with name, phone, e-mail, treatment and message, plus a plain-text version.

Every enquiry is also saved under Upiti in WordPress admin, with the email status (poslano / GREŠKA). Nothing is lost if email fails.

== Spam protection ==
- Hidden honeypot field (bots fill it, people don't)
- Time check: a form sent less than 3 seconds after opening is ignored
- Max 5 enquiries per IP address in 10 minutes
- Server-side validation of name, phone and e-mail; treatment must be one of the known options

== Form on another domain ==
If the website runs on a different domain than WordPress, enter that domain under Upiti → Postavke → Dopuštene domene, and in the website's index.html set window.DENTORA_API to the full address, e.g. https://dentora-clinic.hr/wp-json/dentora/v1/upit

== Troubleshooting ==
- Form says "Slanje trenutno nije moguće": open https://YOUR-DOMAIN/wp-json/dentora/v1 in a browser. If the plugin is active you see JSON with "namespace":"dentora/v1" and the route "/dentora/v1/upit". A 404 page means the plugin is not active or permalinks are "Plain" (Settings → Permalinks → choose "Post name" → Save). A 401/403 means a security plugin blocks the REST API for visitors; allow the dentora/v1 route there.
- Enquiry appears under Upiti but status is GREŠKA: the SMTP plugin could not send. Check its email log / test email.
