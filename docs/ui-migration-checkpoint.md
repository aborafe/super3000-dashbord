UI Migration Checkpoint
Date: 2026-02-06

Phase: A (Sneat foundation) — complete

Done:
- Sneat assets are present under public/admin/assets.
- Admin layout uses Sneat vendor CSS/JS and admin partials.
- Admin dashboard renders using the Sneat layout.
- Locale route parameter is removed before controller dispatch to avoid model binding errors.
- Vite build succeeds and generates public/build/manifest.json.
- Sneat base layout now matches the original structure (core + theme + helpers/menu + bootstrap bundle).
- RTL/LTR + localStorage dark mode + language switch are wired in the admin navbar.

Remaining (Phase 2):
- Migrate Products module views to Sneat styling (in progress).

Notes:
- Vite scripts use `--configLoader native`, with `resolve.preserveSymlinks` and `optimizeDeps.noDiscovery` to avoid Windows spawn issues.
- If Vite fails with "spawn EPERM" on Windows, run from a non-protected path or whitelist node/esbuild in Windows Security.
- `theme-default.css` and RTL styles should be replaced with the official Sneat files if you want exact theme parity.

How to run (PowerShell):
- php artisan serve
- npm run dev

How to run (CMD):
- php artisan serve
- npm run dev
