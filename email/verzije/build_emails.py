"""Builds three Dentora confirmation emails (table layout, inline styles, MSO/VML for Outlook)."""
import pathlib, html

OUT = pathlib.Path(__file__).parent
LOGO = "https://dentora-clinic.hr/wp-content/uploads/2026/05/logo_zlatni.png"
LOGO_W = "https://dentora-clinic.hr/wp-content/uploads/2026/05/logo_bijeli.png"
PORTRAIT = "https://dentora-clinic.hr/wp-content/uploads/email/dentora-email-dr-bozana-portret-240.jpg"
MAPS = "https://www.google.com/maps/search/?api=1&amp;query=Dentora+Clinic+Dankove%C4%8Dka+6+Zagreb"
SANS = "Arial, Helvetica, sans-serif"
SERIF = "Georgia, 'Times New Roman', serif"
INK, TEXT, MUTED, GOLD, GOLD_TXT, LINE = "#2b2520", "#4f4a43", "#6f655b", "#8a6a3f", "#7f5e33", "#e6dccb"


def head(title, bg):
    return f'''<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="hr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="x-apple-disable-message-reformatting" />
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
<meta name="color-scheme" content="light" />
<meta name="supported-color-schemes" content="light" />
<title>{title}</title>
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<style type="text/css">body, table, td, p, a, span {{ font-family: Arial, Helvetica, sans-serif !important; }} .serif {{ font-family: Georgia, 'Times New Roman', serif !important; }}</style>
<![endif]-->
<style type="text/css">
  body {{ margin:0 !important; padding:0 !important; width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }}
  table, td {{ border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }}
  img {{ border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; display:block; }}
  a {{ text-decoration:none; }}
  a[x-apple-data-detectors] {{ color:inherit !important; text-decoration:none !important; font-size:inherit !important; font-family:inherit !important; font-weight:inherit !important; line-height:inherit !important; }}
  u + #body a {{ color:inherit; text-decoration:none; font-size:inherit; font-family:inherit; font-weight:inherit; line-height:inherit; }}
  #MessageViewBody a {{ color:inherit; text-decoration:none; font-size:inherit; font-family:inherit; font-weight:inherit; line-height:inherit; }}
  @media only screen and (max-width:620px) {{
    .container {{ width:100% !important; }}
    .px {{ padding-left:24px !important; padding-right:24px !important; }}
    .h1 {{ font-size:30px !important; line-height:36px !important; }}
    .stack {{ display:block !important; width:100% !important; }}
    .stack-pad {{ padding:0 0 16px 0 !important; }}
    .btn-full {{ width:100% !important; max-width:100% !important; margin-right:0 !important; }}
  }}
</style>
</head>
<body id="body" style="margin:0; padding:0; background-color:{bg};">'''


def preheader(text):
    pad = "&#847;&zwnj;&nbsp;" * 14
    return f'''
<div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden; mso-hide:all;">{text} {pad}</div>'''


def shell_open(bg, card, pad="32px 12px"):
    return f'''
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{bg};">
  <tr>
    <td align="center" style="padding:{pad};">
      <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" align="center"><tr><td><![endif]-->
      <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px; background-color:{card};">'''


SHELL_CLOSE = '''
      </table>
      <!--[if mso]></td></tr></table><![endif]-->
    </td>
  </tr>
</table>
</body>
</html>
'''


def button(label, href, primary=True, width=240, align_margin="0 12px 12px 0"):
    """Bulletproof button: VML for Outlook on Windows, fluid inline-block elsewhere."""
    fill = GOLD if primary else "#fffdf9"
    color = "#ffffff" if primary else GOLD_TXT
    stroke = 'stroke="f"' if primary else f'strokecolor="{GOLD}" strokeweight="1px"'
    target = '' if href.startswith(('tel:', 'mailto:')) else ' target="_blank"'
    return f'''<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{href}" style="height:50px;v-text-anchor:middle;width:{width}px;" arcsize="50%" {stroke} fillcolor="{fill}"><w:anchorlock/><center style="color:{color};font-family:Arial,sans-serif;font-size:15px;font-weight:bold;">{label}</center></v:roundrect>
<![endif]--><!--[if !mso]><!--><a class="btn-full" href="{href}"{target} style="display:inline-block; box-sizing:border-box; width:100%; max-width:{width}px; margin:{align_margin}; background-color:{fill}; border:1px solid {GOLD}; color:{color}; font-family:{SANS}; font-size:15px; font-weight:bold; line-height:48px; text-align:center; text-decoration:none; border-radius:25px; -webkit-text-size-adjust:none;">{label}</a><!--<![endif]-->'''


def p(text, size=16, lh=26, color=TEXT, mb=16, extra=""):
    return f'<p style="margin:0 0 {mb}px 0; font-family:{SANS}; font-size:{size}px; line-height:{lh}px; color:{color};{extra}">{text}</p>'


def eyebrow(text, color=GOLD_TXT, mb=14):
    return f'<p style="margin:0 0 {mb}px 0; font-family:{SANS}; font-size:11px; line-height:16px; letter-spacing:2px; text-transform:uppercase; color:{color}; font-weight:bold;">{text}</p>'


def summary_rows(rows, label_w=120, bg=None, divider=LINE):
    out = []
    for i, (k, v, bold) in enumerate(rows):
        last = i == len(rows) - 1
        border = "" if last else f" border-bottom:1px solid {divider};"
        pb = "10px 0 0 0" if last else "10px 0"
        weight = " font-weight:bold;" if bold else ""
        brk = " overflow-wrap:anywhere;" if k == "E-mail" else ""
        out.append(f'''<tr>
  <td width="{label_w}" valign="top" style="padding:{pb};{border} font-family:{SANS}; font-size:14px; line-height:20px; color:{MUTED};">{k}</td>
  <td valign="top" style="padding:{pb};{border} font-family:{SANS}; font-size:15px; line-height:22px; color:{INK};{weight}{brk}">{v}</td>
</tr>''')
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' + "\n".join(out) + "</table>"


ROWS = [("Ime i prezime", "{{ime_prezime}}", True), ("Telefon", "{{telefon}}", True), ("E-mail", "{{email}}", True),
        ("Tretman", "{{tretman}}", True), ("Poruka", "{{poruka}}", False)]
FOOT_NOTE = f'Ovu poruku primili ste jer ste ispunili kontaktni obrazac na <a href="https://dentora-clinic.hr/" target="_blank" style="color:{GOLD_TXT}; text-decoration:underline;">dentora-clinic.hr</a>.'
PRIVACY = f'<a href="https://dentora-clinic.hr/politika-privatnosti-2/" target="_blank" style="color:{GOLD_TXT}; text-decoration:underline;">Politika privatnosti</a>'


# ---------------------------------------------------------------- A: Minimal
def minimal():
    h = head("Zaprimili smo vaš upit – Dentora Clinic", "#f7f3ec")
    h += preheader("Hvala, {{ime_prezime}}. Javit ćemo vam se uskoro radi dogovora termina.")
    h += shell_open("#f7f3ec", "#ffffff", "48px 12px")
    h += f'''
        <tr><td class="px" style="padding:48px 56px 0 56px;">
          <img src="{LOGO}" width="150" alt="DENTORA Clinic" style="display:block; width:150px; max-width:150px; height:auto; border:0; font-family:{SERIF}; font-size:22px; color:{GOLD};" />
        </td></tr>
        <tr><td class="px" style="padding:56px 56px 0 56px;">
          <h1 class="h1 serif" style="margin:0 0 24px 0; font-family:{SERIF}; font-size:34px; line-height:40px; font-weight:normal; color:{INK};">Hvala vam, {{{{ime_prezime}}}}.</h1>
          {p("Zaprimili smo vaš upit. Javit ćemo vam se telefonom tijekom radnog vremena kako bismo dogovorili termin koji vam odgovara.", mb=0)}
        </td></tr>
        <tr><td class="px" style="padding:40px 56px 0 56px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="border-top:1px solid {LINE}; font-size:0; line-height:0;">&nbsp;</td></tr></table>
        </td></tr>
        <tr><td class="px" style="padding:24px 56px 0 56px;">
          {summary_rows([("Tretman", "{{tretman}}", True), ("Telefon", "{{telefon}}", False), ("Poruka", "{{poruka}}", False)], label_w=110, divider="#f0ebe2")}
        </td></tr>
        <tr><td class="px" style="padding:40px 56px 0 56px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="border-top:1px solid {LINE}; font-size:0; line-height:0;">&nbsp;</td></tr></table>
        </td></tr>
        <tr><td class="px" style="padding:32px 56px 0 56px;">
          {p(f'Hitno je ili trebate nešto promijeniti? Nazovite nas na <a href="tel:+385919487770" style="color:{GOLD_TXT}; text-decoration:underline; font-weight:bold;">091 948 7770</a> ili odgovorite na ovaj e-mail.', size=15, lh=24, mb=32)}
          <p class="serif" style="margin:0; font-family:{SERIF}; font-size:18px; line-height:26px; font-style:italic; color:{INK};">Božana Jelić Rubil</p>
          {p("dr. med. dent. · Dentora Clinic", size=13, lh=20, color=MUTED, mb=0)}
        </td></tr>
        <tr><td class="px" style="padding:56px 56px 48px 56px;">
          {p("Dankovečka ul. 6, 10040 Zagreb · pon – pet, 08 – 20 h", size=12, lh=18, color=MUTED, mb=6)}
          {p(FOOT_NOTE + " · " + PRIVACY, size=12, lh=18, color=MUTED, mb=0)}
        </td></tr>'''
    return h + SHELL_CLOSE


# ---------------------------------------------------------------- B: Personal letter from the doctor
def letter():
    h = head("Poruka dr. Božane – Dentora Clinic", "#efe7da")
    h += preheader("{{ime_prezime}}, hvala na povjerenju. Uskoro vas zovemo radi termina.")
    h += shell_open("#efe7da", "#fffdf9")
    h += f'''
        <tr><td align="center" style="padding:28px 40px 24px 40px; border-bottom:1px solid {LINE};" class="px">
          <a href="https://dentora-clinic.hr/" target="_blank"><img src="{LOGO}" width="170" alt="DENTORA Clinic" style="display:block; width:170px; max-width:170px; height:auto; border:0; font-family:{SERIF}; font-size:24px; color:{GOLD};" /></a>
        </td></tr>
        <tr><td class="px" style="padding:44px 56px 0 56px;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
            <td valign="middle" style="padding:0 18px 0 0;">
              <img src="{PORTRAIT}" width="72" height="72" alt="Dr. Božana Jelić Rubil" style="display:block; width:72px; height:72px; border-radius:36px; border:0; background-color:#e9e3d9; font-family:{SANS}; font-size:10px; color:{MUTED};" />
            </td>
            <td valign="middle">
              <p class="serif" style="margin:0; font-family:{SERIF}; font-size:19px; line-height:24px; color:{INK};">Božana Jelić Rubil</p>
              {p("dr. med. dent. · osnivačica Dentora Clinic", size=13, lh=20, color=MUTED, mb=0)}
            </td>
          </tr></table>
        </td></tr>
        <tr><td class="px" style="padding:36px 56px 0 56px;">
          <h1 class="h1 serif" style="margin:0 0 22px 0; font-family:{SERIF}; font-size:32px; line-height:40px; font-weight:normal; color:{INK};">Dobar dan, {{{{ime_prezime}}}},</h1>
          {p("hvala vam što ste se javili Dentora Clinic. Vaš upit za <strong style=\"color:" + INK + ";\">{{tretman}}</strong> je stigao do nas i osobno ću ga pogledati prije našeg razgovora.", size=17, lh=28)}
          {p("Netko iz mog tima nazvat će vas tijekom radnog vremena kako bismo dogovorili termin. Na prvom posjetu imat ćemo dovoljno vremena za pregled i razgovor, bez žurbe i bez obveze. Zajedno ćemo vidjeti što je najbolje za vaš osmijeh.", size=17, lh=28)}
          {p("Ako u međuvremenu imate pitanja, slobodno odgovorite na ovaj e-mail.", size=17, lh=28, mb=28)}
          <p class="serif" style="margin:0 0 2px 0; font-family:{SERIF}; font-size:24px; line-height:30px; font-style:italic; color:{GOLD_TXT};">Srdačno,</p>
          <p class="serif" style="margin:0; font-family:{SERIF}; font-size:24px; line-height:30px; font-style:italic; color:{GOLD_TXT};">Božana</p>
        </td></tr>
        <tr><td class="px" style="padding:36px 56px 0 56px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f7f3ec; border-left:3px solid {GOLD};">
            <tr><td style="padding:18px 18px 20px 18px;">
              {eyebrow("Vaš upit", color=MUTED, mb=6)}
              {summary_rows(ROWS, label_w=104)}
            </td></tr>
          </table>
        </td></tr>
        <tr><td class="px" style="padding:32px 56px 40px 56px; font-size:0; line-height:0;">
          {button("Nazovite: 091 948 7770", "tel:+385919487770", width=232)}{button("Kako do nas", MAPS, primary=False, width=232, align_margin="0 0 12px 0")}
        </td></tr>
        <tr><td class="px" align="center" style="padding:24px 56px 28px 56px; background-color:#f7f3ec; border-top:1px solid {LINE};">
          {p("Dentora Clinic · Dankovečka ul. 6, 10040 Zagreb · pon – pet, 08 – 20 h", size=12, lh=18, color=MUTED, mb=6, extra=" text-align:center;")}
          {p(FOOT_NOTE + " · " + PRIVACY, size=12, lh=18, color=MUTED, mb=0, extra=" text-align:center;")}
        </td></tr>'''
    return h + SHELL_CLOSE


# ---------------------------------------------------------------- C: Editorial with "before your visit"
def editorial():
    tips = [("Ponesite snimke", "Ako imate starije RTG snimke ili nalaze, ponesite ih na pregled."),
            ("Popis lijekova", "Zapišite lijekove koje redovito uzimate i eventualne alergije."),
            ("Dođite par minuta ranije", "Tako u miru stignete prije početka pregleda.")]
    tip_rows = "".join(f'''<tr>
      <td width="28" valign="top" style="padding:0 0 18px 0; font-family:{SERIF}; font-size:20px; line-height:24px; color:{GOLD_TXT};">{i+1}.</td>
      <td valign="top" style="padding:0 0 18px 0; font-family:{SANS}; font-size:15px; line-height:23px; color:{TEXT};"><strong style="color:{INK};">{t}</strong><br />{d}</td>
    </tr>''' for i, (t, d) in enumerate(tips))
    h = head("Vaš upit je zaprimljen – Dentora Clinic", "#2b2520")
    h += preheader("{{ime_prezime}}, upit za {{tretman}} je zaprimljen. Evo što slijedi i kako se pripremiti.")
    h += shell_open("#2b2520", "#fffdf9")
    h += f'''
        <tr><td style="background-color:#2b2520; padding:36px 48px 40px 48px;" class="px">
          <a href="https://dentora-clinic.hr/" target="_blank"><img src="{LOGO_W}" width="150" alt="DENTORA Clinic" style="display:block; width:150px; max-width:150px; height:auto; border:0; font-family:{SERIF}; font-size:22px; color:#d9b97f;" /></a>
          <p style="margin:44px 0 0 0; font-family:{SANS}; font-size:11px; line-height:16px; letter-spacing:2px; text-transform:uppercase; color:#d9b97f; font-weight:bold;">Upit zaprimljen</p>
          <h1 class="h1 serif" style="margin:12px 0 16px 0; font-family:{SERIF}; font-size:40px; line-height:46px; font-weight:normal; color:#ffffff;">Vidimo se uskoro,<br /><em style="color:#d9b97f;">{{{{ime_prezime}}}}.</em></h1>
          <p style="margin:0; font-family:{SANS}; font-size:16px; line-height:26px; color:#c9bfb2;">Vaš upit je kod nas. Javit ćemo vam se telefonom tijekom radnog vremena i zajedno dogovoriti termin.</p>
        </td></tr>
        <tr><td style="background-color:{GOLD}; font-size:0; line-height:0; height:3px;">&nbsp;</td></tr>
        <tr><td class="px" style="padding:40px 48px 0 48px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
            <td class="stack stack-pad" width="50%" valign="top" style="padding:0 16px 0 0;">
              {eyebrow("Tretman", color=MUTED, mb=6)}
              <p class="serif" style="margin:0; font-family:{SERIF}; font-size:24px; line-height:30px; color:{INK};">{{{{tretman}}}}</p>
            </td>
            <td class="stack" width="50%" valign="top">
              {eyebrow("Javljamo se na", color=MUTED, mb=6)}
              <p class="serif" style="margin:0; font-family:{SERIF}; font-size:24px; line-height:30px; color:{INK};">{{{{telefon}}}}</p>
            </td>
          </tr></table>
        </td></tr>
        <tr><td class="px" style="padding:28px 48px 0 48px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid {LINE}; border-bottom:1px solid {LINE};">
            <tr><td style="padding:16px 0 18px 0;">
              {summary_rows([("Ime i prezime", "{{ime_prezime}}", False), ("E-mail", "{{email}}", False), ("Poruka", "{{poruka}}", False)], label_w=120, divider="#f0ebe2")}
            </td></tr>
          </table>
        </td></tr>
        <tr><td class="px" style="padding:40px 48px 0 48px;">
          {eyebrow("Prije prvog posjeta")}
          <h2 class="serif" style="margin:0 0 22px 0; font-family:{SERIF}; font-size:28px; line-height:34px; font-weight:normal; color:{INK};">Kako se pripremiti</h2>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">{tip_rows}</table>
        </td></tr>
        <tr><td class="px" style="padding:20px 48px 44px 48px; font-size:0; line-height:0;">
          {button("Nazovite: 091 948 7770", "tel:+385919487770")}{button("Kako do nas", MAPS, primary=False, align_margin="0 0 12px 0")}
        </td></tr>
        <tr><td style="background-color:#f7f3ec; padding:28px 48px; border-top:1px solid {LINE};" class="px">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
            <td class="stack stack-pad" width="50%" valign="top" style="padding:0 16px 0 0;">
              {eyebrow("Dentora Clinic", color=GOLD_TXT, mb=6)}
              {p(f'Dankovečka ul. 6, 10040 Zagreb<br /><a href="tel:+385919487770" style="color:{INK}; text-decoration:none;">091 948 7770</a><br /><a href="mailto:info@dentora-clinic.hr" style="color:{INK}; text-decoration:none; overflow-wrap:anywhere;">info@dentora-clinic.hr</a>', size=14, lh=22, color=INK, mb=0)}
            </td>
            <td class="stack" width="50%" valign="top">
              {eyebrow("Radno vrijeme", color=GOLD_TXT, mb=6)}
              {p("pon – pet: 08 – 20 h<br />sub – ned: zatvoreno", size=14, lh=22, color=INK, mb=0)}
            </td>
          </tr></table>
        </td></tr>
        <tr><td class="px" align="center" style="padding:20px 48px 26px 48px; background-color:#f7f3ec;">
          {p(FOOT_NOTE + " · " + PRIVACY, size=12, lh=18, color=MUTED, mb=0, extra=" text-align:center;")}
        </td></tr>'''
    return h + SHELL_CLOSE


SAMPLE = {"{{ime_prezime}}": "Ana Horvat", "{{telefon}}": "091 123 4567", "{{email}}": "ana.horvat@gmail.com",
          "{{tretman}}": "Prozirni aligneri", "{{poruka}}": "Zanima me terapija alignerima, imam blago iskrivljene donje zube. Mogu dolaziti poslijepodne."}

for name, fn in [("dentora-email-A-minimal", minimal), ("dentora-email-B-pismo-doktorice", letter), ("dentora-email-C-editorial", editorial)]:
    doc = fn()
    (OUT / f"{name}.html").write_text(doc, encoding="utf-8")
    prev = doc.replace(LOGO_W, "slike/logo_bijeli.png").replace(LOGO, "slike/logo_zlatni.png").replace(PORTRAIT, "slike/dentora-email-dr-bozana-portret-240.jpg")
    for k, v in SAMPLE.items():
        prev = prev.replace(k, v)
    (OUT / f"preview-{name}.html").write_text(prev, encoding="utf-8")
    print(name, len(doc.encode()) // 1024, "KB", doc.count("{{"))
