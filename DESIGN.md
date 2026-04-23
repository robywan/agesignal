---
name: AgeSignal
description: Personal health dashboard for end patients. Monitors lab analysis results, visualizes health trends, and communicates medical data in plain human language. Design philosophy — reassure first, inform second. Never alarm unnecessarily.
version: 0.1

colors:
  # Page & surfaces
  page-bg:       "#f0f4f8"
  surface-card:  "#ffffff"
  surface-dark:  "#060d1a"
  surface-muted: "#f5f7f9"

  # Brand blue
  blue-primary:  "#005cb6"
  blue-light:    "#B5D4F4"
  blue-deep:     "#0C447C"
  blue-wire:     "#1e5fa0"
  blue-glow:     "#2d8fe0"

  # Status semantic — these colors encode health meaning, never use decoratively
  status-ok:       "#1D9E75"
  status-ok-bg:    "#EAF3DE"
  status-ok-text:  "#3B6D11"
  status-warn:     "#EF9F27"
  status-warn-bg:  "#FAEEDA"
  status-warn-text:"#854F0B"
  status-low:      "#E24B4A"
  status-low-bg:   "#FCEBEB"
  status-low-text: "#791F1F"

  # Neutrals
  gray-50:  "#f0f4f8"
  gray-100: "#e2e6ea"
  gray-200: "#d9dde2"
  gray-400: "#8c9198"
  gray-600: "#6b7178"
  gray-900: "#111922"

  # Aliases
  primary:        "{colors.blue-primary}"
  text-primary:   "{colors.gray-900}"
  text-secondary: "{colors.gray-600}"
  border-default: "{colors.gray-100}"

typography:
  display:
    fontFamily: "Inter, DM Sans, system-ui, sans-serif"
    fontSize: "2rem"
    fontWeight: "500"
  heading:
    fontFamily: "Inter, DM Sans, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: "500"
  label:
    fontFamily: "Inter, DM Sans, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: "500"
  body:
    fontFamily: "Inter, DM Sans, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: "400"
  caption:
    fontFamily: "Inter, DM Sans, system-ui, sans-serif"
    fontSize: "10px"
    fontWeight: "400"
  score:
    fontFamily: "Inter, DM Sans, system-ui, sans-serif"
    fontSize: "36px"
    fontWeight: "500"

spacing:
  1:  "4px"
  2:  "8px"
  3:  "12px"
  4:  "16px"
  5:  "20px"
  6:  "24px"
  8:  "32px"
  10: "40px"

rounded:
  sm:   "6px"
  md:   "8px"
  lg:   "12px"
  xl:   "16px"
  full: "9999px"

components:
  card:
    backgroundColor: "{colors.surface-card}"
    borderRadius:    "{rounded.lg}"
    border:          "0.5px solid {colors.border-default}"
    padding:         "12px 16px"
  card-dark:
    backgroundColor: "{colors.surface-dark}"
    borderRadius:    "{rounded.lg}"
    border:          "0.5px solid rgba(255,255,255,0.07)"
  status-badge:
    borderRadius: "{rounded.full}"
    fontSize:     "9px"
    fontWeight:   "500"
    padding:      "2px 8px"
  progress-circle:
    size:      "64px"
    trackColor: "{colors.gray-100}"
    lineWidth:  "5px"
  gauge:
    trackColor: "{colors.gray-100}"
    lineWidth:  "10px"
  avatar-panel:
    backgroundColor: "{colors.surface-dark}"
    wireframeBase:   "{colors.blue-wire}"
    wireframeGlow:   "{colors.blue-glow}"
    wireframeOpacity: "0.55"
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

AgeSignal is a personal health companion — not a clinical tool. The primary user is a non-medical patient who receives lab analysis results and wants to understand what they mean without anxiety. Every design decision must serve two goals: **immediate reassurance** (or a clear, calm alert) and **plain language understanding**.

The interface has a dual aesthetic: a clean, light clinical surface for data cards and metrics, and a dark scanner-style panel for the 3D wireframe avatar. The contrast between the two is intentional — the dark panel signals precision and technology, the light surfaces signal clarity and accessibility.

## Color Philosophy

Color in AgeSignal carries clinical meaning. The three-tier semantic system (ok / warn / low) is used exclusively for health status. Never use these colors decoratively or for branding.

**Status ok — green `#1D9E75`:** Value within normal range. Used for badge background fill (`#EAF3DE`), bar fill, progress arc, avatar zone color. Text on green backgrounds: `#3B6D11`.

**Status warn — amber `#EF9F27`:** Value slightly outside normal range. Not dangerous — requires attention and discussion with a doctor. Used for banner background tint, badges, avatar zones. Text on amber backgrounds: `#854F0B`.

**Status low/high — red `#E24B4A`:** Value significantly outside range. Clear alert. Used for card border accent, alert box, avatar critical zones. Text on red backgrounds: `#791F1F`. This color is reserved for genuine clinical alerts — never use it for minor deviations.

**Brand blue `#005cb6`:** Used only for interactive elements (buttons, links, AI send button, selected states). Never for status encoding.

**Page background `#f0f4f8`:** Warm off-white with a slight blue tint. Not pure white — creates a subtle depth between page and card surfaces.

**Dark panel `#060d1a`:** Deep navy, used exclusively for the 3D avatar container. The wireframe mesh lives here. Never use as a general card or section background.

## Typography

Single typeface throughout: Inter or DM Sans. Clean, geometric, highly legible at small sizes. No serif fonts — this is a UI, not editorial content.

**Score number (36px, weight 500):** Used only for the Health Score metric. Maximum visual prominence. Color: `#111922`.

**Heading (15px, weight 500):** Patient name in header, panel titles. Color: `#111922`.

**Label (12px, weight 500):** Parameter names inside cards, section headers. Color: `#111922`.

**Body (11px, weight 400):** Human language explanations, trend descriptions, AI chat messages. Line-height: 1.45. Color: `#6b7178` for secondary context, `#111922` for primary content.

**Caption (10px, weight 400):** Dates, units, metadata. Color: `#6b7178`. Minimum size — never go below 10px.

**Uppercase labels (10px, weight 400, letter-spacing 0.05em):** Section dividers like "PARAMETRI SETTIMANALI", "AI CARE". Color: `#6b7178`. Used sparingly — only for major section identification.

## Layout

Three-column grid on desktop. Columns: left (1.1fr) · center (1.6fr) · right (1.1fr). Gap: 10px. Outer padding: 10px. Border-radius on main container: 16px.

**Left column:** Health score card → weekly metric progress circles → appointments list.

**Center column:** 3D wireframe avatar with diagnostic pins → trend line chart (6 months) → horizontal value bars with badges.

**Right column:** Blood pressure gauge → AI Care chat → BMI card.

**Header:** Full width. White surface. Patient avatar circle (initials) + name + greeting left. Last analysis date right. Height: ~58px.

**Status banner:** Full width. Appears immediately below header. Background tinted with dominant status color (amber if warnings present, red if critical values present, green if all normal). Contains status sentence + subtitle listing specific out-of-range parameters. Never shows generic text — always names the specific issue.

**Footer:** Full width. White surface. Last analysis date left. Download PDF button right.

## Components

### Health Score Card
Large centered number (36px) representing overall health score 0–100. Below: "su 100" in muted caption. Below: comparison string in green — "Meglio del X% delle persone" — translates the score into a human benchmark. Below: horizontal progress bar, full width, green-to-teal gradient fill proportional to score. Background: white card, standard border.

### Progress Circles
64×64px canvas elements. Track: `#f0f0f0`, 5px line width. Arc: status color, 5px line width, rounded linecap. Start: top (−π/2). Percentage value centered inside in 11px bold. Used in a 2×2 grid for the four main parameters.

### Value Bars
Horizontal layout per parameter: parameter name (80px fixed, 11px, muted) → bar track (flex 1, 5px height, `#f0f0f0`, rounded) → bar fill (status color) → numeric value (70px fixed, 11px, bold) → status badge (pill). Bar fill width percentage represents position within/outside normal range relative to max measurable value.

### Gauge (Blood Pressure)
Semicircular gauge drawn on canvas. Track arc: `#f0f0f0`, 10px line width. Fill arc: status color, 10px line width, rounded linecap. Needle: thin line from center, `#111922`, 2px. Value label centered below arc: systolic/diastolic in 14px bold, unit in 10px muted. Status label below: "Normale / Attenzione / Alto".

### 3D Wireframe Avatar
Canvas rendered with Three.js r128. Background: `#060d1a`. Camera: perspective 42°, position z=5. Ambient light `#223355`. Directional light `#4488bb` from top-right. Floor grid: `#0d1f3a` / `#0a1628`, y=−1.5.

Body construction: geometric primitives (SphereGeometry, CylinderGeometry) assembled as anatomical skeleton. Base wireframe: `#1e5fa0` at 40–55% opacity. Joint nodes: `#2d8fe0` at 55% opacity, slightly larger spheres. Spine: small spheres aligned vertically, `#3366aa`.

Body proportions scale with BMI: thin for underweight (BMI<18.5), proportionate for normal (18.5–25), wider torso+limbs for overweight (25–30), clearly round silhouette for obese (>30).

Diagnostic zones (color driven by lab values):
- Heart sphere (chest, slightly left): pulsates with sine animation. Color: ok/warn/low based on cholesterol.
- Abdomen sphere (center torso): color based on blood glucose.
- Knee spheres (both): color based on Vitamin D.

Overlay pins (HTML absolutely positioned over canvas): small colored dot + connector line + label bubble. Each pin identifies the diagnostic zone and its status with a brief label ("Colesterolo ⚠" / "Glicemia ✓"). Pins use `pointer-events: none`.

Avatar is draggable: mousedown/mousemove/touchmove rotate Y axis freely, X axis clamped −0.6 to +0.7 rad.

### AI Care Chat
Compact chat interface. Bubbles: assistant messages in `#f5f7f9` background, user messages in `#B5D4F4` (light blue), right-aligned. Font 11px, line-height 1.45. Input row: text input + send button (`#005cb6` background, white text). The AI assistant speaks in plain Italian, never uses medical jargon, always frames advice positively. Beta badge in header.

### Status Badge (pill)
Border-radius full (9999px). Font 9px, weight 500. Padding 2px 8px. Three variants:
- ok: background `#EAF3DE`, text `#3B6D11`
- warn (alto/attenzione): background `#FAEEDA`, text `#854F0B`
- low/critical (basso): background `#FCEBEB`, text `#791F1F`

### Alert Banner
Full width strip below header. Visible only when one or more values are warn or critical. Background: `#FAEEDA` (warn) or `#FCEBEB` (critical). Left: icon + bold status sentence + subtitle. Right (if critical): "Contatta il medico" button — solid `#A32D2D` background, white text, 5px border-radius, 10px font. The banner text always names the specific parameters, never generic.

### Appointments List
Stack of appointment rows. Each row: colored dot (status color matching urgency) + doctor name + specialty (11px bold + 10px muted) + time (10px muted, right-aligned). No borders between rows — breathing room via gap: 6px is sufficient.

## Clinical Reference Data

These ranges govern all status color assignments in the interface.

| Parameter | Unit | Green (ok) | Amber (warn) | Red (critical) |
|---|---|---|---|---|
| Blood glucose | mg/dL | 70–100 | 100–125 or 60–70 | >125 or <60 |
| Total cholesterol | mg/dL | <200 | 200–240 | >240 |
| Hemoglobin (M) | g/dL | 13.5–17.5 | 12–13.5 or 17.5–18.5 | <12 or >18.5 |
| Hemoglobin (F) | g/dL | 12–16 | 11–12 or 16–17 | <11 or >17 |
| Vitamin D | ng/mL | ≥30 | 20–29 | <20 |
| BMI | — | 18.5–24.9 | 25–29.9 or 17–18.5 | >30 or <17 |
| Systolic BP | mmHg | <120 | 120–139 | ≥140 |
| Diastolic BP | mmHg | <80 | 80–89 | ≥90 |

## Do's and Don'ts

**Do:**
- Always show a human language explanation alongside every numeric value
- Use trend arrows (↑ ↓ →) to show direction of change — the patient cares more about trend than absolute value
- Show the health score comparison ("better than X% of people") — benchmarking is more meaningful than raw numbers
- Keep the AI assistant tone calm, warm, and practical — food suggestions, lifestyle tips, never diagnoses
- Use the dark avatar panel as the emotional center of the dashboard — it creates identity and engagement
- Show the status banner only when needed — if everything is normal, replace it with a positive confirmation

**Don't:**
- Never show raw values without explanation
- Never use red for minor deviations — red is reserved for values that genuinely warrant medical contact
- Never show medical jargon in patient-facing text (no "hypercholesterolemia", no "hyponatremia")
- Never stack more than 4 cards in the right column — cognitive overload kills trust
- Never animate anything except the heartbeat pulse on the avatar — motion should be functional, not decorative
- Never use the brand blue (#005cb6) for status encoding — it is an interaction color only
- Never remove the AI Care component — it is the primary engagement driver for repeat visits
