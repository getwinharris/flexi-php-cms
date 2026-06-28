---
version: alpha
name: Flexi-Feet-Apple-Design-System
description: A photography-first Apple-inspired design system for Flexi Feet. The interface should feel calm, clinical, premium, and deeply human: white space, precise typography, confident product imagery, quiet chrome, and a single brand-led action color drawn from the Flexi Feet logo.

colors:
  brand-cyan: "#3fbada"
  brand-cyan-hover: "#2ea4c4"
  brand-navy: "#1e1b5d"
  brand-navy-soft: "#2f2a7c"
  ink: "#1d1d1f"
  ink-muted: "#86868b"
  ink-soft: "#6e6e73"
  canvas: "#ffffff"
  canvas-parchment: "#f5f5f7"
  surface-pearl: "#fafafc"
  surface-blue-wash: "#f0f9fc"
  surface-navy: "#1e1b5d"
  surface-navy-2: "#17144b"
  hairline: "#e8e8ed"
  hairline-strong: "#d2d2d7"
  on-primary: "#ffffff"
  on-dark: "#ffffff"
  success-whatsapp: "#25d366"

typography:
  hero-display:
    fontFamily: "Inter, SF Pro Display, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 64px
    fontWeight: 700
    lineHeight: 1.05
    letterSpacing: 0
  display-lg:
    fontFamily: "Inter, SF Pro Display, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 48px
    fontWeight: 700
    lineHeight: 1.08
    letterSpacing: 0
  display-md:
    fontFamily: "Inter, SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 34px
    fontWeight: 700
    lineHeight: 1.16
    letterSpacing: 0
  lead:
    fontFamily: "Inter, SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 22px
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: 0
  body:
    fontFamily: "Inter, SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 17px
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: 0
  body-strong:
    fontFamily: "Inter, SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 17px
    fontWeight: 700
    lineHeight: 1.42
    letterSpacing: 0
  caption:
    fontFamily: "Inter, SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: 0
  caption-strong:
    fontFamily: "Inter, SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 14px
    fontWeight: 700
    lineHeight: 1.35
    letterSpacing: 0
  nav-link:
    fontFamily: "Inter, SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 14px
    fontWeight: 500
    lineHeight: 1
    letterSpacing: 0

rounded:
  none: 0px
  sm: 8px
  md: 12px
  lg: 18px
  xl: 24px
  pill: 9999px
  full: 9999px

spacing:
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  xxl: 48px
  section: 96px

components:
  button-primary:
    backgroundColor: "{colors.brand-cyan}"
    textColor: "{colors.on-primary}"
    typography: "{typography.caption-strong}"
    rounded: "{rounded.pill}"
    padding: 12px 24px
  button-primary-hover:
    backgroundColor: "{colors.brand-cyan-hover}"
    textColor: "{colors.on-primary}"
  button-secondary:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.brand-navy}"
    borderColor: "{colors.hairline}"
    typography: "{typography.caption-strong}"
    rounded: "{rounded.pill}"
    padding: 12px 24px
  text-link:
    backgroundColor: transparent
    textColor: "{colors.brand-cyan}"
    typography: "{typography.body}"
  header-frosted:
    backgroundColor: "rgba(255, 255, 255, 0.72)"
    textColor: "{colors.ink}"
    backdropFilter: "saturate(180%) blur(20px)"
    height: 80px
  hero-gallery:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.brand-navy}"
    typography: "{typography.hero-display}"
    padding: "calc({spacing.section} + 40px) 0 {spacing.section}"
  product-photo-tile:
    backgroundColor: "{colors.canvas-parchment}"
    textColor: "{colors.brand-navy}"
    rounded: "{rounded.none}"
    padding: "{spacing.section} 0"
  dark-story-band:
    backgroundColor: "{colors.surface-navy}"
    textColor: "{colors.on-dark}"
    rounded: "{rounded.none}"
    padding: "{spacing.section} 0"
  utility-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    borderColor: "{colors.hairline}"
    rounded: "{rounded.lg}"
    padding: "{spacing.lg}"
  faq-accordion:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.brand-navy}"
    borderColor: "{colors.hairline}"
    rounded: "{rounded.md}"
    padding: "22px 24px"
  search-input:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    borderColor: "{colors.hairline}"
    rounded: "{rounded.md}"
    height: 56px
    padding: "0 18px"
  footer-quiet:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.caption}"
    padding: "16px 0"
---

## Overview

Flexi Feet should feel like a specialist medical footwear brand presented with Apple's restraint: product and patient-care imagery first, interface second. The site should be calm, precise, and trustworthy, with every surface built from the logo's two core colors: Flexi Cyan (`#3fbada`) for actions and highlight moments, and Flexi Navy (`#1e1b5d`) for authority, headings, dark bands, and key clinical content.

The design should avoid loud decoration. No busy gradients, no oversized card stacks, no heavy shadows, and no visual clutter. Instead, use generous whitespace, crisp typography, large real photography, clean white/parchment surfaces, and restrained motion. The goal is premium clinical confidence: Apple-like clarity, but warmer and more service-oriented because this is healthcare-adjacent footwear.

**Key Characteristics:**
- Photography-first presentation with real shoes, scanning technology, foot-care conditions, and customer-care contexts.
- One action accent: Flexi Cyan. Buttons, links, focus states, active chips, and selected controls should all resolve to this family.
- Flexi Navy is the authority color: headings, dark editorial bands, active filter fills, and high-value service sections.
- White and Apple parchment surfaces carry most of the site. Dark navy bands are used sparingly for contrast and storytelling.
- UI chrome should recede. Cards may use a hairline border, but shadows should be soft and rare.
- Rounded controls should be consistent: 8px for compact elements, 12px for controls, 18px for utility cards, full pill for CTAs.
- Body text uses a calm 17px reading size. Headlines are bold, spacious, and never cramped.

## Colors

### Brand & Accent

- **Flexi Cyan** (`{colors.brand-cyan}` — `#3fbada`): The primary action color. Use for CTAs, links, selected controls, focus rings, progress accents, check icons, and key highlights.
- **Flexi Cyan Hover** (`{colors.brand-cyan-hover}` — `#2ea4c4`): Hover and pressed state for primary actions.
- **Flexi Navy** (`{colors.brand-navy}` — `#1e1b5d`): The trust color. Use for headlines, active filter chips, dark bands, icon strokes, and high-emphasis text.
- **Flexi Navy Soft** (`{colors.brand-navy-soft}` — `#2f2a7c`): A softer navy for secondary emphasis where full navy feels too dense.

### Surface

- **Canvas** (`{colors.canvas}` — `#ffffff`): Default page background and content surface.
- **Parchment** (`{colors.canvas-parchment}` — `#f5f5f7`): Apple-style off-white for alternating bands and quiet section breaks.
- **Pearl** (`{colors.surface-pearl}` — `#fafafc`): Inputs, toolbars, and subtle elevated surfaces.
- **Blue Wash** (`{colors.surface-blue-wash}` — `#f0f9fc`): Rare clinical highlight surface, useful for scanner/technology callouts.
- **Navy Surface** (`{colors.surface-navy}` — `#1e1b5d`): Dark story bands, appointment areas, and premium contrast sections.
- **Navy Surface 2** (`{colors.surface-navy-2}` — `#17144b`): Slightly deeper variant for nested dark elements.

### Text

- **Ink** (`{colors.ink}` — `#1d1d1f`): Main body text on light surfaces.
- **Muted Ink** (`{colors.ink-muted}` — `#86868b`): Secondary body text, captions, and subtitles.
- **Soft Ink** (`{colors.ink-soft}` — `#6e6e73`): Higher-contrast muted text when readability matters.
- **On Dark** (`{colors.on-dark}` — `#ffffff`): Text on navy bands.

### Borders

- **Hairline** (`{colors.hairline}` — `#e8e8ed`): Default 1px border for cards, FAQ rows, inputs, and utility controls.
- **Strong Hairline** (`{colors.hairline-strong}` — `#d2d2d7`): Dividers, empty states, and form boundaries that need more definition.

## Typography

Use `Inter` as the practical web font, with SF Pro/system fonts as the conceptual Apple reference. The site should never feel dense or promotional; text should feel measured and easy to trust.

| Token | Size | Weight | Line Height | Use |
|---|---:|---:|---:|---|
| `{typography.hero-display}` | 64px | 700 | 1.05 | Homepage hero headline |
| `{typography.display-lg}` | 48px | 700 | 1.08 | Major section headings |
| `{typography.display-md}` | 34px | 700 | 1.16 | Secondary section headings |
| `{typography.lead}` | 22px | 400 | 1.45 | Hero subheadline and section lead copy |
| `{typography.body}` | 17px | 400 | 1.6 | Default paragraphs |
| `{typography.body-strong}` | 17px | 700 | 1.42 | Card titles and strong UI labels |
| `{typography.caption}` | 14px | 400 | 1.45 | Metadata, helper text, small descriptions |
| `{typography.caption-strong}` | 14px | 700 | 1.35 | Buttons, chips, compact controls |
| `{typography.nav-link}` | 14px | 500 | 1 | Header navigation |

### Typography Principles

- Use 17px body text for readable, premium pacing.
- Do not use negative letter-spacing. The current site should keep letter spacing at `0` for clean cross-platform rendering.
- Use weight 700 for brand confidence in headings, but avoid heavy all-caps except tiny labels.
- Keep paragraph line length under 720px where possible.
- Use navy for headings on light backgrounds and white for headings on navy backgrounds.
- Avoid italic styling for critical medical or service information; reserve it only for small expressive brand moments.

## Layout

### Spacing

Use an 8px-based rhythm:

- Tight details: 4px, 8px, 12px.
- Component padding: 16px, 24px, 32px.
- Section spacing: 80px to 120px depending on visual weight.
- Maximum content width: 1200px for the main site, 980px for text-heavy sections like FAQ and blog content.

### Page Rhythm

The ideal homepage rhythm:

1. **White photography hero** with product shoe image and one clear CTA.
2. **Parchment or white proof section** for About/mission cards.
3. **Navy story band** for video/reels or patient impact.
4. **White product/technology tile** with large real imagery.
5. **Parchment conditions grid** with quiet clinical cards.
6. **White FAQ accordion** with search and filters.
7. **Navy appointment band** as the strongest conversion surface.
8. **Quiet white footer**.

### Whitespace Philosophy

Whitespace should act like a medical consultation room: clean, calm, and uncluttered. Give products and scanning technology room to breathe. Avoid cramming more than one major message into a section. If a surface feels busy, remove chrome before adding decoration.

## Imagery

Photography is the main emotional system.

### Do Use

- Clear product photography of diabetic and orthopaedic shoes.
- Real scanning technology images.
- Condition imagery where educational, not alarming.
- Human-care context images: fitting, consultation, walking, patient comfort.
- Light backgrounds with product centered and visible.

### Avoid

- Dark, vague, blurred hero images.
- Stock imagery that hides the product.
- Decorative illustrations where a real shoe or scanner image would be more trustworthy.
- Heavy gradient overlays.
- Crops that cut off important product details.

## Components

### Header

Use `{component.header-frosted}`. The header should feel light and functional, not decorative. Keep the logo visible, links concise, and the appointment CTA on the right. On scroll, use a subtle hairline or very soft shadow only if needed for legibility.

### Buttons

**Primary CTA**: `{component.button-primary}`. Use for booking, appointment requests, and key conversion actions. Shape is always pill. Color is always Flexi Cyan.

**Secondary CTA**: `{component.button-secondary}`. Use for "View Collection", "Learn More", and non-primary actions. Keep it white with navy text and a hairline border.

**Pressed State**: Use `transform: scale(0.97)` and the hover color. Avoid dramatic animation.

**Focus State**: Use a 3px translucent cyan ring: `0 0 0 4px rgba(63, 186, 211, 0.22)`.

### Cards

Use `{component.utility-card}` for repeated content like conditions, product styles, blog cards, and contact information. Cards should be simple:

- White background.
- 1px hairline border.
- 18px radius.
- Minimal or no shadow.
- Image first when the content is visual.
- Navy heading, muted paragraph.

Avoid cards inside cards.

### Product Tiles

Use `{component.product-photo-tile}` for product and technology sections. These should feel closer to Apple product tiles than SaaS cards:

- Full-width band.
- Large image.
- Short headline.
- One supporting paragraph.
- One or two CTAs maximum.

### Dark Story Band

Use `{component.dark-story-band}` for reels, appointment booking, or strong proof sections. Navy should feel premium and clinical, not nightclub-dark. Use white headings, muted white body text, and cyan controls.

### FAQ Accordion

Use `{component.faq-accordion}`. The FAQ should be interactive but calm:

- Search first.
- Filter chips second.
- Accordion rows below.
- Active row uses cyan border/focus and navy heading.
- Empty state should be helpful and plain.

### Forms

Forms should feel precise and reassuring:

- Inputs use 16px to 17px text.
- Labels are visible, not only placeholders.
- Field radius: 12px.
- Focus ring: cyan.
- Submit button: full-width cyan pill or rounded rectangle depending on context.
- Error text: plain language, placed directly under the relevant field when possible.

## Motion

Motion should be subtle and purposeful:

- Reveal sections with a short fade/translate only once.
- Button press: scale to `0.97`.
- Accordion open/close: 180ms to 240ms.
- Product hover: very small image scale, maximum `1.03`.
- Avoid spinning, bouncing, parallax-heavy, or attention-seeking animation.

## Elevation

| Level | Treatment | Use |
|---|---|---|
| Flat | No shadow | Bands, hero sections, dark story sections |
| Hairline | 1px `{colors.hairline}` | Cards, inputs, FAQ rows |
| Soft Lift | `0 12px 24px rgba(0,0,0,0.06)` | Important cards only |
| Product Shadow | `0 30px 60px rgba(0,0,0,0.15)` | Product imagery only |

Avoid heavy card shadows. Elevation should never become the design language.

## Do's and Don'ts

### Do

- Use Flexi Cyan for every primary action.
- Use Flexi Navy for trust, headings, and dark bands.
- Use real product and service photography as the primary visual material.
- Keep body copy readable at 17px with generous line-height.
- Use white and parchment surfaces to create Apple-like section rhythm.
- Keep controls familiar: pill buttons, search inputs, segmented filters, accordion rows.
- Make appointment booking feel like the clearest next step.

### Don't

- Do not introduce random accent colors for CTAs.
- Do not use decorative gradient blobs, abstract SVG backgrounds, or visual noise.
- Do not overuse shadows on every card.
- Do not make sections feel like marketing posters when they need to explain medical footwear clearly.
- Do not hide real product imagery behind dark overlays.
- Do not use tiny text for important service, fit, payment, or appointment information.
- Do not place cards inside larger decorative cards.

## Responsive Behavior

| Breakpoint | Width | Behavior |
|---|---:|---|
| Small phone | <= 480px | Single column, compact headings, full-width controls |
| Phone | 481-768px | Single column, centered hero, filters wrap into two columns when needed |
| Tablet | 769-1024px | Two-column grids where comfortable, nav may remain compact |
| Desktop | 1025-1440px | Full layout, 1200px max content width |
| Wide desktop | >= 1441px | Content remains locked; margins absorb space |

### Mobile Rules

- Minimum tap target: 44px.
- Hero image must remain visible and not push all copy below the fold.
- FAQ search and filters should wrap cleanly without horizontal scroll.
- Booking form fields stack to one column.
- Avoid fixed heights that clip text.

## Accessibility

- Maintain color contrast for all text and controls.
- Use visible labels for booking form fields.
- Ensure buttons are real `<button>` elements where they change UI state.
- Accordions must update `aria-expanded`.
- Keyboard focus must be visible, especially on FAQ controls, nav links, and form fields.
- Avoid communicating meaning through color alone.

## Implementation Notes

Current CSS already contains useful base tokens:

```css
--logo-cyan: #3fbada;
--logo-navy: #1e1b5d;
--logo-cyan-hover: #2ea4c4;
--apple-gray-100: #f5f5f7;
--apple-gray-200: #e8e8ed;
--apple-gray-300: #d2d2d7;
--apple-gray-400: #86868b;
```

Future design changes should consolidate around these tokens rather than adding one-off colors. If a new component needs emphasis, choose from cyan, navy, white, parchment, or hairline gray first.

## Iteration Guide

1. Start with the page rhythm, not individual decoration.
2. Choose the section surface: white, parchment, or navy.
3. Add the real image or product asset before adding supporting UI.
4. Use one headline, one short paragraph, and one clear action.
5. Add borders only when a component needs separation.
6. Add shadow only when a product image needs depth.
7. Test desktop and mobile before considering the design finished.

## Known Gaps

- A full product photography set is not yet defined.
- Dark-mode variants are not needed unless the site later adds a user-facing theme switcher.
- Form error states should be expanded when booking validation is redesigned.
- Blog detail pages may need additional editorial typography tokens.
- Admin screens should follow a denser operational design and should not copy the public site's large Apple-style product rhythm.
