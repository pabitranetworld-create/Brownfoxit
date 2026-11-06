# Progress checklist

Status of every requirement in the project specification. Updated at the end of each phase.

**Legend:** ✅ done and tested automatically (CI) · 🟡 partly done · ⬜ not started · ⛔ blocked (waiting for something from the owner)

Every push runs `tests/e2e-install.sh` on a fresh server with MySQL 8. It covers the installer, the home page, the page builder with all widgets, the media library, the admin panel and the deploy hooks. A deploy only happens when that test passes.

---

## Phase 1 – CMS & page builder (this phase)

| Requirement | Status | Notes |
|---|---|---|
| Create unlimited pages, edit title / slug / SEO / social image | ✅ | Admin → Pages |
| Sections with 1–6 columns, presets, widths per desktop / tablet / mobile | ✅ | |
| Nested layouts | ✅ | "Nested columns" widget, one level deep (deeper nesting is refused by the validator) |
| Add / delete / duplicate / reorder sections, columns, widgets | ✅ | Structure tab: drag & drop and ↑↓ buttons |
| Backgrounds (colour, gradient, image + overlay, animated), spacing, alignment, width, box styles | ✅ | |
| Typography choices | 🟡 | Heading size / colour / tag presets and text size are implemented. Free font-family and pixel font-size controls are **not** offered, to keep the brand consistent. |
| Borders | 🟡 | Column box styles (card, glass, animated glow border) and image corner radius only. No free border editor. |
| Media library with alt text and captions, picker in the builder and in admin forms | ✅ | Upload, search, edit alt / caption, delete. "Add old images" imports files already in `public/uploads`. |
| Responsive layout settings, hide per device | ✅ | |
| Animation controls that change the frontend | ✅ | Entrance type, duration, delay, stagger, intensity, hover, parallax, mobile behaviour, reduced-motion behaviour. The test checks that each setting reaches the HTML. |
| Save draft, live preview, preview in a new tab, publish / unpublish | ✅ | The live page doesn't change until you publish. |
| Version history and restore | ✅ | Last 30 versions per page |
| Duplicate pages, reusable templates, new page from a template | ✅ | |
| Validated, versioned layout storage | ✅ | `App\PageBuilder\LayoutValidator`: unknown widgets and fields are dropped, rich text is sanitised (no scripts or `javascript:` links). Format version is stored in the JSON. |
| Drag & drop (Elementor-style) | ✅ | Drag widgets and section layouts from the panel into the preview (pointer-based: works with mouse, pen and touch, and across the preview iframe). Move widgets and sections inside the preview with the ⠿ grip, with auto-scroll and an orange drop line. Browser-tested: `tests` + Playwright drag script. |
| Click-to-edit in the preview | ✅ | Hover bars for section (blue), column and widget (red): add above, drag, edit, duplicate, delete. A click on a section's padding selects the section. |
| Page layouts (like Elementor) | ✅ | Per page: **Default** (theme header/footer + title banner), **Full Width** (header/footer, no banner, sections can go edge to edge), **Canvas** (no theme header/footer: a blank page). Sections also have width `bleed` (full screen, no padding), height auto/full screen/custom, and content position. |
| Custom HTML pages | ✅ | "Custom HTML page" when creating a page: a Canvas page with one full-bleed HTML widget. HTML widget has separate HTML / CSS / JS fields (fullscreen code editor, Tab key). Page-level custom CSS, JS and `<head>` code. Only admins can write HTML/JS. |
| Save / publish robustness | ✅ | CSRF token sent in the body as well as the header (some hosts strip headers → 419); clear Bengali error messages per HTTP status; local backup in the browser + restore prompt; autosave every 60 s; browser errors reported to the server log (`admin/client-log`), visible in the "Server self-test" GitHub workflow. |
| White inner pages | ✅ | Every page except Home is white by default (page setting "পেজের রং": White/Dark). Header text turns dark on white pages, stays white over a dark first section, and the sticky header is white. Sections with dark / gradient / image backgrounds stay dark. Title banner and footer join adapted. |
| Full Width = really full width | ✅ | On Full Width and Canvas pages, "Boxed" sections fill the screen (a little side space; sections holding only custom HTML go edge to edge). |
| Pure custom HTML (Canvas) | ✅ | A Canvas page that only holds HTML widgets is sent without any theme CSS/JS, so the code looks exactly as written. A whole HTML document (`<!DOCTYPE html>…`) is output as it is. |
| Broken / cut-off HTML can't break a page | ✅ | Unclosed `<style>`, `<script>`, comments and `<div>`s in an HTML widget are closed automatically; the builder warns when pasted code looks incomplete. (Old 20,000-character limit removed.) |
| Very tall blocks always appear | ✅ | Entrance animations start as soon as any part is on screen (before, blocks taller than ~8 screens never appeared). New HTML widgets have no entrance animation. |

---

## Phase 2 – Content pages, services, forms

| Requirement | Status | Notes |
|---|---|---|
| 22 dedicated service pages (`/services/…`) | ✅ | Unique copy per service: hero, overview, who it is for, problems, benefits, deliverables, features, use cases, tools, process, client requirements, notes, FAQ (with FAQ schema), related services, CTA, SEO title + description. Automation/n8n pages cover integrations, error handling, monitoring, human approval and maintenance; software pages separate prototypes from production; video pages cover deliverables, formats, footage and revisions without a fixed policy. All are normal builder pages (editable). |
| Related portfolio projects on service pages | 🟡 | Portfolio grid filtered by category; it hides itself while there are no matching projects. Full portfolio migration is Phase 3. |
| Service catalogue | ✅ | Admin → Services: group, page link, "show on home", "show in catalogue". Home page keeps its 4 cards (now linked to service pages). Main menu prioritises AI Automation, Website, Software, Motion & Video. |
| About, Services overview, Our Process, FAQ, Tools & Technologies, Contact, Start a Project | ✅ | Complete English copy, only verified facts from the existing site (founder, 15+ years, location, contact details). |
| Contact + quote forms | ✅ | Builder widget "Form". Server validation, honeypot, minimum fill time, rate limit; no-reload sending with field errors, loading and success animation. Messages stored in Admin → Messages (status, notes). Service pre-selected from `?service=`. |
| Email notifications | ⛔ needs SMTP | Admin → Settings → Email (SMTP): enter the cPanel mailbox, password stored encrypted, "send test email" button. Until then messages are only stored in the admin. |
| Search results page | ✅ | `/search` over published pages and services, highlighted excerpts. Blog/portfolio/products join in later phases. |
| Custom 404 page | ✅ | Editable builder page `page-not-found` (with search box), shown with HTTP 404. |
| Legal starter drafts | 🟡 owner review | Privacy, Terms, Refund, Digital delivery, Shipping — created **unpublished** with a "requires review" notice and [bracketed] items. Footer links to them appear automatically once published. Not legal advice. |
| Pricing / packages page | ⬜ not created | Spec: only with genuine pricing information. |
| Content seeding on deploy | ✅ | `ContentSeeder` runs on every deploy but only creates what is missing; never overwrites edits and never re-creates a page you deleted. |
| Tools lists | 🟡 owner review | Tool lists on service pages are typical choices — please remove any you do not want to offer. |
| Pabitra.net install (About the Expert etc.) | ⬜ | Will be added when the second install is set up. |

## Phase 3 – Shop, payments, portfolio & blog migration (this phase)

**Shop (WooCommerce-like)** — Admin → দোকান
- Products: digital / physical / service, regular + sale price (with dates), SKU, stock, gallery, variants
  (own price, sale price, stock, files), downloadable files (private storage), features, specifications, FAQ,
  related products, SEO, "Duplicate". Product categories, coupons (percent / fixed, minimum, limit, dates).
- Public: `/shop`, `/shop/category/{slug}`, `/shop/{slug}`, `/cart`, `/checkout`, `/order/{number}`,
  `/account` (register, login, password reset, orders, downloads, profile). Guest checkout optional.
- Totals are always calculated on the server. Orders get a number, timeline and emails (texts editable).
- Downloads: random 48-character links, limit + expiry (per product or default), revoked on refund/cancel.

**Payments** — Admin → Payment gateways (enable/disable switch, sandbox/live, test button)
- PayStation, aamarPay, EPS (built from their API docs), Bank transfer + Mobile banking (manual: admin confirms).
- Custom gateway 1/2: connect any other gateway without code (URLs, fields with placeholders, signatures,
  response paths for status / amount / transaction id).
- Every online payment is verified server-to-server before the order is marked paid; amount and duplicate checks.
- Secrets are stored encrypted. EPS endpoints follow a community package — check with EPS's merchant-portal docs.

**Portfolio & blog** — `/portfolio`, `/portfolio/{slug}` (case study fields, gallery, video), `/blog`,
`/blog/category/{slug}`, `/blog/{slug}` (reading time, prev/next, related). Page texts: Admin settings.

**WordPress import** — Admin → WordPress থেকে আনুন (or `php artisan content:import-wp https://site`,
or the "Server self-test" workflow with action `import` and the site URL in `slug`).
Posts + portfolio are published with their original dates; products always arrive as drafts. Images are
downloaded; nothing existing is overwritten; every item is listed in the import report.

Tests: `tests/store-e2e.php` (added to `tests/e2e-install.sh`).

## Design update – "landing page vibe" (after phase 3)
- New `public/assets/css/vibe.css` + `public/assets/js/vibe.js`: colourful running animations on all inner pages
  (aurora colour backgrounds, floating shapes, waves, light beams, stars, confetti, rainbow borders, cursor spotlight,
  3D tilt, magnetic buttons). All continuous motion stops for visitors with "reduced motion" turned on, and
  sections outside the screen pause their animations.
- Builder: new section backgrounds (Aurora, Vivid) and effects (aurora, shapes, waves, beams, stars, confetti),
  new "Marquee" (running strip) widget, new "pop" icon-box style (colourful numbered cards).
- Service pages and core pages: new landing layout. The content seeder replaces only pages that are still exactly
  as the installer created them (fingerprints in `database/content/fingerprints.php` + setting `content_fingerprints`);
  pages edited in the builder are never touched.
- Product page rebuilt as a sales landing page (hero with buy box, running feature band, feature cards,
  sticky buy card, how-it-works, FAQ, colourful CTA, sticky buy bar). Shop page gets a running band.
- Fix: shop card class clashed with the builder "card" style (service boxes had collapsed to one column).
