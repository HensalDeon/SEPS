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
2. Link the theme and plugin into it:

   ```bash
   bin/link-local.sh seps
   ```

3. In LocalWP, click **Open site shell** and run:

   ```bash
   wp plugin install secure-custom-fields --activate
   wp theme activate seps
   wp plugin activate seps-core
   wp seps seed
   ```

`wp seps seed` fills a fresh site with the Figma content: the page tree from the sitemap, all six menus, Site Settings, the home page sections, four news posts, testimonials and FAQs, plus the images (with alt text). You can run it again safely: seeded items are updated, not duplicated.

## Where content is managed

Everything on the home page is editable. Nothing is hard-coded in templates.

| What | Where in wp-admin |
|---|---|
| Logo | Appearance → Customize → Site Identity |
| School name / "Abu Dhabi" line | Settings → General (Site Title / Tagline) |
| Email, phone, address, social links, header CTA, footer heading, subscribe text, footer photo, copyright | **Site Settings** (sidebar) |
| Header navigation (with dropdowns) | Appearance → Menus → *Header: Primary navigation* |
| Top bar links | Appearance → Menus → *Header: Top bar links* |
| Footer columns | Appearance → Menus → *Footer: Column 1/2/3*. **The menu name is the column heading.** |
| Privacy / Terms links | Appearance → Menus → *Footer: Legal links* |
| Home page sections | Pages → Home. One tab per section, each with a *Show this section* toggle. |
| Testimonials | **Testimonials** (sidebar). The home page shows the latest 6 unless specific ones are picked. |
| FAQs | **FAQs** (sidebar). Ordered by *Order*; can be hand-picked per page. |
| News | Posts. The newest is featured; the next three are listed. |

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

## Responsive behaviour

The Figma file has desktop frames only. Responsive layouts follow the design's structure:

- **Header:** inline navigation from 1440px. Below that, an off-canvas drawer with focus trap, Escape to close, and the utility links and socials included. The top bar collapses to email and phone on mobile.
- **Sections:** multi-column layouts step down to 2 columns, then 1. Decorative shapes scale with the viewport.
- **Highlights strip:** a static row at 1536px+. Below that, an infinite marquee that pauses on hover and stays static for users who prefer reduced motion.
- **Testimonials:** the photo dome bleeds into the FAQ section on desktop, as designed. Below 1280px it sits in normal flow. The carousel shows 2 cards per view, or 1 on mobile.
- **Footer:** the decorative photo shows at 1536px+. Menus reflow to 2 columns, then 1.

## Deliberate additions beyond the Figma

- **Dropdown chevrons in the header nav.** The sitemap has sub-pages, and the chevron is the keyboard and screen-reader toggle for them.
- **Accessibility:** skip link, visible focus rings, `aria-current`, screen-reader context on repeated buttons ("Apply Now: Kindergarten"), and alt text on every image.
- **SEO:** FAQPage structured data (JSON-LD) on the FAQ section.

## Open items for the client / TL

See [OPEN-QUESTIONS.md](OPEN-QUESTIONS.md) (logo, social and portal URLs, testimonial and FAQ copy, newsletter integration).

Inner pages exist as placeholders in the sitemap structure, ready for the next phases.
