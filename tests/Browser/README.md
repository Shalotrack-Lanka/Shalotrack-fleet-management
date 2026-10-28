# F5 - real-browser XSS check

Loads 15 portal pages in headless Chromium against a fake API that answers with attack strings, clicks every handler,
and exits with code 1 if attacker JavaScript ever runs. Local only: no database, no real API, no Google Maps.

Setup once:  `pip install playwright` then `playwright install chromium`  (PHP must be on PATH, `composer install` done)
Run:         `python tests/Browser/xss_crawl.py`   (about 35 seconds)

Not part of `php artisan test`. Run it before releases, or add it to CI as a separate job.
