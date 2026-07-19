# Design System Specification
## GSM Guard: Modern Enterprise Cybersecurity Dashboard

This document details the complete design system specifications for the **GSM Guard Cyber Security Portal**. It implements a three-layer token architecture (Primitive $\to$ Semantic $\to$ Component) optimized for data-dense cybersecurity dashboards, providing complete Tailwind configurations, visual component blueprints, and accessibility parameters.

---

## 1. Design Tokens & Variables

The tokens are structured into three layers to enable complete decoupling, dark mode consistency, and thematic flexibility.

### 1.1 Primitive Tokens (Color Constants)
These represent raw static hexadecimal codes, mapping to a dark-mode matrix-themed palette:

```css
:root {
  /* Green Core (Matrix theme) */
  --primitive-green-950: #011605;
  --primitive-green-900: #022d0b;
  --primitive-green-800: #034512;
  --primitive-green-700: #04621a;
  --primitive-green-600: #069c2b;
  --primitive-green-500: #00FF41; /* Primary Glow */
  --primitive-green-400: #33ff67;
  --primitive-green-300: #66ff8d;

  /* Neutrals (Monochrome Dark) */
  --primitive-slate-950: #000000; /* Pitch Black */
  --primitive-slate-900: #080808; /* Deep Dark */
  --primitive-slate-800: #0d0d0d; /* Secondary Background */
  --primitive-slate-700: #121212; /* Muted Background */
  --primitive-slate-600: #181818; /* Surface/Card */
  --primitive-slate-500: #1f1f1f; /* Borders/Separators */
  --primitive-slate-400: #888888; /* Muted text */
  --primitive-slate-300: #cccccc; /* Secondary text */
  --primitive-slate-200: #e0e0e0; /* Body text */
  --primitive-slate-100: #ffffff; /* Contrast headings */

  /* Alarm/Destructive Reds */
  --primitive-red-650: #8b0000;
  --primitive-red-500: #FF3333; /* Alert Highlight */
  --primitive-red-400: #ff6666;
  --primitive-red-300: #ff9999;
}
```

### 1.2 Semantic Tokens (Purpose Aliases)
These map raw primitives to generic layout functionalities:

```css
:root {
  /* UI Backgrounds */
  --color-background: var(--primitive-slate-950);
  --color-surface: var(--primitive-slate-900);
  --color-card: var(--primitive-slate-800);
  --color-muted: var(--primitive-slate-700);

  /* Typography */
  --color-foreground: var(--primitive-slate-200);
  --color-foreground-title: var(--primitive-slate-100);
  --color-foreground-muted: var(--primitive-slate-400);

  /* Borders & Focus */
  --color-border: var(--primitive-slate-500);
  --color-ring: var(--primitive-green-500);

  /* State Alerts */
  --color-primary: var(--primitive-green-500);
  --color-accent: var(--primitive-red-500);
  --color-destructive: var(--primitive-red-650);
  --color-warning: #ffcc00;
}
```

### 1.3 Component Tokens (Target specific elements)
Specific component variables bound to semantic definitions:

```css
:root {
  /* KPI & Dashboard Widgets */
  --widget-bg: var(--color-surface);
  --widget-border: var(--color-border);
  --widget-title-color: var(--color-foreground-muted);
  --widget-value-color: var(--color-foreground-title);

  /* Encryption Panels */
  --panel-active-border: var(--color-primary);
  --panel-glow-color: rgba(0, 255, 65, 0.15);

  /* Data Table Layouts */
  --table-row-hover: rgba(0, 255, 65, 0.04);
  --table-border: var(--color-border);
}
```

---

## 2. Layout, Typography, & Spacing Scales

### 2.1 Typography Pairs
*   **Font Pairs:** Fira Code (Headings/Monospace Data) paired with Fira Sans (Body copy).
*   **Mood:** Highly technical, precise, and structural (emulates console interfaces).
*   **Scale Specification:**
    - `text-2xs` (9px): Table column indicators, timestamp details.
    - `text-xs` (12px): Badge text, status flags.
    - `text-sm` (14px): Input fields, secondary metadata, tables cells.
    - `text-base` (16px): Standard paragraph text.
    - `text-lg` (18px): Widget titles, sidebar headers.
    - `text-xl` (20px): Form section headers.
    - `text-2xl` (24px): Primary viewport welcome headings.

### 2.2 Spacing Scales (4px/8px Incremental system)
```css
:root {
  --space-xs: 4px;   /* Inline gaps, micro labels */
  --space-sm: 8px;   /* Icon-text gaps, small margins */
  --space-md: 16px;  /* Standard padding for panels/widgets */
  --space-lg: 24px;  /* Dashboard grid gaps, large padding */
  --space-xl: 32px;  /* Page section margins */
  --space-2xl: 48px; /* Form divisions */
  --space-3xl: 64px; /* Logins page wrapper */
}
```

### 2.3 Border Radii
- `--radius-sm` (4px): Checkboxes, badges.
- `--radius-md` (8px): Inputs, buttons, table cell rows.
- `--radius-lg` (12px): Standard cards, dashboard widgets, panel borders.
- `--radius-xl` (16px): Main viewport blocks.

---

## 3. Reusable UI Component Specifications

### 3.1 Cyber-Glow Cards & Widgets
- **Visual Spec:** Dark background (`#080808`), solid 1px dark border (`#1f1f1f`), with a subtle inner glow on hover.
- **Micro-interactions:** Transitions must take 200ms using `ease-out`. Hover shifts scale slightly (`scale-[1.01]`) and glows with `--color-primary` at 20% opacity.

```css
.cyber-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-md);
  transition: all 200ms cubic-bezier(0.16, 1, 0.3, 1);
}

.cyber-card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 0 15px rgba(0, 255, 65, 0.15);
}
```

### 3.2 Action Buttons
*   **Primary CTA Button (Glow Matrix):** Green glow text on black background, or green background with dark text.

| Property | Default State | Hover State | Active (Pressed) | Disabled |
| :--- | :--- | :--- | :--- | :--- |
| **Background** | `#00FF41` | `#33ff67` | `#069c2b` | `#1f1f1f` |
| **Text Color** | `#0F172A` | `#0F172A` | `#ffffff` | `#888888` |
| **Scale** | `1.00` | `1.015` | `0.98` | `1.00` |
| **Transition** | 150ms ease | 150ms ease | 50ms ease | None |

*   **Secondary Action Button (Outline):**
    - Border: 1px border colored with `--color-primary`.
    - Hover: Background transitions to `rgba(0, 255, 65, 0.08)`.

### 3.3 Data Tables
- **Spec:** Border-collapse, text left-aligned, monospaced figures.
- **Row States:** Hovering over a row highlights it with `--table-row-hover` (`rgba(0, 255, 65, 0.04)`) with a 150ms fade timing.

### 3.4 Alerts & Notifications
- **Status Warnings:** Colors must change dynamically based on threat levels:
  - *Normal:* Border green (`--primitive-green-800`), background green wash (`rgba(6, 156, 43, 0.05)`).
  - *Suspicious/Medium:* Border yellow (`--color-warning` at 30%), background yellow wash.
  - *Critical Alarm:* Border red (`--primitive-red-500`), background red wash (`rgba(255, 51, 51, 0.08)`), accompanied by a soft pulse animation.

---

## 4. Typography, Animations, & Breakpoints

### 4.1 UI Transitions & Keyframe Animations
- **Spring scale:**Pressing buttons must scale down (`scale-95`) and bounce back immediately.
- **Warning Alert Pulse:**
  ```css
  @keyframes alert-pulse {
    0% { box-shadow: 0 0 0 0 rgba(255, 51, 51, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(255, 51, 51, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 51, 51, 0); }
  }
  .animate-alert-pulse {
    animation: alert-pulse 2s infinite;
  }
  ```

### 4.2 Responsive Breakpoints
- **Mobile (375px - 640px):** Single-column grid layouts, bottom tabs navigation, tables hidden or collapsed into responsive card formats.
- **Tablet (641px - 1024px):** Dual-column grids, sidebar navigation collapses to header menu.
- **Desktop (1025px - 1440px+):** Fixed structural sidebars, dense data layout metrics.

---

## 5. Tailwind CSS Configuration Blueprint

Below is the configuration integration file mapping our three-layer token structure to utility classes:

```javascript
module.exports = {
  darkMode: 'class', // Enforce class-based theme switching
  theme: {
    extend: {
      colors: {
        cyber: {
          bg: 'var(--color-background)',
          surface: 'var(--color-surface)',
          card: 'var(--color-card)',
          border: 'var(--color-border)',
          primary: 'var(--color-primary)',
          accent: 'var(--color-accent)',
          muted: 'var(--color-foreground-muted)',
          destructive: 'var(--color-destructive)'
        }
      },
      fontFamily: {
        mono: ['Fira Code', 'monospace'],
        sans: ['Fira Sans', 'sans-serif']
      },
      spacing: {
        'xs': 'var(--space-xs)',
        'sm': 'var(--space-sm)',
        'md': 'var(--space-md)',
        'lg': 'var(--space-lg)',
        'xl': 'var(--space-xl)',
        '2xl': 'var(--space-2xl)'
      },
      borderRadius: {
        'sm': 'var(--radius-sm)',
        'md': 'var(--radius-md)',
        'lg': 'var(--radius-lg)'
      }
    }
  },
  plugins: []
}
```

---

## 6. Accessibility & W3C Guidelines

To satisfy federal and enterprise cybersecurity dashboard compliance (WCAG AA):

1.  **Strict Color Contrast:** Main body texts (`--color-foreground`) must maintain a minimum contrast ratio of **4.5:1** against backgrounds (`--color-surface`). Secondary values or numbers on charts must meet a **3:1** ratio.
2.  **No Color-Only Meaning:** A security state (e.g. Critical Threat) cannot be conveyed using a red dot alone. It must be accompanied by textual classifications (`CRITICAL`) or an alert icon (e.g. `Lucide: AlertOctagon`) so that colorblind operators can read the layout safely.
3.  **Keyboard Navigable Focus Rings:** Focus rings colored with `--color-ring` must be clearly visible (2px minimum thickness) on interactive buttons, input fields, and tab controls during keyboard navigation.
