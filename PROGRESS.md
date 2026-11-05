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
| Header / footer navigation management | ✅ | Admin → Home Page → Menu / Footer (built in an earlier phase) |
| Assign content to Pabitra.net / Brown Fox IT / both | ⛔ | The owner chose **two separate installs**, so each site has its own database and content can't be shared between them. A page export/import tool (copy a page from one site to the other) is planned. |
| Widgets (22): hero, heading, text, image, buttons, icon cards, list, video, gallery + lightbox, FAQ accordion (+ FAQ schema), tabs, CTA, counters, process steps, workflow diagram, comparison table, testimonials, services grid, portfolio grid + animated filter, blog posts, divider, custom HTML, nested columns | ✅ | Every widget is rendered and checked by the CI test. |
| Contact / quote form widget | ⬜ | Phase 2 (it needs storage and email) |

## Frontend motion (inner pages)

| Requirement | Status | Notes |
|---|---|---|
| Scroll reveal, staggered cards, word-by-word headings, scroll-filled headings | ✅ | `public/assets/js/motion.js` |
| Parallax, hover effects (lift / grow / glow / 3D tilt / shine) | ✅ | |
| Animated backgrounds (gradient flow, light orbs, moving lines, glowing grid, particles), glow borders | ✅ | |
| Image zoom / clip reveal / circular mask / float | ✅ | |
| Animated workflow diagram (moving data dots, node pulses) | ✅ | |
| FAQ accordion, tabs with moving indicator, lightbox gallery, video popup | ✅ | Checked in a browser |
| Animated portfolio filtering | ✅ | |
| Scroll progress bar, back-to-top, sticky header | ✅ | |
| Page transitions | 🟡 | Uses the browser's native cross-page View Transitions (Chrome / Edge). Other browsers load pages normally. |
| Reduced-motion support | ✅ | Checked in a browser with "reduce motion" on: nothing hidden, continuous effects stopped |
| Content stays visible if JavaScript fails | ✅ | Elements are hidden only while JS runs, with a 2.5-second failsafe. Checked with JavaScript disabled. |
| Desktop / tablet / mobile checked | ✅ | 1366 px, 834 px and 390 px in Chromium, with no horizontal overflow. Real-device testing by the owner is still recommended. |
| Lightweight 3D / WebGL | ⬜ | Not added. Nothing so far needs it. |
| Cart / checkout / form-feedback animations | ⬜ | Built together with those features in phases 2 and 4 |
| Admin stays calm and fast | ✅ | The builder has no decorative animation. |

## Not started yet

| Phase | Items |
|---|---|
| 2 – Content pages | 22 service pages with full copy, About (company), Process, FAQ, Contact + Request a Quote forms (stored + emailed), legal page drafts (marked for owner/legal review), 404, search |
| 3 – Portfolio & blog | Migration from brownfoxit.com / pabitra.net with a source → target inventory, case-study pages, blog list / article pages |
| 4 – E-commerce | Products (drafts until the owner gives real data), cart, checkout, orders, customer accounts, protected downloads, coupons, reports; PayStation / aamarPay / EPS gateways after their API documents and merchant credentials are confirmed, with a manual-payment fallback until then |
| 5 – Final QA | Full checklist of the specification, SEO (sitemap, redirects from old WordPress URLs), security review, documentation |

## Waiting for the owner

- Product list and real product details (phase 4)
- Merchant credentials and API documents for PayStation, aamarPay and EPS (phase 4)
- Permission to read pabitra.net for the second site's content (phase 3)
