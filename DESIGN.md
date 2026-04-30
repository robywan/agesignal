---
name: AgeSignal — Design System
description: Personal health dashboard for end patients. Visualizes lab test data uploaded by the user as PDF reports — values, reference ranges, classification, and historical trends. The interface is reassuring, plain-language, and grounded strictly in data the application actually ingests.
version: 0.2
---

# Document scope

This document defines the design system for AgeSignal: visual tokens, components, layout rules, and the canonical list of data sources that the UI is allowed to consume. It is the authoritative reference for any UI work in this repository.

**Hard rule.** Every component described here must be backed by data the application actually has. AgeSignal is a referto-processing app, not a fitness tracker, calendar, or wearable platform. Components that imply data we do not ingest (sleep, hydration, exercise, nutrition tracking, upcoming medical appointments, real-time vitals) are out of scope. The "Data sources" section below is the closed list.

---

## Data sources (what the UI is allowed to consume)

| Source | Fields used by the UI |
|---|---|
| `User` | `name`, `email`, `birthdate`, `sex` (enum), `height_cm`, `weight_kg`. Computed: `age`, `bmi`. |
| `UserCondition` (one user → many) | `name`, `since_year`, `notes`, `is_active`. |
| `LabTestDocument` (one user → many) | `name`, `test_date`, `status`, uploaded file via Spatie Media Library. |
| `LabTestTable` | Tables extracted from a document. |
| `LabTestResult` | `name`, `value`, `unit_measure`, `reference_min`, `reference_max`, `textual_range`, `numeric_value`, `operator`, `is_abnormal`, `loinc_num`, `loinc_status` (enum), `loinc_confidence_score`. |
| `LoincCoreEntry` | `component`, `system`, `class`, `class_type`, `long_common_name`. Used to group parameters by class. |

Anything not in this list cannot back a UI component. If a feature requires data we do not have, the feature does not get built — the design pivots around the actual data.

---

## Design philosophy

1. **Honest before pretty.** A correct empty state beats a fake metric. If we don't have the data, say so.
2. **Information density without noise.** Every pixel earns its place. No decorative elements that don't carry information.
3. **Plain language.** The patient is not a clinician. Every numeric value has a written explanation in everyday Italian.
4. **Reassure, then inform.** Color and copy are calibrated to inform without alarming. Red is reserved for values that genuinely warrant medical contact.
5. **The patient owns their history.** All data shown is data the user uploaded. Nothing is invented, inferred without flagging, or sourced from outside the user's referti.
6. **AI only as plain-language summarizer.** When AI is used, its job is to translate technical results into everyday language and suggest topics for the next medical visit. Never diagnoses, never prognoses, never prescriptions.

---

## Color tokens

All tokens live in `resources/css/app.css` under the Tailwind v4 `@theme` block. Reference them via Tailwind utilities (`bg-surface`, `text-text-primary`, etc.).

### Surfaces
| Token | Hex | Usage |
|---|---|---|
| `--color-page` | `#f0f4f8` | Page background — warm off-white with slight blue tint |
| `--color-surface` | `#ffffff` | Card backgrounds |
| `--color-surface-muted` | `#f5f7f9` | Subtle inset surfaces (chat bubbles, soft chips) |
| `--color-surface-dark` | `#060d1a` | Reserved (no current consumer; do not use without need) |

### Brand blue — interaction only, never status
| Token | Hex | Usage |
|---|---|---|
| `--color-brand` | `#005cb6` | Primary buttons, links, focus rings |
| `--color-brand-light` | `#b5d4f4` | Avatar circle background, accent chips |
| `--color-brand-deep` | `#0c447c` | Text on `brand-light` |
| `--color-brand-wire` | `#1e5fa0` | Reserved |
| `--color-brand-glow` | `#2d8fe0` | Reserved |

Never use brand blue to encode health status. It is an interaction colour only.

### Status — semantic, reserved for health values
| Token | Hex | Usage |
|---|---|---|
| `--color-status-ok` | `#1d9e75` | Solid fill — value within range |
| `--color-status-ok-bg` | `#eaf3de` | Soft tint — badge / banner background |
| `--color-status-ok-text` | `#3b6d11` | Text on `status-ok-bg` |
| `--color-status-warn` | `#ef9f27` | Solid — slightly out of range |
| `--color-status-warn-bg` | `#faeeda` | Soft tint |
| `--color-status-warn-text` | `#854f0b` | Text on warn tint |
| `--color-status-low` | `#e24b4a` | Solid — significantly out of range, warrants medical contact |
| `--color-status-low-bg` | `#fcebeb` | Soft tint |
| `--color-status-low-text` | `#791f1f` | Text on low tint |

Status colors are reserved for `LabTestResult.loinc_status` (or equivalent classification). Never decorative.

### Neutrals
| Token | Hex | Usage |
|---|---|---|
| `--color-gray-50` | `#f0f4f8` | Aliased to page bg |
| `--color-gray-100` | `#e2e6ea` | Bar tracks, default borders |
| `--color-gray-200` | `#d9dde2` | Strong borders |
| `--color-gray-400` | `#8c9198` | Muted icons |
| `--color-gray-600` | `#6b7178` | Secondary text |
| `--color-gray-900` | `#111922` | Primary text |
| `--color-text-primary` | alias `gray-900` | |
| `--color-text-secondary` | alias `gray-600` | |
| `--color-border-default` | alias `gray-100` | |

---

## Typography

Single typeface: **Inter** primary, **DM Sans** fallback (loaded from Bunny Fonts in `partials/head.blade.php`). Geometric sans, optimized for small sizes. No serifs.

Semantic text utilities (`@theme` tokens — produce `text-{name}` classes):

| Class | Size | Weight | Line-height | Used for |
|---|---|---|---|---|
| `text-score` | 36px | 500 | 1.1 | Reserved for the single most important number on a screen (rarely used) |
| `text-display` | 2rem | 500 | 1.2 | Section displays (rare) |
| `text-heading` | 15px | 500 | 1.3 | Patient name, panel titles |
| `text-label` | 12px | 500 | 1.3 | Parameter names, row labels |
| `text-body` | 11px | 400 | 1.45 | Plain-language explanations, descriptions |
| `text-caption` | 10px | 400 | 1.3 | Dates, units, metadata, section headers (with letter-spacing 0.05em + uppercase) |

**Minimum size: 10px.** Never go below. Sentence case throughout — uppercase only for section dividers (caption + tracking + uppercase).

---

## Spacing

Tailwind's default 4px-base scale matches the design system. Use `1` (4), `2` (8), `2.5` (10), `3` (12), `4` (16), `5` (20), `6` (24), `8` (32), `10` (40). The dashboard's outer gap is `2.5` (10px); inner card padding is `4` (16px) horizontal, `4` vertical.

## Radius

| Class | Value | Used for |
|---|---|---|
| `rounded-sm` | 6px | Small buttons, badges (other than full pills) |
| `rounded-md` | 8px | Inputs, ai-care chat bubbles |
| `rounded-lg` | 12px | (Reserved) |
| `rounded-xl` | 16px | All cards, header, banner, footer |
| `rounded-full` | 9999px | Status pills, dots, avatar circle |

---

## Layout

**Authenticated shell** — Flux sidebar layout (`x-layouts::app`) provides app navigation. The sidebar handles nav; each route's content renders inside `<flux:main>`.

**Dashboard layout** — full-width column inside `flux:main`, with internal padding `2.5` and gap `2.5`:

```
┌──────────────────────────────────────────────────────────┐
│ HEADER · profile (avatar, name, age/sex/BMI, last referto)│
├──────────────────────────────────────────────────────────┤
│ BANNER (conditional — profile incomplete OR values out)   │
├───────────────────────────────────┬──────────────────────┤
│ LEFT (2fr) — parameter content    │ RIGHT (1fr) —        │
│   · parameter groups (LOINC class)│ conditions, AI Care  │
│   · referti history               │ placeholder           │
└───────────────────────────────────┴──────────────────────┘
```

The dashboard has a 2-column desktop grid `2fr / 1fr` (no fixed minimum width yet — mobile breakpoints to be defined when needed).

---

## Components

### Card
Standard surface. Class: `rounded-xl border border-border-default bg-surface px-4 py-4`. Sections of the dashboard are composed of these.

### Section header (inside a card)
Caption text, uppercase, slight tracking, `text-text-secondary`. Class pattern:
```html
<div class="text-caption text-text-secondary mb-3"
     style="letter-spacing: 0.05em; text-transform: uppercase;">
  Section name
</div>
```

### Status badge — `<x-status-badge :status="ok|warn|low" :label="..." />`
Pill, 9px font, weight 500. Three variants matching the status palette. Display-only, never interactive. See [resources/views/components/status-badge.blade.php](resources/views/components/status-badge.blade.php).

### Header (dashboard)
Avatar circle (40×40px, `bg-brand-light`, `text-brand-deep`, initials inside) + patient name + summary line ("45 anni · Uomo · BMI 24.8 · Normopeso") on the left, "Ultimo referto · 28 apr 2026" on the right. The summary line lists only the profile fields the user has actually filled in; falls back to "Profilo non ancora compilato" if all are missing.

### Profile-incomplete banner
Visible only when any of `birthdate`, `sex`, `height_cm`, `weight_kg` is null. Uses the `status-warn` palette. Includes a link to the profile edit page. Does not use red — an incomplete profile is not a clinical issue.

### Parameter group (planned)
Card containing a heading (LOINC `class` name in Italian) and a list of parameters. For each parameter: name, latest value + unit, status badge, sparkline of historical values, trend arrow. Click → drill-down page with full timeline.

### Sparkline (planned)
Small inline SVG line, ~80×24px, color = current status. Maps the last 6–12 values for one parameter.

### Drill-down (planned route)
Single-parameter page: full timeline chart (line over time, range as background band), value table, plain-language explanation derived from LOINC and the user's status history.

### AI Care (placeholder)
Card with title and a one-paragraph honest description of what the feature will do when active. No mock chat. When implemented, it must operate on the user's actual referti via Prism, output Italian plain-language summaries, suggest topics for the next medical visit, and never diagnose.

### Referti history
Compact list of recent `LabTestDocument` rows: dot + name + test date + processing status badge. Linked to the full referti index page.

### Conditions chips
Pills listing the user's `activeConditions`. Each pill: name + (optional) "since YEAR" in muted text. Empty state: "Nessuna condizione dichiarata."

---

## Status logic

`LabTestResult.loinc_status` is the source of truth. The UI reads it and maps to the badge palette:

| `loinc_status` | Badge variant | Meaning shown to user |
|---|---|---|
| in-range / normal | `ok` | "Nel range" |
| borderline / elevated / low | `warn` | "Attenzione" |
| out-of-range / abnormal | `low` | "Fuori range" |

The classification is computed by the AI extraction pipeline (`ClassifyLabTestResultJob`) based on the value vs `reference_min` / `reference_max` / `textual_range`. The UI must not re-classify; it only renders.

---

## Empty states

Every list, card, and chart has a defined empty state. Pattern: short sentence in `text-body text-text-secondary` describing the absence, optionally followed by a single action link if there's one obvious next step. Never hide a section silently.

---

## Do / Don't

**Do:**
- Show only data we ingest. If a section has no data, say so explicitly.
- Use the status palette only for `loinc_status`-driven elements.
- Keep AI Care honest about what it can and cannot do.
- Translate medical jargon to everyday Italian. "Hypercholesterolemia" → "colesterolo alto".

**Don't:**
- Don't invent metrics (no health scores, no fitness data, no calendar).
- Don't decorate. A 3D wireframe avatar that doesn't carry diagnostic information is a decoration. Cut it.
- Don't use red for borderline values. Red is reserved for genuinely worrying values.
- Don't show raw values without explanation. Every number needs a written sentence next to it.
- Don't forecast or diagnose, even when AI is involved.

---

## Changelog

### 0.2 — 2026-04-30
- **Rewritten from scratch.** Previous version (0.1) was rejected for proposing components not backed by available data: sleep / hydration / movement / nutrition metrics, "Health Score" composite number, calendar of upcoming medical appointments, and a 3D wireframe avatar built from sphere primitives. None reflected actual ingestible data and the avatar carried no diagnostic information.
- Added explicit "Data sources" section as the closed list of what the UI may consume.
- Added "Design philosophy" rules with information-value tests.
- Layout simplified from 3-column desktop dashboard to 2-column (parameter content / conditions + AI Care).
- Status colors (`ok`/`warn`/`low`) now bound exclusively to `LabTestResult.loinc_status`.
- 3D avatar removed entirely. Component file deleted.
- Empty states made first-class — no section can render with no honest content.

### 0.1 — initial draft (deprecated)
- 3-column dashboard with Health Score, weekly fitness metrics, appointments, 3D wireframe avatar, AI Care chat, blood pressure gauge, BMI card. Components mostly fictional; design scope drifted from data domain. Replaced.
