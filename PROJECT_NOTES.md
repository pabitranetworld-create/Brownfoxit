# Project notes (for continuing development)

Read this before adding a new page or feature, so everything stays consistent.

## Stack
- Laravel 12, PHP 8.2+, MySQL (cPanel shared hosting). No Node/Vite build step:
  CSS/JS are plain files in `public/assets`, libraries are copied into `public/vendor`
  (Swiper 11, Font Awesome 6 Free, Inter Tight + Lato fonts).
- Sessions, cache and queue use the database driver (works on shared hosting, no Redis).

## Install & deploy
- `public/index.php` shows `installer/installer.php` (plain PHP, runs before Laravel boots) until
  `storage/installed.json` exists. The installer writes `.env`, runs migrate + seed, creates the admin
  from the form, downloads images in batches (`App\Support\ImageSync`), then writes `installed.json`.
- `.github/workflows/release.yml` builds `brownfoxit-install.zip` (with vendor/) for fresh installs.
- `.github/workflows/deploy.yml`: push to `main` → composer install → FTP upload of changed files
  (never `.env`, `storage/`, `public/uploads/`) → `POST /_deploy` with `X-Deploy-Token`
  (`DeployController`: `migrate --force`, `optimize:clear`, `optimize`).
- Because the hook runs `optimize`, config is cached in production: never call `env()` outside
  `config/*.php`; add new settings to a config file instead.
- Workflow for changes: commit to `main` = deploy. Every schema change must be a new migration
  (never edit an old one that has already run on the server).

## Content model
| Kind of content | Where it lives | Read it with |
|---|---|---|
| Lists that will get admin CRUD (services, clients, projects, testimonials, posts, post categories) | own tables + models in `app/Models` | `Model::active()->ordered()` (trait `Concerns\Sortable`) |
| Single blocks / small lists (hero, about + skills, counters, steps, CTA, menu, footer, site contact info, social links) | `settings` table, one JSON value per key | `setting('hero.title')` helper (cached, cache cleared on save) |

Keys in `settings`: `site`, `menu`, `hero`, `about`, `service_cards`, `marketplace`, `counters`,
`projects_section`, `testimonials_section`, `steps`, `cta`, `blog_section`, `footer`
(see `database/seeders/SettingsSeeder.php` for the exact shape).

## Conventions
- **Images:** store paths relative to `public/`, e.g. `uploads/2026/05/photo.jpg`, and output them with
  `media($path)`. `media()` also accepts full URLs. Admin uploads will go to the `public` disk
  (`storage/...` after `php artisan storage:link`), and `media()` handles those paths too.
- **Links** saved in settings/menus: output with `link_to_url($url)` ("/#about", "/blog", "https://…").
- **New public page:** route in `routes/web.php` → controller in `app/Http/Controllers` → view that
  `@extends('layouts.app')` and fills `@section('content')`. Set `@section('title')` /
  `@section('meta_description')` for SEO. Page-specific CSS/JS via `@push('styles')` / `@push('scripts')`.
- **Section partials** go in `resources/views/<page>/sections/`. Reusable bits go in `resources/views/partials/`.
- **Animations** available to any markup:
  - `.reveal` (+ `data-anim="fade-left|fade-right"`, `data-delay="ms"`) for scroll-in effects
  - `.text-fill` headings that light up as you scroll
  - `.has-particles` + `<canvas class="particles">` + `data-number/size/random/colors` for the floating dots
  - `.count[data-to]` for counters, `.progress-item[data-value]` for progress bars
  - `[data-video="YouTubeID"]` opens the video popup
- Seeders are idempotent (`updateOrCreate`); re-running `php artisan db:seed` is safe and never resets
  the admin password.
- After adding images anywhere (views, seeders, DB), `php artisan site:download-images` picks them up
  automatically (it scans for `uploads/....ext`).

## Roadmap (to be built step by step)
- [x] Home page (design + database-driven content)
- [x] Web installer + GitHub auto-deploy
- [ ] Admin panel: login, dashboard, CRUD for every table + settings editor + image upload
- [ ] About, Services (+ detail), Portfolio (+ detail), Blog (list, category, post), Contact (form → DB + email)
- [ ] Shop: products, cart, checkout, orders, customer account (WooCommerce replacement)
- [ ] SEO: sitemap.xml, meta per page, 301 redirects from old WordPress URLs
