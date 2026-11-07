# SEO system — Brown Fox IT

How search-engine optimisation works on this site, what was found before it was built, what is done,
what is still open, and how to launch. Admin screens are in Bengali; this file is for developers.

## 1. Architecture (existing project)

- Laravel 12 · PHP 8.2 · MySQL/MariaDB (SQLite in local tests) · Blade views · no Node build step.
- cPanel shared hosting; GitHub Actions runs the full test suite on MySQL 8 and then deploys by FTP + a deploy hook
  (`/_deploy`: migrate → content seeder → optimize).
- Content lives in: builder pages (`pages`), blog (`posts`, `post_categories`), portfolio (`projects`),
  shop (`products`, `product_categories`), fixed routes (home, shop, blog, portfolio).
- Public pages render through `layouts/app.blade.php`; Canvas pages through `pages/pure.blade.php`.

## 2. Pre-implementation audit (what was found)

| Problem | Effect | Fixed by |
|---|---|---|
| Shop/blog/account pages printed **two canonical tags** (layout + store layout), product page a third | conflicting signals | one head partial |
| Titles/descriptions built separately in ~15 views, different patterns | inconsistent, some pages without description | central resolver |
| `robots.txt` was a static "allow all" file, no sitemap anywhere | new content found slowly | dynamic robots + sitemaps |
| The draft domain (1stdraft…) was indexable | duplicate of the future live site in Google | automatic noindex off the real domain |
| FAQ widget emitted `FAQPage` JSON-LD on many pages | not eligible (Google restricts FAQ rich results to gov/health sites since 2023) | removed |
| Product JSON-LD always had an Offer (even price 0 / drafts) | invalid offers | Offer only with a real price |
| No redirects when slugs change; no way to map old WordPress URLs | lost links/rankings on migration | redirect manager + auto 301 |
| Blog categories could not be edited in admin; no tag pages | | new admin resource, `/blog/tag/{tag}` (noindex) |
| Home hero image not prioritised; many below-the-fold images not lazy | slower LCP | `fetchpriority`, preload, `loading="lazy"` |

## 3. What is implemented

**Central resolver** — `App\Seo\Seo` (one per request, helper `seo()`), printed only by `partials/seo-head.blade.php`.
Priority: per-page SEO settings → value given by the view → generated from the content → site defaults.
Outputs exactly one `<title>`, description, robots, canonical, Open Graph, X card, and one JSON-LD `@graph`.

- **Per-page settings** (`seo_meta` table + existing `seo_title/seo_description/og_image/noindex` columns, which stay
  the single source for those four): title, description, canonical, noindex/nofollow, robots extras, OG title/
  description/image/alt, X card, focus + secondary keywords, custom JSON-LD (validated; ratings/reviews refused),
  breadcrumb label/hide, notes, URL (slug) change with automatic 301.
- **Global settings** (Admin → SEO সেটিংস): real domain, visibility (auto/index/noindex), force HTTPS, home title,
  separator, default description/share image, X handle, organisation identity, founder (Person), optional
  LocalBusiness (off by default), og:locale, extra robots lines.
- **Integrations** (Admin → Google ও Bing): Search Console + Bing verification meta, GA4 with Consent Mode v2
  (consent banner when required; nothing stored before "Accept").
- **Robots**: noindex automatically for cart, checkout, order, account, download, payment, search, tag pages,
  drafts, previews, utility pages (404 page), and every page on a non-production domain (+ `X-Robots-Tag` header).
- **Canonical**: https + preferred host on the real domain, no trailing slash (matches the existing .htaccess rule),
  tracking/sort/filter parameters removed, `?page=N` kept for N>1, self-referencing unless set otherwise.
- **Sitemaps**: `/sitemap.xml` (index) → `/sitemap-{pages|posts|projects|products|categories}.xml`, split every
  2,000 URLs, `lastmod`, image entries; only public, indexable, self-canonical URLs; cached 1 h and refreshed on
  every content save. `/robots.txt` generated (rewrite rule makes it work even if an old static file exists).
- **Redirect manager** (Admin → Redirects): 301/302/307/308, active toggle, hit counter, duplicate detection, loop
  refusal, chains collapsed to the final URL, system paths protected, case/trailing-slash insensitive, query kept,
  "test an address" tool. Slug changes create 301s automatically and update older redirects (no chains).
  **WordPress mapper**: reads the old site's pages/posts/categories/tags/products/portfolio via its REST API and
  proposes the closest new URL with a confidence level; nothing is created until confirmed; no blanket redirects
  to the home page.
- **Preferred host**: `www`↔non-`www` and `http`→`https` 301 on the real domain only.
- **Structured data** (`App\Seo\StructuredData`): Organization (+logo, sameAs, founder), Person, WebSite,
  WebPage/CollectionPage/AboutPage/ContactPage/ItemPage, BreadcrumbList, BlogPosting (real author, dates),
  Service (service pages), Product (+Offer only with real price; availability from stock), CreativeWork
  (portfolio). Linked with `@id`s. No ratings, reviews, awards or invented addresses; no FAQPage.
- **Visible breadcrumbs** on nested full-width pages (e.g. `/services/…`); other pages already had them.
- **Audit** (Admin → SEO ড্যাশবোর্ড): renders every public page in an isolated copy of the app (no network
  loopback needed) and checks title/description/canonical/robots counts and lengths, H1 count, images without
  alt, JSON-LD validity, visible word count (labelled as a hint), internal links; then a broken-link pass.
  Dashboard: public/indexable/noindex counts, missing metadata, duplicates, broken links, redirect loops/chains,
  sitemap counts, recently changed, "needs attention". Clearly labelled as on-site checks — no traffic,
  rankings or backlinks are shown or invented.
- **SEO editor** per page: Google result preview, live length counters, keyword placement checks (guidance only),
  related-phrase ideas from the page's own text (no search-volume claims), internal-link ideas, checklist.
- **Performance**: preload + `fetchpriority=high` for the home LCP image with width/height, lazy loading for
  below-the-fold home images, gzip (mod_deflate) and longer cache headers, off-screen animation pausing and
  `prefers-reduced-motion` (already in place) kept.
- **Email (bonus, Admin → ইমেইল / Gmail)**: Gmail App Password (server/port filled in automatically) or
  "Connect with Google" (OAuth, `gmail.send` scope only, refresh token encrypted, sent through the Gmail API).

Tests: `tests/seo-e2e.php` (part of `tests/e2e-install.sh`, runs on every push).

## 4. Not done / limitations

- **WebP/AVIF conversion and responsive `srcset`** for uploaded images — not implemented (uploads are served as
  uploaded). Recommended next step: generate WebP copies on upload.
- **CSS/JS minification** — files are served unminified but gzip-compressed; a build step would need tooling the
  host does not have. Unused-CSS removal not attempted (risk to the animated design).
- **Core Web Vitals / PageSpeed scores were not measured** — the live site cannot be reached from the build
  environment. Measure on the real domain with PageSpeed Insights / Search Console after launch.
- **hreflang**: not output — the site has one language (English). The resolver and `og:locale` setting are ready
  for alternate versions when they genuinely exist.
- **Indexing API**: not used (Google only allows it for job postings / livestreams). Use Search Console.
- The audit checks what the site can see itself; impressions, clicks, rankings and backlinks need Search Console.
- Structured data was validated for JSON syntax and our own rules; run Google's Rich Results Test on the live
  domain for Google's verdict.

## 5. Deployment (cPanel)

Nothing manual is needed: push → tests → FTP deploy → `/_deploy` runs the migration (`seo_meta`, `redirects`,
`seo_audits`, `post_categories.description`) and the content seeder (sets the real domain for known installs:
`brownfoxit.com` / `pabitra.net`). If the host has an old `public/robots.txt`, the rewrite rule bypasses it.

## 6. Post-launch checklist

1. Admin → SEO সেটিংস: real domain correct (with or without `www`), HTTPS on, organisation details filled.
2. **Before** switching the old WordPress site off: Admin → Redirects → fetch old addresses → review → create.
3. Point the domain to the new site; check `http://`, `www.` and an old URL all land on the right https page.
4. Run the audit and the broken-link check; fix anything listed under "needs attention".
5. Search Console: add the property, verify (HTML tag field or DNS), submit `sitemap.xml`; Bing: import.
6. Publish the legal pages after review; set GA4 (consent on for EU/UK visitors) if analytics is wanted.
7. Weeks 1–4: Search Console → Pages (404s, redirect errors, "crawled – not indexed"), Core Web Vitals.
8. Keep publishing genuinely useful articles/case studies; review AI-assisted drafts before publishing.
