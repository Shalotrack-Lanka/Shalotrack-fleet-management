"""Real-browser XSS check for the Fleet portal (F5).

Starts a fake API that answers with HOSTILE strings, starts the portal against it, loads every page in headless
Chromium and clicks every inline / delegated handler. Fails (exit 1) if attacker JavaScript ever runs.
No database, no real API, no Google Maps, no network: everything is local and thrown away.

One-time setup:   pip install playwright   then   playwright install chromium
Run from the repo root:   python tests/Browser/xss_crawl.py
"""
import base64, os, subprocess, sys, time, urllib.request
from pathlib import Path
from playwright.sync_api import sync_playwright

D = str(Path(__file__).resolve().parent)
REPO = str(Path(D).parent.parent)
API_PORT, WEB_PORT = 9001, 8099
base, label = f"http://127.0.0.1:{WEB_PORT}", "FLEET"
PHP = os.environ.get("PHP", "php")
ENV = dict(os.environ, APP_ENV="local", APP_DEBUG="false", APP_KEY="base64:" + base64.b64encode(bytes(32)).decode(),
           SESSION_DRIVER="file", SESSION_ENCRYPT="false", SESSION_COOKIE="shalotrack_fleet_session", SESSION_SECURE_COOKIE="false",
           CACHE_STORE="array", LOG_CHANNEL="null", LOG_STACK="null",
           SHALOTRACK_API_BASE_URL=f"http://127.0.0.1:{API_PORT}", FIREBASE_PROJECT_ID="proj", FIREBASE_API_KEY="k",
           DB_CONNECTION="sqlite", DB_DATABASE=":memory:")
SERVER_PHP = os.path.join(REPO, "public", "index.php")

def fresh_cookie():
    return subprocess.run([PHP, os.path.join(D, "make_cookie.php")], cwd=REPO, env=ENV, capture_output=True, text=True).stdout.strip()

procs = [subprocess.Popen([sys.executable, "-I", os.path.join(D, "mock_api.py"), str(API_PORT)]),
         subprocess.Popen([PHP, "-S", f"127.0.0.1:{WEB_PORT}", "-t", os.path.join(REPO, "public"), SERVER_PHP], cwd=REPO, env=ENV,
                          stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)]
for _ in range(50):
    try:
        urllib.request.urlopen(base + "/login", timeout=2); break
    except Exception:
        time.sleep(0.3)
else:
    [p.kill() for p in procs]; sys.exit("portal did not start")
V1 = "11111111-1111-4111-8111-111111111111"
TOKEN = "AbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_AbCde"
STUB = open(D + "/gmaps_stub.js", encoding="utf-8").read()
PAGES = (sys.argv[3].split(",") if len(sys.argv) > 3 else ["/dashboard", "/vehicles", f"/vehicles/{V1}", "/geofences", "/saved-places", "/sharing", "/emergency-contacts", "/renewals",
         "/stats", "/reports", "/trips", "/complaints", "/profile", "/places", f"/live/{TOKEN}"])
SEL = "[onclick],[onkeydown],[onchange],[oninput],[onsubmit],[data-act],[data-share-id],[data-id],[data-plate],[data-name]"

def blocked_or_stub(route):
    u = route.request.url
    if "/logout" in u: return route.abort()           # a click on "Sign out" must not destroy the session we are testing with
    if u.startswith(base): return route.continue_()
    if "maps.googleapis.com/maps/api/js" in u: return route.fulfill(status=200, content_type="application/javascript", body=STUB)
    return route.abort()

def ev(pg, js, arg=None, default=None):
    """pg.evaluate that survives the page navigating while we read it (a click may legitimately change page)."""
    for _ in range(4):
        try:
            return pg.evaluate(js, arg) if arg is not None else pg.evaluate(js)
        except Exception as e:
            if "context was destroyed" not in str(e) and "navigation" not in str(e).lower():
                raise
            try: pg.wait_for_load_state("load", timeout=10000)
            except Exception: pass
            pg.wait_for_timeout(300)
    return default

rows, total_exec = [], 0
with sync_playwright() as pw:
    b = pw.chromium.launch()
    for path in PAGES:
        ctx = b.new_context(viewport={"width": 1280, "height": 900})
        if not path.startswith("/live/"):
            ctx.add_cookies([{"name": "shalotrack_fleet_session", "value": fresh_cookie(), "url": base}])
        ctx.route("**/*", blocked_or_stub)
        hits = []
        ctx.expose_function("__pwnReport", lambda v: hits.append(v))
        ctx.add_init_script("""(() => { let v = 0; Object.defineProperty(window, '__pwn', { configurable: true,
            get() { return v; }, set(x) { v = x; try { window.__pwnReport(x); } catch (e) {} } }); })();""")
        pg = ctx.new_page(); pg.set_default_timeout(8000); pg.set_default_navigation_timeout(15000)
        dialogs = []
        pg.on("dialog", lambda d: (dialogs.append(d.message), d.dismiss()))
        resp = pg.goto(base + path, wait_until="load"); pg.wait_for_timeout(900)
        landed = pg.url.replace(base, "") or "/"
        status = resp.status if resp else None
        on_load = len(hits)
        # the payload must actually be on the page, otherwise "nothing ran" proves nothing
        shown = ev(pg, """() => { const h = document.documentElement.innerHTML; return h.includes('__pwn'); }""", default=False)
        n = clicked = 0
        for _pass in range(2):   # second pass also reaches elements that the first clicks created (modals, re-rendered lists)
            r = ev(pg, """(sel) => { const els = [...document.querySelectorAll(sel)].slice(0, 400); let ok = 0;
                for (const e of els) { try {
                    e.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}));
                    if (e.hasAttribute('onkeydown')) e.dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter', bubbles: true}));
                    ok++; } catch (err) {} }
                return [els.length, ok]; }""", SEL, default=[0, 0])
            n += r[0]; clicked += r[1]
            pg.wait_for_timeout(250)
        pg.wait_for_timeout(400)
        after = len(hits)
        imgs = ev(pg, "document.querySelectorAll('img[src=x]').length", default=0)
        scripts = ev(pg, "[...document.scripts].filter(s => s.textContent.includes('__pwn=(window.__pwn')).length")
        ran = bool(on_load or after or imgs)   # a native confirm() that PRINTS the text is the page working correctly, not an attack
        total_exec += ran
        if dialogs: print(f"[{label}] page's own confirm()/alert() on {path} (text shown, not run): {[d[:60] for d in dialogs[:2]]}")
        rows.append((path.replace(V1, "{id}").replace(TOKEN, "{token}"), landed.replace(V1, "{id}").replace(TOKEN, "{token}"), status, shown, n, clicked, after, imgs, ran))
        ctx.close()
    b.close()

print(f"\n[{label}] {'page':20s} {'landed':20s} http  payload_on_page  handlers  clicked  __pwn  injected_img  ATTACKER_JS_RAN")
for r in rows:
    print(f"[{label}] {r[0]:20s} {r[1]:20s} {r[2]}   {str(r[3]):14s} {r[4]:8d}  {r[5]:7d}  {r[6]!s:5s}  {r[7]:12d}  {r[8]}")
print(f"\n[{label}] RESULT: attacker JS executed on {total_exec} of {len(rows)} pages; pages that actually carried the payload: {sum(1 for r in rows if r[3])}")

[p.kill() for p in procs]
sys.exit(1 if total_exec else 0)