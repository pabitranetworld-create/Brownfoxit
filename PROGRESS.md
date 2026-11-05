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
