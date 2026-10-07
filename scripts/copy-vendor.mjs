// Copies third-party browser bundles out of node_modules into public/build/vendor
// so pages load them from our own origin instead of a public CDN.
//
//  - Versions are pinned in package.json / package-lock.json (Dependabot-able),
//    and the version is part of the file name, so nginx's 1-year cache on
//    /build/ can never serve a stale copy after an upgrade.
//  - Must run AFTER `vite build` (Vite empties public/build first).
//  - Blade references these via vendor_asset('signalr') / vendor_asset('chart')
//    (app/helpers or AppServiceProvider), never by hard-coded path.
import { copyFileSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { createHash } from 'node:crypto';

const out = 'public/build/vendor';
mkdirSync(out, { recursive: true });

const pkgVersion = (name) =>
    JSON.parse(readFileSync(`node_modules/${name}/package.json`, 'utf8')).version;

const files = {
    signalr: { pkg: '@microsoft/signalr', src: 'node_modules/@microsoft/signalr/dist/browser/signalr.min.js', ext: 'min.js' },
    chart:   { pkg: 'chart.js',            src: 'node_modules/chart.js/dist/chart.umd.js',                     ext: 'umd.js' },
};

const manifest = {};
for (const [key, f] of Object.entries(files)) {
    const version = pkgVersion(f.pkg);
    const name = `${key}-${version}.${f.ext}`;
    copyFileSync(f.src, `${out}/${name}`);
    const sri = 'sha384-' + createHash('sha384').update(readFileSync(f.src)).digest('base64');
    manifest[key] = { file: `build/vendor/${name}`, integrity: sri };
    console.log(`vendor: ${key} ${version} -> ${out}/${name}`);
}
writeFileSync(`${out}/manifest.json`, JSON.stringify(manifest, null, 2));
