# Fira Link Design System (v2.0)
**The Unified Design System for Fira Link — Ethiopia's Premier Coffee Trade Platform**

---

## 1. Executive Summary & Brand Principles

Fira Link ([fira-link.com](https://fira-link.com)) connects Ethiopian coffee producers, cooperatives, and exporters directly with international buyers, roasters, and brokers. The platform provides a trusted, institutional-grade digital infrastructure encompassing a **B2B Spot & Forward Coffee Marketplace**, **Cupping & Quality Verification**, **Export Documentation Studio**, **Live Market Terminal**, and **Trade Academy & Origin Expeditions**.

### 1.1 Brand Tenets
1. **Authoritative & Trustworthy:** A trade platform handling multimillion-dollar commodity shipments must project stability, institutional rigor, and clarity within 3 seconds.
2. **Unified Architecture:** Exactly ONE design system from header to footer. No mismatched container widths, no rogue button styles, no card-in-card nesting, and no conflicting active tab indicators.
3. **Respect for Ethiopian Coffee Heritage:** Authentic photography of Ethiopian origins (Yirgacheffe, Sidama, Guji, Jimma, Harar), raised drying beds, grading tables, and logistics hubs. Absolutely zero generic AI faces, cutout artifacts, or synthetic stock models.
4. **Data Density & Ergonomics:** High legibility, tabular numbers for pricing, unambiguous status pills, and accessible contrast across all viewports (1440px desktop down to 390px mobile).

---

## 2. Design Tokens (CSS Architecture)

All CSS variables are scoped to `:root` and declared in `public/assets/front/css/fira-design-system.css`. No hex literals or arbitrary values are permitted in component styles.

### 2.1 Color Tokens
The palette is rooted in Fira Link's regal purple identity, balanced by pristine neutral slates and purpose-built semantic indicators.

```css
:root {
  /* Brand Core */
  --fira-brand-primary:   #6D1A95; /* Regal Brand Purple */
  --fira-brand-deep:      #3B0764; /* Deep Velvet Indigo / Primary Hover */
  --fira-brand-tint:      #F3E8FF; /* Soft Lavender Tint */
  --fira-brand-subtle:    #FAF5FF; /* Subtle Brand Glow */

  /* Neutral Surface & Page */
  --fira-page-bg:         #F9F7FA; /* Premium Warm Alabaster Page Background */
  --fira-surface:         #FFFFFF; /* Pure White Card / Modal Surface */
  --fira-surface-alt:     #F3F0F5; /* Secondary Surface / Input Fill */
  --fira-border-hairline: #E9E4F0; /* 1px Architectural Hairline Border */
  --fira-border-strong:   #D4C9DF; /* Active / Hover Border */

  /* Typography Colors */
  --fira-text-primary:    #1E1B4B; /* Deep Navy Obsidian - High Contrast */
  --fira-text-secondary:  #4B4869; /* Muted Body / Secondary Labels */
  --fira-text-muted:      #6B7280; /* Tertiary Captions / Stale Timestamps */
  --fira-text-inverse:    #FFFFFF; /* Text on Primary Purple */

  /* Status Tokens (Word + Colour) */
  /* Rule: Pending is PURPLE on Lavender (#6D1A95 on #F3E8FF), never amber or green */
  --fira-pending:         #6D1A95;
  --fira-pending-bg:      #F3E8FF;
  --fira-pending-border:  #E9D5FF;

  /* Success (Verified, Active, Completed, In Stock) */
  --fira-success:         #15803D; /* Forest Green */
  --fira-success-bg:      #DCFCE7; /* Mint Tint */
  --fira-success-border:  #BBF7D0;

  /* Danger / Alert (Expired, Disputed, Required Error) */
  --fira-danger:          #B91C1C; /* Crimson */
  --fira-danger-bg:       #FEE2E2; /* Blush Tint */
  --fira-danger-border:   #FECACA;

  /* Info / Neutral Badge */
  --fira-info:            #1E1B4B;
  --fira-info-bg:         #EDE9FE;
  --fira-info-border:     #DDD6FE;
}
```

### 2.2 Typography Scale & Rules
The typeface is **Inter** for Latin characters, paired with **Noto Sans Ethiopic** for Amharic.

```css
:root {
  --fira-font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --fira-font-amharic: 'Noto Sans Ethiopic', 'Inter', sans-serif;

  /* Type Scale */
  --fira-type-display: clamp(3.25rem, 5vw, 4rem);    /* 52px - 64px, Line-height: 1.08, -0.035em */
  --fira-type-h1:      clamp(2.5rem, 3.8vw, 2.75rem); /* 40px - 44px, Line-height: 1.15, -0.025em */
  --fira-type-h2:      clamp(1.875rem, 2.5vw, 2rem);  /* 30px - 32px, Line-height: 1.22, -0.02em */
  --fira-type-h3:      clamp(1.375rem, 1.8vw, 1.5rem);/* 22px - 24px, Line-height: 1.28, -0.015em */
  --fira-type-h4:      1.125rem;                      /* 18px, Line-height: 1.35 */
  --fira-type-body-lg: 1.0625rem;                     /* 17px, Line-height: 1.6 */
  --fira-type-body:    1rem;                          /* 16px, Line-height: 1.6 */
  --fira-type-ui:      0.875rem;                      /* 14px, Line-height: 1.5 (Strict Floor for UI) */
  --fira-type-small:   0.8125rem;                     /* 13px, Line-height: 1.45 (Table Meta) */
  --fira-type-micro:   0.75rem;                       /* 12px, Line-height: 1.4 (Badges / Stamps Floor) */
}
```

#### Typography Rules:
1. **Never below 14px for UI controls:** Buttons, inputs, dropdowns, navigation links, and filter labels must never fall below `14px` (`0.875rem`).
2. **Absolute Floor of 12px:** Badges, timestamps, and legal footers may use `12px` (`0.75rem`), but never 10px or 11px.
3. **Tabular Numerics:** All commodity prices, FOB rates, cupping scores, lot weights, and counters MUST use `font-variant-numeric: tabular-nums` to ensure exact column alignment.
4. **Sentence Case:** All headings, buttons, tabs, and menu items use sentence case (e.g., *"Request spot sample"* rather than *"Request Spot Sample"*).
5. **Line Length:** Prose paragraphs must not exceed 70 characters (`max-width: 70ch`).
6. **Amharic Localization:** Amharic typography inherits `--fira-font-amharic` with a 10% line-height expansion (`line-height: 1.7`) to accommodate ascenders and descenders.

---

### 2.3 8px Spacing Grid
All paddings, margins, gaps, and section heights follow an 8px base rhythm:

| Token | Value | Recommended Use |
|---|---|---|
| `--fira-space-0-5` | `4px` | Icon-to-text gap, badge inner vertical padding |
| `--fira-space-1` | `8px` | Small button gap, chip spacing, stack micro-gap |
| `--fira-space-1-5` | `12px` | Input inner vertical padding, card tag gap |
| `--fira-space-2` | `16px` | Card internal padding, mobile horizontal gutters |
| `--fira-space-3` | `24px` | Desktop card padding, grid gutter, form field stack |
| `--fira-space-4` | `32px` | Group spacing, modal interior padding |
| `--fira-space-6` | `48px` | Section padding on mobile (390px) |
| `--fira-space-8` | `64px` | Section padding on tablet (768px), large card padding |
| `--fira-space-12` | `96px` | Section vertical rhythm on desktop (1440px / 1200px) |

---

### 2.4 Border Radii
Fira Link uses disciplined geometric curves:

| Token | Value | Target Components |
|---|---|---|
| `--fira-radius-input` | `12px` | Form inputs, select dropdowns, textareas, action buttons |
| `--fira-radius-card` | `16px` | Product cards, course cards, stat cards, banners |
| `--fira-radius-large` | `24px` | Modals, off-canvas drawers, hero feature shells |
| `--fira-radius-pill` | `999px` | Status badges, chips, search bars, counter badges |

---

### 2.5 Shadows & Elevation (Purple-Tinted, Never Black)
Pitch-black or muddy grey drop-shadows are strictly forbidden. Shadows utilize a 5% to 12% alpha blend of `--fira-brand-deep` (`#3B0764`), creating a soft, luminous depth:

```css
:root {
  /* Elevation 1 (Cards, Dropdown Menus, Subtle Resting Elements) */
  --fira-shadow-1: 0 1px 3px rgba(59, 7, 100, 0.05), 0 1px 2px rgba(59, 7, 100, 0.03);

  /* Elevation 2 (Card Hover, Floating Nav, Tooltips) */
  --fira-shadow-2: 0 4px 16px -2px rgba(59, 7, 100, 0.08), 0 2px 6px -1px rgba(59, 7, 100, 0.04);

  /* Elevation 3 (Modals, Overlays, Sticky Header on Scroll, Drawers) */
  --fira-shadow-3: 0 16px 36px -4px rgba(59, 7, 100, 0.12), 0 6px 16px -2px rgba(59, 7, 100, 0.06);
}
```

---

### 2.6 Layout Grid & Container Unity
A critical defect identified during the Phase 0 audit was the mismatch between container widths (Header at 1440px vs. Sections at 1140px, 1200px, 1280px, 1360px).

Under Fira Link Design System v2.0, **EXACTLY ONE CONTAINER RULE APPLIES TO THE ENTIRE WEBSITE**:

```css
.fira-container {
  width: 100%;
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
  padding-left: 24px;
  padding-right: 24px;
}

@media (max-width: 768px) {
  .fira-container {
    padding-left: 16px;
    padding-right: 16px;
  }
}
```
- **Desktop (1024px – 1440px):** `max-width: 1200px; padding: 0 24px;`
- **Mobile (< 768px):** `width: 100%; padding: 0 16px;`
- **12-Column Grid:** 12 equal columns with `24px` column gaps (`16px` on phone).
- **Background Alternation Rule:** Only `--fira-page-bg` (`#F9F7FA`) and `--fira-surface` (`#FFFFFF`) are used for section backgrounds. A page must never contain more than 2 background colors.

---

### 2.7 Motion & Transitions
Transitions provide immediate sensory feedback without unnecessary motion:
- `--fira-transition-fast`: `150ms ease-out` (button hover, chip toggle, active click)
- `--fira-transition-normal`: `250ms cubic-bezier(0.16, 1, 0.3, 1)` (tabs, dropdowns, card lift)
- `--fira-transition-slow`: `400ms cubic-bezier(0.16, 1, 0.3, 1)` (modal open, drawer slide)
- **Accessibility:** `@media (prefers-reduced-motion: reduce) { * { transition-duration: 0.01ms !important; } }`

---

## 3. Component Specifications & Interactive States

Every component is defined across seven explicit states:
1. `default`
2. `hover`
3. `focus-visible` (architectural purple outline ring, no default blue halos)
4. `active` (pressed feedback)
5. `disabled` (low opacity, `not-allowed` cursor, zero pointer events)
6. `loading` (spinner or skeleton state)
7. `error` (crimson indicator and assistive message)

### 3.1 Buttons
**The One-Primary-Per-View Rule:** Each viewport/card may contain at most ONE solid primary button. Competing primary buttons are prohibited.

| Variant | Height | Styling | States Covered |
|---|---|---|---|
| **Primary Button** | 52px (Hero/Main CTA)<br>44px (Standard UI) | Background: `#6D1A95`<br>Text: `#FFFFFF`<br>Radius: `12px` | Hover (`#3B0764`), Focus (`outline: 2px solid #6D1A95; outline-offset: 2px`), Active, Disabled (opacity 0.4), Loading (embedded 16px SVG spinner). |
| **Secondary Outline** | 52px / 44px | Background: `transparent`<br>Border: `1px solid #E9E4F0`<br>Text: `#1E1B4B` | Hover (Bg: `#FAF5FF`, Border: `#6D1A95`, Text: `#6D1A95`), Focus, Active, Disabled. |
| **Text Link** | Auto | Text: `#6D1A95`, Font-weight: `600`, Underline on hover only. | Default, Hover, Focus-visible. |
| **Destructive** | 44px | Background: `#FEE2E2`<br>Text: `#B91C1C`<br>Border: `1px solid #FECACA` | Hover (Bg: `#B91C1C`, Text: `#FFFFFF`), Disabled. |

### 3.2 Form Controls
- **Inputs & Selects:** Height `44px`, Radius `12px`, Border `1px solid #E9E4F0`, Background `#FFFFFF`, Text `#1E1B4B` (14px).
  - *Focus state:* Border becomes `#6D1A95`, box-shadow `0 0 0 3px rgba(109, 26, 149, 0.12)`.
  - *Error state:* Border becomes `#B91C1C`, helper text rendered in crimson below the field.
  - *Disabled state:* Background `#F3F4F6`, border `#E5E7EB`, color `#9CA3AF`, cursor `not-allowed`.
- **Checkboxes & Radios:** `20px` custom controls. Checked state: `#6D1A95` with white checkmark/dot.
- **Switches:** `44px x 24px` track. Inactive: `#E9E4F0`; Active: `#6D1A95`.

### 3.3 Navigation & Underline Tabs
- **Tab Rule:** Tabs must NEVER appear as filled solid pills. Tabs use clean text with an animated 2px underline:
  - *Inactive Tab:* Text `#6B7280`, font-weight `500`, border-bottom `2px solid transparent`.
  - *Active Tab:* Text `#6D1A95`, font-weight `600`, border-bottom `2px solid #6D1A95`.
  - *Hover Tab:* Text `#1E1B4B`.
- **Segmented Control:** Enclosed container (`#F3F0F5`, padding `4px`, radius `12px`). Active segment slides with white surface and subtle shadow.
- **Filter Chips:** Height `32px`, radius `999px`. Inactive: border `1px solid #E9E4F0`, bg `#FFFFFF`. Active: border `1px solid #6D1A95`, bg `#F3E8FF`, text `#6D1A95`.

### 3.4 Badges & Status Pills
Status pills combine an accessible background, border, text color, and icon:
- **Verified Seller / Partner:** `#DCFCE7` bg, `#BBF7D0` border, `#15803D` text + Shield Check icon.
- **Grade 1 Specialty / Natural:** `#F3E8FF` bg, `#E9D5FF` border, `#6D1A95` text + Coffee Bean icon.
- **Pending Review:** `#F3E8FF` bg, `#E9D5FF` border, `#6D1A95` text + Clock icon.
- **Escrow Secured:** `#EDE9FE` bg, `#DDD6FE` border, `#1E1B4B` text + Lock icon.
- **Sold Out / Discontinued:** `#F3F4F6` bg, `#E5E7EB` border, `#6B7280` text.

### 3.5 Cards
- **Product / Coffee Lot Card:**
  - 16px radius, 1px `#E9E4F0` border, `#FFFFFF` background, Elevation 1.
  - Top: High-resolution image (aspect ratio 16:10) with Origin & Grade badge overlay.
  - Body: Harvest year, Washing station / Washing region, Q-Grading cupping score (`tabular-nums`), moisture content.
  - Price: FOB Djibouti price in USD/lb and USD/kg with tabular typography.
  - Single primary action: *"Request sample"* or *"Contact exporter"*.
- **Course Card:**
  - 16px radius, thumbnail image (16:9), Category badge, Instructor (default: *Firaol Kebede*), Lessons count, Duration.
  - Certificate badge only if paid course; no certificate badge if free course.
- **Metric / Stat Card:**
  - Large tabular number (`32px`, weight 700, `#1E1B4B`), descriptive label (`14px`, `#6B7280`), optional sparkline / trend indicator.

### 3.6 Data Tables
- **Architecture:** Sticky table header (`background: #F9F7FA; border-bottom: 2px solid #E9E4F0`).
- **Alignment:** Text left-aligned; lot quantities, FOB prices, bag counts, and dates right-aligned with `tabular-nums`.
- **Interactive:** Subtle row hover (`background: #FAF5FF`).
- **Responsive:** On viewports under `768px`, tables collapse into readable multi-row cards.

### 3.7 Feedback & Overlay Elements
- **Modal:** Centered overlay, max-width `540px` (or `720px` for workflows), radius `24px`, background `#FFFFFF`, shadow Elevation 3, 24px inner padding, clear top-right close icon.
- **Off-Canvas Drawer:** Slides from right, width `400px` (100% on phone), radius `24px 0 0 24px`, backdrop blur `rgba(30, 27, 75, 0.4)`.
- **Toast:** Floating at bottom-right, radius `12px`, elevation 2, auto-dismissible after 4 seconds.
- **Skeleton Loader:** Pulse animation from `#F3F0F5` to `#E9E4F0` (`1.5s ease-in-out infinite`), matching exact shape of destination text/image.
- **Empty State:** Centered container, clean monoline icon, bold 18px heading, 14px explanatory text, and a single primary button.

---

## 4. Iconography Standards

1. **Format:** Strict inline SVG line-icons with `stroke-width="1.75"` and `stroke="currentColor"`.
2. **Dimensions:** Standard size `20px x 20px` for UI controls; `24px x 24px` for headers/features; `16px x 16px` inside badges and micro-actions.
3. **Consistency:** Lucide / Feather line style. No mixed solid/duotone icons on the same screen.

---

## 5. Imagery & Photography Guidelines

1. **Authenticity Only:** Only genuine photography representing Ethiopian coffee value chains is permitted:
   - Washing stations and wet mills (Yirgacheffe, Sidama, Guji, Limu).
   - Raised African drying beds with cherry sorting.
   - Coffee cupping, sensory grading, and moisture analysis in accredited laboratories.
   - Warehousing, jute bag staging (60kg bags with grain-pro liners), container stuffing, and Djibouti port freight logistics.
2. **Formats:** Modern formats only: `.webp` or `.avif`, compressed to under `150 KB` per hero image and under `60 KB` per card thumbnail.
3. **Prohibited Imagery:**
   - Jagged cut-out portraits (e.g., the legacy `fira_hero_cutout.png`).
   - Generic Caucasian or non-Ethiopian stock models claiming to be Ethiopian farmers.
   - Synthetic AI-generated faces with distorted eyes, teeth, or coffee cherries.

---

## 6. Content Design & Internationalization

### 6.1 Voice & Tone
- **Tone:** Authoritative, institutional, transparent, direct. We facilitate high-stakes international agricultural commerce.
- **Forbidden:** Informal colloquialisms, marketing hyperbole ("revolutionary magic"), or generic educational clichés.

### 6.2 Button Copy Conventions
- Buttons must use active verbs and specify the object:
  - *Correct:* *"Request sample"*, *"Download cupping sheet"*, *"View export contracts"*, *"Inquire on lot"*, *"Enroll free"*.
  - *Incorrect:* *"Click here"*, *"Submit"*, *"More"*, *"Learn more"*.

### 6.3 Numerical & Commercial Standards
- **Export Coffee:** Quoted in USD per pound (`$ / lb`) and USD per metric tonne (`$ / MT`) FOB Djibouti.
- **Lot Quantities:** Quoted in 60 kg export jute bags or Full Container Loads (1 FCL = 320 bags = 19.2 MT).
- **Domestic Academy & Local Services:** Quoted in Ethiopian Birr (`ETB`).
- **Dates:** ISO standard storage (`YYYY-MM-DD`), displayed locally (e.g., *"14 Nov 2026"*).

---

*Fira Link Design System v2.0 — Approved for Implementation across Web Shell and Application Pages.*
