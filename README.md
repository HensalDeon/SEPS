# SEPS website

WordPress build of the Sunrise English Private School redesign
([Figma](https://www.figma.com/design/ujP9xOuLv54gf5NguY2eud/SEPS-Website-Redesign)).

**Phase 1:** global header and footer, and the home page.

## What's in the repo

```
wp-content/
├── themes/seps/            Custom classic theme (presentation only)
│   ├── inc/                Setup, assets, navigation, template tags
│   │   └── scf/            Secure Custom Fields: options page + field groups (code-registered)
│   ├── template-parts/
│   │   ├── header/         Top bar, masthead (logo, nav, CTA, mobile drawer)
│   │   ├── footer/         Footer
│   │   └── sections/       One file per home page section
│   ├── assets/src/         SCSS (BEM, tokens) and JS (ES modules)
│   └── assets/dist/        Compiled CSS/JS. Committed, so the theme runs without a build.
└── plugins/seps-core/      Content model: Testimonials + FAQs post types, their fields,
                            and the `wp seps seed` command
```

The post types live in a plugin so that content survives a future theme change.

## Local setup (LocalWP)

1. In LocalWP, create a site (e.g. **seps**, PHP 8.2, preferred setup).
2. Copy the theme and plugin into it:

   ```bash
   bin/sync-local.sh seps
   ```

3. In LocalWP, click **Open site shell** and run:

   ```bash
   wp plugin install secure-custom-fields --activate
   wp theme activate seps
   wp plugin activate seps-core
   wp seps seed
   ```

`wp seps seed` fills a fresh site with the Figma content: the main pages from the sitemap, all six menus, Site Settings, the home page sections, four news posts, testimonials, FAQs, staff, and the About page, plus the images (with alt text). Seeded items are updated in place, never duplicated.

> **Re-running overwrites edits** to seeded items (Home sections, menus, Site Settings…). On an existing site, run only the steps you need, e.g. `wp seps seed --only=staff,about`. Run `wp help seps seed` for the list of steps.

**Day to day:** edit files in this repo, then re-run `bin/sync-local.sh seps` to update the Local site. (Use `npm run build` first if you changed SCSS or JS.)

> Don't symlink the theme or plugin into Local. LocalWP's *Push to WP Engine* doesn't follow symlinks, so the theme never reaches the server and the site loads blank.

## Deploying to WP Engine (from LocalWP)

1. `npm run build` in `wp-content/themes/seps` if styles or scripts changed, then `bin/sync-local.sh seps`.
2. In LocalWP: **Push** → *an existing site* → **SEPS** → **Staging**.
   - **Code changes only:** Files → *Select files* → tick `themes/seps` and `plugins/seps-core`. Leave **Database unticked** so content edited on staging isn't overwritten.
   - **First deploy, or a deliberate content reset:** All files + Database.
   - ⚠️ **Always untick every red "Will be deleted" row** before pushing. Those are WP Engine's own files that don't exist in Local (`wp-content/advanced-cache.php`, favicons). Deleting `advanced-cache.php` disables WP Engine's cache integration.
3. In the WP Engine portal: **Cache → Clear all caches**.

The sync script excludes development files (`node_modules`, SCSS/JS sources, build configs), so only what the site needs goes to the server.

## Where content is managed

Everything on the home page is editable. Nothing is hard-coded in templates.

| What | Where in wp-admin |
|---|---|
| Logo | Appearance → Customize → Site Identity |
| School name / "Abu Dhabi" line | Settings → General (Site Title / Tagline) |
| Email, phone, address, social links, header CTA, footer heading, subscribe text, footer photo, copyright | **Site Settings** (sidebar) |
| Header navigation | Appearance → Menus → *Header: Primary navigation*. Single level by design: nested items are not shown (no dropdowns). |
| Top bar links | Appearance → Menus → *Header: Top bar links* |
| Footer columns | Appearance → Menus → *Footer: Column 1/2/3*. **The menu name is the column heading.** |
| Privacy / Terms links | Appearance → Menus → *Footer: Legal links* |
| Home page sections | Pages → Home. One tab per section, each with a *Show this section* toggle. |
| Inner pages (About, Academics and future pages) | Pages → *page* → **Sections**. Add, remove and drag to reorder: Page Hero, Image + Text Blocks, Values & Guidelines, Message, Team, Highlights & Focus Areas, Badge Accordion, Timetable, Documents, Requirements, Info Cards, Text + Image, Notice + Checklist, FAQs, Cards (carousel or grid), Latest posts, Enquiry form, Map, Photo + stacked cards, Text. The Page Hero heading can have a line break; the hero also has an optional note line, decoration (and its height), "Keep in view" (photo crop focus), a second (white) button and a *Wide text, narrow photo* layout. Text + Image photos can sit left or right, or be a circle, with an optional rounded corner. Every section has an optional **Section ID** so links can jump to it (e.g. `/academics/#mandatory-disclosure`). |
| Staff (Principals, Leadership Team) | **Staff** (sidebar). Photo = Featured image, *Position* field, *Team*, and *Order* to sort. Shown by the Team section. |
| Testimonials | **Testimonials** (sidebar). The home page shows the latest 6 unless specific ones are picked. |
| FAQs | **FAQs** (sidebar). Ordered by *Order*; can be hand-picked per page. |
| Contact form submissions | **Enquiries** (sidebar, read-only). Each enquiry is also emailed to the *Email* in Site Settings. Subject and contact-time options are set on the Enquiry form section. Personal data can be exported or erased from Tools → Export / Erase Personal Data. |
| News | Posts. On the home page the newest is featured and the next three are listed; News & Events lists them all ("All News") below its sections. |

## Front-end build

Requires Node 20+.

```bash
cd wp-content/themes/seps
npm install
npm run dev     # watch SCSS + JS with source maps
npm run build   # production build → assets/dist (commit the result)
npm run lint    # stylelint (SCSS) + eslint (JS)
```

- **CSS:** Sass → Autoprefixer. Design tokens are CSS custom properties (`abstracts/_tokens.scss`). Type and spacing scale fluidly from 375px to the 1920px Figma frame.
- **JS:** esbuild bundle, loaded with `defer`. Everything is progressive enhancement: the page works without JS (`no-js` fallback for the mobile nav).
- **Font:** Outfit, self-hosted variable WOFF2. There are no requests to Google.

## PHP standards

The code follows the WordPress Coding Standards. To lint (Composer required):

```bash
composer install   # also installs WordPress stubs, which removes IDE "unknown function" warnings
composer lint
```

## Building inner pages (section builder)

Every page except Home is built from **Page Sections** (`inc/scf/fields-page-sections.php`). Each section type is a template in `template-parts/sections/` that receives its data as `$args`, the same contract the home page uses.

News & Events is the WordPress posts page: `home.php` renders its sections on page 1, then the paginated list of all posts (`#all-news`). Later pages, category archives and search show the list only (`template-parts/post-list.php`).

To add a section type for a new design:

1. Add its layout to `page_section_layouts()` and its template slug to `page_section_templates()`. Keys follow `field_seps_ps_{layout}_{name}` and must never change once content exists.
2. Create `template-parts/sections/{slug}.php`. Use `wp_unique_id()` for heading IDs, since sections can repeat on a page.
3. Create `assets/src/scss/sections/_{slug}.scss`, add it to `main.scss`, and follow the `--edge` rule below.
4. Add demo content for the page to `wp seps seed` as a new step.

Reuse existing section types whenever a design repeats one.

## Layout rule for new pages: `--edge`

Every section must line up with the header at every screen width, including wider than the 1920px design.

- **Contained content:** use `.container` (max 1620px).
- **Full-bleed sections** (background or photo to the screen edge): keep the background full-bleed, but pad or position the content with **`var(--edge)`**, the distance from the screen edge to the container edge. Never use `--gutter` or fixed values measured from the screen edge.
  - Text in a full-bleed half: `padding-inline-start: var(--edge);`
  - Decorative shapes: `left/right: var(--edge)` or `calc(var(--edge) ± …)`
  - Compositions that only work at design width: `max-width: 120rem; margin-inline: auto;`
- `--edge` only works inside `<main>`. In the header or footer, use `max(var(--gutter), (100% - var(--container-max)) / 2)`.
- **Check new pages at 390, 768, 1280, 1440, 1920 and 2560px.** At 2560, every section should start on the same line as the header logo.

## Responsive behaviour

The Figma file has desktop frames only. Responsive layouts follow the design's structure:

- **Header:** inline navigation from 1440px. Below that, an off-canvas drawer with focus trap, Escape to close, and the utility links and socials included. The top bar collapses to email and phone on mobile.
- **Sections:** multi-column layouts step down to 2 columns, then 1. Decorative shapes scale with the viewport.
- **Highlights strip:** a static row at 1536px+. Below that, an infinite marquee that pauses on hover and stays static for users who prefer reduced motion.
- **Testimonials:** the photo dome bleeds into the FAQ section on desktop, as designed. Below 1280px it sits in normal flow. The carousel shows 2 cards per view, or 1 on mobile.
- **Footer:** the decorative photo shows at 1536px+. Menus reflow to 2 columns, then 1.

## Deliberate additions beyond the Figma

- **Accessibility:** skip link, visible focus rings, `aria-current`, screen-reader context on repeated buttons ("Apply Now: Kindergarten"), and alt text on every image.
- **SEO:** FAQPage structured data (JSON-LD) on the FAQ section.

## Open items for the client / TL

See [OPEN-QUESTIONS.md](OPEN-QUESTIONS.md) (logo, social and portal URLs, testimonial and FAQ copy, newsletter integration).

Each sitemap entry is a **single page**: its sub-items (e.g. About → Vision & Mission, Leadership) are sections on that page, not separate pages. Pages not yet designed exist as empty placeholders, ready for the next phases.

## Email delivery (Contact form)

Enquiries are always saved under **Enquiries**, so none are lost, but the
notification email depends on the server's mail setup:

- **LocalWP:** emails are caught by Mailpit (Local → site → *Tools* → Mailpit).
- **WP Engine:** sends via its own mail service. For reliable delivery to a
  school mailbox, set up an SMTP plugin (e.g. WP Mail SMTP with the school's
  Google Workspace / Microsoft 365 account). Each enquiry shows *Emailed:
  Yes/No* so failures are visible.

