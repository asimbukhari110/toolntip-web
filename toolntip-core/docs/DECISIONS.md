# 2026-07-13

Decision:
Use WordPress taxonomies for Categories and Tags instead of ACF fields.

Reason:
Supports archive pages, SEO, filtering, REST API, and related tools.

Impact:
Future recommendation engine will use taxonomy relationships.


##Decision ID: PD-000

Title:
Launch Toolntip with a Curated Category Set

Decision:

Toolntip Version 1.0 will launch with a carefully curated
set of software categories rather than attempting to
cover every software category.

Reason:

- Higher quality content
- Better SEO
- Easier navigation
- Faster content production
- Better user trust
- Simpler taxonomy management

Future:

Additional categories will be added only after there
is sufficient content to support them.

##PD-001

Templates never call get_field().

Reason:
Centralized data layer.

##Decision ID: PD-002

Title:
One Primary Category Per Tool

Decision:

Each Tool in Toolntip will belong to one primary
Category and may have multiple Tags.

Reason:

- Cleaner navigation
- Better SEO
- Easier recommendations
- Simpler taxonomy
- Better user experience

### Product Decision PD-003

## Title

Related Tools Strategy

## Status

Approved

## Decision

Toolntip will display **6 Related Tools** on each Tool Detail page.

The recommendation engine will score all potential candidates and return the best matches. The UI will display only the highest-ranked six tools.

## Ranking Priority

1. Same Category
2. Shared Tags
3. Same Pricing Model
4. Same Platform
5. Future AI recommendations (Version 3.x)

## Rationale

* Six tools fit naturally into responsive layouts.
* Provides enough discovery without overwhelming users.
* Encourages internal navigation.
* Improves SEO through contextual internal linking.
* Allows future expansion without changing the user interface.

## Future Enhancements

* Personalized recommendations
* Recently viewed tools
* Trending tools
* AI-assisted recommendations
* "Users also viewed" suggestions
* Configurable display limit

## PD-005 — Discovery Engine v1

**Status:** Approved

### Decision

The Similar Tools feature will use a weighted scoring algorithm based on existing tool metadata.

### Scoring Signals

- Same Category: +50
- Shared Tag: +20 per tag
- Same Pricing: +10
- Same Platform: +10
- Same Developer: +5

The current tool must always be excluded.

The algorithm returns normalized Tool Data sorted by score. The UI displays the top six results.

### Rationale

- Simple and deterministic
- Easy to debug
- Uses existing metadata
- Scales to future enhancements
- Integrates naturally with the existing Tool Data Model

## PD-006 — Public Helper API Design

**Status:** Approved

Public helper functions should describe **what they return**, not **how they work**.

Examples:

- `tnt_get_tool_data()`
- `tnt_get_tool_gallery()`
- `tnt_get_related_tools()`

Implementation details such as querying, scoring, sorting, or caching remain encapsulated within private helper functions.

This keeps the public API stable even if the internal implementation changes.

## PD-007 — Related Tool Eligibility

**Status:** Approved (Proposed)

Only tools with a positive relatedness score are eligible for the Similar Tools section.

The Discovery Engine will not display unrelated tools merely to satisfy the display limit.

Quality of recommendations takes precedence over quantity.

## PD-008 — Similar Tools Rendering

**Status:** Approved

The Similar Tools section is optional.

It is rendered only when the Discovery Engine returns one or more related tools.

The section is omitted entirely when no eligible tools are found.

### Rationale

Showing fewer high-quality recommendations provides a better user experience than displaying unrelated tools or empty placeholders.

---

## PD-009 — Discovery Engine Pipeline

**Status:** Approved

The Discovery Engine follows a staged processing pipeline:

Current Tool
↓
Candidate Retrieval
↓
Relatedness Scoring
↓
Filter (score > 0)
↓
Sort
↓
Limit
↓
Normalize
↓
Render

Each stage has a single responsibility, allowing the recommendation engine to evolve without changing the public API.

## PD-010 — Normalized Taxonomy Data

**Status:** Approved

Category and Tag helpers should return normalized taxonomy objects rather than raw term IDs.

Recommended structure:

- id
- name
- slug

This provides sufficient information for both business logic (comparison) and presentation (UI) without requiring additional taxonomy queries.

## PD-011 — Helper Return Values

**Status:** Approved

Public helper functions should return Toolntip's normalized data structures rather than native WordPress objects whenever practical.

### Example

Instead of returning `WP_Term` objects directly, taxonomy helpers should return normalized arrays containing:

- `id`
- `name`
- `slug`

### Rationale

- Decouples business logic from WordPress internals.
- Creates a stable internal API.
- Simplifies templates and future integrations.
- Makes future migrations or caching strategies easier.

## PD-012 — Tool Detail Blueprint v1.0

**Status:** Approved

The Tool Detail Page Blueprint v1.0 is considered the canonical layout specification for Toolntip.

The blueprint defines:

- Section order
- Information hierarchy
- UX priorities
- Performance guidelines
- Mobile behavior

Implementation should follow the approved blueprint. Layout changes require a product decision supported by user feedback, analytics, or a demonstrated usability improvement.

### Guiding Principle

The blueprint is stable. New features should fit into the blueprint rather than prompting frequent layout redesigns.

## PD-013 — Product Vocabulary

**Status:** Approved

Toolntip code should use the same terminology as the product wherever practical.

Approved terminology includes:

- Screenshots (not Gallery)
- Similar Tools (not Related Tools in the UI)
- Use Tool
- Editorial Summary

The codebase should adopt product terminology early in development to maintain consistency between design, documentation, and implementation.

Exceptions may be made for established WordPress terminology (e.g., `featured_image`).

### Shortcode Rendering Flow

The `[tnt_tool]` shortcode supports multiple rendering modes.

Current modes:

- `template="detail"` → Full Tool Detail page
- `template="card"` → Tool Card

The shortcode is responsible only for:

1. Reading shortcode attributes.
2. Loading normalized tool data.
3. Selecting the appropriate template.

All presentation logic belongs in the template layer.