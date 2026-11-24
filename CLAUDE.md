# SEPS — project instructions

WordPress build of the SEPS website from Figma, delivered page by page.
See README.md for setup, structure and deployment.

## Layout rule: every section must respect `--edge` (mandatory)

Content must line up with the header on every screen width, including
wider than the 1920px design.

- **Contained sections:** put content inside `.container` (max 1620px). Nothing else is needed.
- **Full-bleed sections** (background or photo runs to the screen edge): keep the background full-bleed, but pad or position content with `var(--edge)`, never `var(--gutter)` or fixed px/vw from the screen edge.
  - Text in a full-bleed half: `padding-inline-start: var(--edge);`
  - Decorative shapes near the edge: `left/right: var(--edge)` or `calc(var(--edge) ± …)`.
  - Elements that touch the screen edge at 1920 (e.g. domes): `right: calc(var(--edge) - var(--gutter));`
  - A composition that only works at design width: cap the section with `max-width: 120rem; margin-inline: auto;`.
  - Cap decorative shapes at their design size (e.g. `width: min(43%, 51.875rem)`).
- `--edge` (in `abstracts/_tokens.scss`) uses `cqi` and is only valid **inside `.site-main`**. In the header or footer, use the %-based equivalent: `max(var(--gutter), (100% - var(--container-max)) / 2)`.
- **Verify every new page at 390, 768, 1280, 1440, 1920 and 2560px.** At 2560, every section must start on the same line as the header logo.

## Other conventions

- **Field groups:** SCF field groups are registered in code (`inc/scf/`), with stable keys `field_seps_{prefix}_{name}`. One Group field per section, passed to `template-parts/sections/*.php` as `$args`.
- **One page per sitemap entry, no sub-pages, no header dropdowns.** Sitemap sub-items (e.g. About → Leadership) are sections on the parent page. The primary menu is rendered single-level.
- **Inner pages use the section builder** (`inc/scf/fields-page-sections.php`, flexible content `page_sections`, keys `field_seps_ps_{layout}_{name}`). For each new page design, reuse existing section types first; add new layouts only for genuinely new sections (see README "Building inner pages"). Use `wp_unique_id()` for heading IDs.
- **Seeder:** each page gets its own `wp seps seed` step. Users run `--only=<steps>` on existing sites, because re-seeding overwrites edits.
- **Content model:** post types and taxonomies go in the `seps-core` plugin, not the theme.
- **Escaping:** template tags in `inc/template-tags.php` return fully escaped HTML.
- **CSS:** BEM, design tokens as custom properties, mobile-first `mq()` mixin. The nav breakpoint is 1440px; keep `$breakpoints.nav` and `navigation.js` in sync.
- **Build and lint:** after SCSS/JS changes, run `npm run build` and `npm run lint` in `wp-content/themes/seps`.
- **Local site:** sync with `bin/sync-local.sh seps`. Never symlink into Local: WP Engine push doesn't follow symlinks.
- **Pushing from Local:** always untick the red "Will be deleted" rows (WP Engine's `advanced-cache.php`, favicons). Keep Local and WP Engine on the same PHP and plugin versions, and push plugin folders with "all files" (a partial push of CF7 crashed staging).
