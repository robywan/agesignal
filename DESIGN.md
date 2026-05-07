---
name: AgeSignal
description: Personal health dashboard for end patients. Reads laboratory test reports the user has uploaded, classifies parameters via LOINC, and presents the most recent referto in plain language. Design philosophy — reassure first, inform second. Never alarm unnecessarily. Every component must be alimented by data the application actually owns.
version: 0.2

colors:
  page-bg:       "#f0f4f8"
  surface-card:  "#ffffff"
  surface-muted: "#f5f7f9"

  blue-primary:  "#005cb6"
  blue-light:    "#B5D4F4"
  blue-deep:     "#0C447C"

  status-ok:       "#1D9E75"
  status-ok-bg:    "#EAF3DE"
  status-ok-text:  "#3B6D11"
  status-warn:     "#EF9F27"
  status-warn-bg:  "#FAEEDA"
  status-warn-text:"#854F0B"
  status-low:      "#E24B4A"
  status-low-bg:   "#FCEBEB"
  status-low-text: "#791F1F"

  gray-50:  "#f0f4f8"
  gray-100: "#e2e6ea"
  gray-200: "#d9dde2"
  gray-400: "#8c9198"
  gray-600: "#6b7178"
  gray-900: "#111922"

  primary:        "{colors.blue-primary}"
  text-primary:   "{colors.gray-900}"
  text-secondary: "{colors.gray-600}"
  border-default: "{colors.gray-100}"

typography:
  display:  { fontFamily: "Inter, DM Sans, system-ui, sans-serif", fontSize: "2rem",  fontWeight: "500" }
  heading:  { fontFamily: "Inter, DM Sans, system-ui, sans-serif", fontSize: "15px",  fontWeight: "500" }
  label:    { fontFamily: "Inter, DM Sans, system-ui, sans-serif", fontSize: "12px",  fontWeight: "500" }
  body:     { fontFamily: "Inter, DM Sans, system-ui, sans-serif", fontSize: "11px",  fontWeight: "400" }
  caption:  { fontFamily: "Inter, DM Sans, system-ui, sans-serif", fontSize: "10px",  fontWeight: "400" }
  score:    { fontFamily: "Inter, DM Sans, system-ui, sans-serif", fontSize: "36px",  fontWeight: "500" }

spacing:
  1: "4px"
  2: "8px"
  3: "12px"
  4: "16px"
  5: "20px"
  6: "24px"
  8: "32px"
  10: "40px"

rounded:
  sm: "6px"
  md: "8px"
  lg: "12px"
  xl: "16px"
  full: "9999px"

components:
  card:
    backgroundColor: "{colors.surface-card}"
    borderRadius:    "{rounded.lg}"
    border:          "0.5px solid {colors.border-default}"
    padding:         "12px 16px"
  status-badge:
    borderRadius: "{rounded.full}"
    fontSize:     "9px"
    fontWeight:   "500"
    padding:      "2px 8px"
  progress-circle:
    size:      "64px"
    trackColor: "{colors.gray-100}"
    lineWidth:  "5px"
  ai-bubble:
    backgroundColor: "{colors.surface-muted}"
    borderRadius:    "{rounded.md}"
    fontSize:        "11px"
    padding:         "7px 9px"
  ai-bubble-user:
    backgroundColor: "{colors.blue-light}"
    color:           "{colors.blue-deep}"
---

## Overview

AgeSignal is a personal health companion — not a clinical tool. The primary user is a non-medical patient who uploaded a lab referto and wants to understand it without anxiety. Every design decision serves two goals: **immediate reassurance** (or a clear, calm alert) and **plain language understanding**.

The dashboard always shows data derived from the most recent `LabTestDocument` of the authenticated user, with cross-references to historical referti where applicable.

**Hard rule (data-grounded design):** every visible component must be alimented by data the application actually owns. No avatar, no fitness-style widgets, no calendar, no population benchmarks, no fabricated trends. If a component has no data, it is hidden — never replaced with a placeholder that pretends to know.

## Available data sources

The dashboard composes its components from these sources only:

- **`users`**: `name`, `birthdate`, `sex`, `height_cm`, `weight_kg` (the demographic columns are added in this iteration; see migration)
- **`lab_test_documents`**: `test_date`, `status`, ownership via `owner_user_id`, attached PDF via Spatie Media Library `files` collection
- **`lab_test_results`**: `name`, `numeric_value`, `unit_measure`, `reference_min`, `reference_max`, `textual_range`, `is_abnormal`, `loinc_num`, `loinc_status`, `loinc_confidence_score`
- **`loinc_core_entries`**: `class`, `long_common_name`, `short_name` — used to group parameters into clinical categories

## Color philosophy

The three-tier semantic system (ok / warn / critical) is reserved exclusively for health status. Never use it decoratively or for branding.

**Status ok — green `#1D9E75`:** parameter within `[reference_min, reference_max]`. Badge background `#EAF3DE`, text `#3B6D11`.

**Status warn — amber `#EF9F27`:** parameter outside reference range but within a 25% deviation from the nearest bound. Badge background `#FAEEDA`, text `#854F0B`. This is the default tier for any `is_abnormal = true` result without further severity logic.

**Status critical — red `#E24B4A`:** parameter outside reference range with deviation > 25% from the nearest bound. Badge background `#FCEBEB`, text `#791F1F`. Reserved for genuine alerts. **Initial implementation may use only ok/warn**; the critical tier ships when severity rules are wired (see "Severity rules" below).

**Brand blue `#005cb6`:** interactive elements only (buttons, links, AI send button). Never for status encoding.

**Page background `#f0f4f8`:** warm off-white with a slight blue tint. Creates depth between page and card surfaces.

## Severity rules

For each `lab_test_result` with non-null `numeric_value`, `reference_min`, `reference_max`:

```
if numeric_value ∈ [reference_min, reference_max]      → ok
elif deviation ≤ 25% of nearest bound                  → warn
else                                                    → critical
```

For results with only `textual_value` or only `textual_range`, severity falls back to `is_abnormal`: false → ok, true → warn (critical is never assigned without numeric deviation).

## Typography

Single typeface throughout: Inter or DM Sans.

- **Heading (15px / 500):** patient name in header, panel titles. Color `#111922`.
- **Label (12px / 500):** parameter names, section headers. Color `#111922`.
- **Body (11px / 400, line-height 1.45):** explanations, AI chat. `#6b7178` for secondary, `#111922` for primary.
- **Caption (10px / 400):** dates, units, metadata. `#6b7178`. Minimum size — never below 10px.
- **Uppercase labels (10px / 400, letter-spacing 0.05em):** section dividers. `#6b7178`. Used sparingly.

## Layout

Two-column desktop grid. Outer page padding: 16px. Gap between columns: 16px.

- **Main column (2fr):** Status banner (conditional) → Parametri fuori range → Trend chart → Risultati per categoria
- **Side column (1fr):** AI Care chat → BMI card → Cronologia referti

**Header:** Full width. White surface. Patient avatar circle (initials) + name + greeting on the left. Last analysis date on the right. Height ~58px.

**Footer:** Full width. White surface. "Scarica PDF referto" button (visible only if a Media Library file exists for the active document) on the right. Last upload date on the left.

On viewports below `lg` (1024px), columns stack: main first, side second.

## Components

### Header
Logo/initials avatar + greeting `Ciao, {first_name}` + name in heading style. Right side: caption `Ultimo referto del {test_date}`. If no referto exists, replaces date with `Nessun referto caricato` and renders a primary CTA `Carica un referto`.

### Status banner (conditional)
Renders only if the latest referto has at least one `warn` or `critical` parameter. Background tinted with the dominant status color. Bold sentence `{n} parametri fuori range` + caption listing up to 3 parameter names (`{name1}, {name2}, {name3}…`).

**Hard rule:** the banner is **informational only**. No buttons. No "Contatta il medico" CTA. No urgency-styled actions. AgeSignal does not push the patient toward medical action — it surfaces the data and lets the patient decide.

If everything is ok, a positive twin component renders instead with `Tutti i parametri sono nella norma`, green tint.

### Parametri fuori range
Card. Header `Parametri fuori range` (label style) + caption with count. List of `value-bar` rows, one per `is_abnormal = true` result of the most recent referto, sorted by absolute deviation descending. Empty state: hidden entirely (the positive status banner already covers this case).

### Value bar (row component)
Horizontal layout: parameter name (80px, label) → bar track (flex 1, 5px height, `#e2e6ea`, rounded) → bar fill (status color, width = position within or beyond reference range, capped at 100%) → numeric value + unit (70px, label) → status pill. Below the row in caption: `Range: {reference_min}–{reference_max} {unit}` or `{textual_range}` when numeric range is missing.

### Trend chart (6 months)
Card. Header `Andamento ultimi 6 mesi` + dropdown to select which parameter to chart. Default: the parameter with the most data points across the user's documents. SVG line chart, x = `test_date`, y = `numeric_value`. Reference range `[reference_min, reference_max]` of the most recent reading rendered as a horizontal green band. Points color-coded by their per-reading status. Hidden if fewer than 2 readings of any parameter exist.

### Risultati per categoria
Card. Groups all results of the most recent referto by `loinc_core_entries.class` (e.g., `Chemistry`, `Hematology`, `Lipids`, `Microbiology`). For each group: section divider with uppercase label = class name + caption count `{ok_count}/{total} nella norma`, then a stacked list of `value-bar` rows (collapsed by default, expandable). Results without a mapped LOINC fall into a final group `Altri parametri`.

### AI Care chat
Compact chat. Bubbles: assistant `#f5f7f9`, user `#B5D4F4` (right-aligned). Font 11px, line-height 1.45. Input row: text input + send button (`#005cb6`, white). Beta badge in header. Tone: plain Italian, no medical jargon, never diagnostic. Powered by Prism with the active referto's results passed as structured context. Token usage logged through `aiUsages()`.

### BMI card
Compact card. Computes `BMI = weight_kg / (height_cm / 100)^2` from `users.height_cm` and `users.weight_kg`. Renders the BMI value (label style) + status pill from the WHO bands:

| BMI | Status |
|---|---|
| 18.5–24.9 | ok |
| 25–29.9 or 17–18.5 | warn |
| ≥ 30 or < 17 | critical |

If either column is null, the card renders a CTA `Completa il tuo profilo` linking to settings.

### Cronologia referti
Card. Vertical list of all `lab_test_documents` of the user, most recent first. Each row: status dot (color from `LabTestDocumentStatus`) + title (`Test del {test_date}`) + caption (status label). The active row is highlighted. Click navigates to the detail (when implemented) — for now, links to `documents.index`.

### Footer
Full width. Left: caption `Caricato il {created_at}`. Right: secondary button `Scarica PDF` linking to the Media Library file URL of the active document. Hidden if no file is attached.

## Reference ranges (sex-specific)

These ranges are used as fallback when a result's `reference_min` / `reference_max` are missing but the LOINC code is mapped to one of the parameters below. When the lab itself reported a range, that range wins.

| Parameter | Unit | Green (ok) | Amber (warn) | Red (critical) |
|---|---|---|---|---|
| Blood glucose | mg/dL | 70–100 | 100–125 or 60–70 | >125 or <60 |
| Total cholesterol | mg/dL | <200 | 200–240 | >240 |
| Hemoglobin (M) | g/dL | 13.5–17.5 | 12–13.5 or 17.5–18.5 | <12 or >18.5 |
| Hemoglobin (F) | g/dL | 12–16 | 11–12 or 16–17 | <11 or >17 |
| Vitamin D | ng/mL | ≥30 | 20–29 | <20 |
| BMI | — | 18.5–24.9 | 25–29.9 or 17–18.5 | >30 or <17 |

## Do's and Don'ts

**Do:**
- Always show a human language explanation alongside every numeric value.
- Use trend arrows (↑ ↓ →) when the same parameter appears in two consecutive referti.
- Hide a card when its data is missing — never render a fake placeholder.
- Keep the AI assistant tone calm, warm, practical. Food and lifestyle suggestions are fine, diagnoses are not.
- Treat the BMI card as conditional — it appears only when both `height_cm` and `weight_kg` are filled.

**Don't:**
- Never show raw values without explanation.
- Never use red for minor deviations — red is reserved for the critical tier as defined above.
- Never use medical jargon in patient-facing text (no "ipercolesterolemia", no "iponatriemia").
- Never use the brand blue `#005cb6` for status encoding — interaction color only.
- Never animate anything except micro-feedback on input states.
- Never invent components that imply data the app does not own (sleep, hydration, calendar, wearables, population benchmarks).
- Never render unsolicited "Contatta il medico" / "Prenota visita" / urgency CTAs. The dashboard is informational; pushing the patient toward medical action is out of scope.
