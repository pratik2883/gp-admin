# Design System Strategy: The Clinical Editorial

## 1. Overview & Creative North Star
**Creative North Star: "The Clinical Curator"**
This design system is built to honor the expertise of senior medical professionals. It rejects the frantic, "app-like" clutter of consumer software in favor of an editorial-grade interface. By leveraging vast white space, authoritative typography, and a "layered paper" philosophy, the system creates an environment of calm, deliberate action. 

The aesthetic breaks from the traditional grid through **intentional asymmetry**—offsetting labels and using varied container widths to guide the eye toward critical patient data. It is a high-contrast, "light-first" system where depth is felt through tonal shifts rather than seen through heavy outlines.

---

## 2. Colors
Our palette is anchored by a deep, authoritative blue, supported by a spectrum of "cool grays" that define the physical structure of the interface.

*   **Primary (`#003f98`)**: Used for the signature top app bar and high-priority actions.
*   **Surface Hierarchy (The Foundation)**:
    *   `surface`: The base layer, a crisp off-white for the main background.
    *   `surface_container_lowest`: Pure white (`#ffffff`). Reserved for the primary content cards to maximize contrast and "pop" against the background.
    *   `surface_container_low`: Used for secondary sectioning or grouped information.
*   **The "No-Line" Rule**: To maintain a premium, editorial feel, **do not use 1px solid borders** for sectioning. Boundaries must be defined by shifting from `surface` to `surface_container_low`.
*   **The "Glass & Gradient" Rule**: 
    *   The top app bar and Primary CTA buttons must use a subtle linear gradient: `primary` (`#003f98`) to `primary_container` (`#1a56be`) at a 135-degree angle.
    *   Floating notifications or mobile navigation bars should utilize **Glassmorphism**: `surface` color at 85% opacity with a `12px` backdrop-blur to allow underlying content to softly bleed through.

---

## 3. Typography
We utilize **Manrope**, a modern sans-serif with excellent legibility at larger scales, to ensure the UI feels like a high-end medical journal.

*   **Display & Headline (24px - 56px)**: Set with tighter letter-spacing (-2%) to feel authoritative. Headlines (`headline-lg` at 32px) are used to announce new sections with "breathing room" (minimum `spacing-8` above).
*   **Body (Minimum 16px)**: We strictly follow the `body-lg` token (16px) for all patient notes and referral details. Never drop below 14px for secondary information.
*   **Information Hierarchy**: Bold weights are reserved for patient names and primary metrics. Regular weights are used for labels to prevent the UI from feeling "heavy."

---

## 4. Elevation & Depth
Hierarchy is achieved through **Tonal Layering**, mimicking the look of physical documents stacked on a clean desk.

*   **The Layering Principle**: Instead of shadows, place a `surface_container_lowest` card (Pure White) atop a `surface_container_low` background. The subtle 2-3% difference in hex value provides a sophisticated, "soft" lift.
*   **Ambient Shadows**: For elements that truly float (like a `Notification` modal), use a high-dispersion shadow: 
    *   *Offset: 0, 8px | Blur: 24px | Color: `on_surface` at 6% opacity.*
*   **The "Ghost Border" Fallback**: If a container requires a boundary (e.g., in a complex form), use a "Ghost Border": `outline_variant` at **15% opacity**.
*   **Roundedness**: Use `xl` (24px) for the top app bar's bottom corners and `lg` (16px) for all content cards to soften the clinical environment.

---

## 5. Components

### Buttons & Touch Targets
*   **Primary CTA**: Full-width on mobile. Uses the signature blue gradient. Minimum height: `spacing-10` (56px) to ensure high-accuracy touch targets for busy doctors.
*   **Secondary/Action Chips**: Use `surface_container_high` with `primary` text. No borders.

### Cards & Lists
*   **Referral Cards**: Use a `surface_container_lowest` background with a `16px` radius. 
*   **Forbidden**: Never use divider lines between list items. Use `spacing-4` (22.4px) of vertical white space or a subtle background shift to separate "Dr. Amit Mehta" from "Dr. Sen."

### Inputs & Fields
*   **Floating Labels**: Labels use `label-md` and sit on a `surface_container_low` background. 
*   **Interaction**: On focus, the field should not change border color; instead, it should gain a subtle `surface_tint` ambient glow.

### Specialized Components
*   **The Status Badge**: For "Pending" or "New" referrals, use `tertiary_container` with `on_tertiary_fixed_variant` text. These should be pill-shaped (`full` roundedness) and placed in the top-right of cards.
*   **PDF/Report Previews**: Use a `surface_variant` icon container with `body-md` text. The interaction area must be the full width of the parent card.

---

## 6. Do's and Don'ts

### Do:
*   **Do** use asymmetrical margins. For example, a wider left margin for labels than right margins for data.
*   **Do** prioritize `surface_container` nesting to show parent-child relationships.
*   **Do** use `display-sm` (36px) for key metrics like "64% Conversion Rate" to make them the focal point.

### Don't:
*   **Don't** use 100% black text. Always use `on_surface` (`#191c1e`) for a softer, more premium contrast.
*   **Don't** use "drop shadows" that are visible as gray streaks. If the shadow is noticeable, it is too heavy.
*   **Don't** cram multiple actions into a single row. Respect the "one primary action per view" rule to maintain a calm user experience.