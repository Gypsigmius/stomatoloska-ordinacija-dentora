"""Builds the Dentora website from src/index.template.html.

  python site/build.py            -> site/dist/dentora-demo/  (upload this folder to the server)
                                     site/dist/dentora-demo.zip
  python site/build.py --single   -> also site/dist/dentora-single-file.html (images embedded, for previews)

Images are referenced in the template as {{file-name.ext}} and must exist in site/img/.
"""
import base64, pathlib, re, shutil, sys, zipfile

ROOT = pathlib.Path(__file__).resolve().parent
SRC = ROOT / "src" / "index.template.html"
IMG = ROOT / "img"
DIST = ROOT / "dist"
OUT = DIST / "dentora-demo"
TAG = re.compile(r"\{\{([\w.-]+)\}\}")
API = "/wp-json/dentora/v1/upit"  # endpoint of the "Dentora upiti" WordPress plugin

HEAD = (
    '<!doctype html>\n<html lang="hr">\n<head>\n<meta charset="utf-8">\n'
    '<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">\n'
    '<meta name="robots" content="noindex,nofollow">\n'
    '<link rel="icon" href="img/logo_zlatni.png">\n'
    '<!-- Form endpoint of the "Dentora upiti" plugin. If WordPress runs on another domain, put the full URL here. -->\n'
    f'<script>window.DENTORA_API="{API}";</script>\n'
)


def wrap(body_html: str) -> str:
    i = body_html.index("<header>")
    head = body_html[:i].replace('<meta name="claude:viewport-fit-sides" content="cover">\n', "")
    return HEAD + head + "</head>\n<body>\n" + body_html[i:] + "\n</body>\n</html>\n"


def main() -> None:
    tpl = SRC.read_text(encoding="utf-8")
    used = sorted(set(TAG.findall(tpl)))
    missing = [u for u in used if not (IMG / u).exists()]
    if missing:
        sys.exit(f"Missing images in site/img: {missing}")

    if OUT.exists():
        shutil.rmtree(OUT)
    (OUT / "img").mkdir(parents=True)
    for u in used:
        shutil.copy2(IMG / u, OUT / "img" / u)
    (OUT / "index.html").write_text(wrap(TAG.sub(lambda m: "img/" + m.group(1), tpl)), encoding="utf-8")
    (OUT / "README.txt").write_text(
        "Dentora Clinic – web stranica\n\n"
        "Uploadajte mapu dentora-demo u korijen WordPressa (pored wp-admin, wp-content).\n"
        "Uz plugin 'Dentora upiti' stranica se prikazuje na naslovnici, a obrasci šalju upite.\n",
        encoding="utf-8",
    )
    with zipfile.ZipFile(DIST / "dentora-demo.zip", "w", zipfile.ZIP_DEFLATED) as z:
        for f in sorted(OUT.rglob("*")):
            z.write(f, f.relative_to(DIST))
    print(f"built {OUT} ({len(used)} images) and {DIST / 'dentora-demo.zip'}")

    if "--single" in sys.argv:
        mime = {".png": "image/png", ".avif": "image/avif", ".webp": "image/webp", ".jpg": "image/jpeg"}
        single = TAG.sub(
            lambda m: f"data:{mime[pathlib.Path(m.group(1)).suffix]};base64,"
            + base64.b64encode((IMG / m.group(1)).read_bytes()).decode(),
            tpl,
        )
        (DIST / "dentora-single-file.html").write_text(single, encoding="utf-8")
        print("built", DIST / "dentora-single-file.html")


if __name__ == "__main__":
    main()
