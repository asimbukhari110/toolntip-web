1. Plugin Architecture

2. Folder Structure

3. Data Flow

4. Rendering Engine

5. Helper Modules

6. Template System

7. Coding Principles

8. Future Architecture

### Related Tools Module

The Related Tools feature follows the standard Toolntip helper architecture.

Public API:

- `tnt_get_related_tools( $tool, $limit = 6 )`

Design principles:

- One public entry point per helper module.
- Internal implementation details remain encapsulated.
- Returns normalized Tool Data.
- UI components never perform recommendation logic.

### Discovery Engine Architecture

The Related Tools feature is implemented as a processing pipeline.

```
Current Tool
      ↓
Candidate Retrieval
      ↓
Score Calculation
      ↓
Sorting
      ↓
Normalization
      ↓
UI Rendering
```

Responsibilities are intentionally separated so that retrieval, scoring, and presentation can evolve independently.

### Discovery Engine Design

The recommendation engine operates on the Tool Data Model rather than directly querying WordPress or ACF data.

Benefits:

- Centralized data normalization
- Reduced duplication
- Easier maintenance
- Stable API between modules
- Simplified future enhancements

Recommendation algorithms should consume normalized tool data whenever practical, rather than interacting directly with WordPress APIs.

## Layered Architecture

Toolntip follows a layered architecture.

### Presentation Layer

Responsible only for rendering HTML.

Examples:

- templates/
- template parts

Must not access WordPress APIs directly.

---

### Component Layer

Responsible for composing the UI.

Examples:

- tnt_render()

---

### Business Logic Layer

Responsible for product behavior.

Examples:

- Tool Data
- Discovery Engine
- Ratings
- Gallery
- Similar Tools

Business logic consumes normalized Toolntip data.

---

### Data Layer

Responsible for interacting with WordPress.

Examples:

- WP_Query
- get_post()
- get_the_terms()
- get_field()

WordPress APIs should remain isolated within helper functions whenever practical.

---

### Guiding Principle

Each layer communicates only with the layer directly beneath it.

### Component View Models

Presentation components should receive only the data they require.

Example:

- `hero.php` receives a Hero View Model.
- `gallery.php` receives Gallery Data.
- `rating.php` receives Rating Data.

Components should not depend on the complete Tool Data Model when a focused view model is sufficient.

Benefits:

- Reduced coupling
- Simpler templates
- Easier maintenance
- Better component reusability

## Tool Detail Page Assembly

The Tool Detail Page is assembled by `templates/tool-detail.php`.

Responsibilities:

- Define the order of sections.
- Render component templates.
- Contain no business logic.
- Perform no WordPress data access.

All data should be prepared before the page template is loaded.

### Helper Organization

Helper files should be organized by domain responsibility rather than UI section.

Examples:

- `helpers-media.php` → logos, screenshots, featured images
- `helpers-video.php` → video metadata
- `helpers-faq.php` → FAQ data
- `helpers-schema.php` → structured data

Presentation templates remain organized by UI sections (e.g., `screenshots.php`, `about.php`, `faq.php`).

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

## Design Principle: Discovery vs. Decision

Toolntip uses two presentation types:

### Tool Card
Purpose: Discovery

Displays a concise summary to encourage users to explore a tool.

Typical usage:
- Homepage
- Search results
- Category archives
- Related Tools

### Tool Detail Page
Purpose: Decision

Provides comprehensive information to help users evaluate a tool.

The detail page follows the canonical section order:

1. Hero
2. Screenshots
3. About
4. Features
5. Pros & Cons
6. Video
7. FAQ
8. Similar Tools
9. Tags

Cards should summarize; detail pages should explain.

## Tool Detail Page Structure

The Tool Detail page is composed of semantic HTML elements.

Structure:

<article class="tnt-tool-detail">

    <header>
        Hero
    </header>

    <main>
        Screenshots
        About
        Features
        Pros & Cons
        Video
        FAQ
        Similar Tools
        Tags
    </main>

</article>

Each content block is implemented as an independent template component rendered through `tnt_render()`.

## Template Organization

Top-level templates represent complete page layouts.

Examples:
- tool-detail.php

Reusable UI components are stored under:

templates/parts/

Examples:
- hero.php
- features.php
- screenshots.php
- tool-card.php
- similar-tools.php

This separation distinguishes full-page layouts from reusable presentation components.

## Template Component Hierarchy

Tool Detail Page
│
├── Hero
├── Screenshots
├── About
├── Features
├── Pros & Cons
├── Video
├── FAQ
├── Similar Tools
└── Tags

The Hero section is composed of smaller reusable components such as Logo, Title, Rating, Metadata, and Buttons.

This keeps the page layout concise while allowing individual Hero elements to be reused or modified independently.

## Architecture Freeze (Version 1.0)

After the initial architecture was established, the project entered an implementation phase.

Until Version 1.0 is complete:

- Avoid unnecessary file moves.
- Avoid unnecessary renaming.
- Prefer extending existing helper modules over introducing new architectural patterns.
- Focus on delivering user-facing functionality.

Architectural refinements are planned for Version 1.1 after the feature set is complete.

## Include File Organization

The plugin loader groups files in the following order:

1. Core initialization
2. Framework utilities
3. Tool helper modules
4. Taxonomies

This organization keeps dependencies predictable and makes new helper modules easy to locate.

## Housekeeping Principles

The codebase follows these maintenance rules:

- Do not keep empty placeholder files.
- Do not keep empty directories.
- Avoid duplicate helper modules with overlapping responsibilities.
- Introduce new files only when they contain functional code.
- Organize files by responsibility rather than anticipated future features.

## Helper Module Guidelines

Each helper module represents a functional domain rather than a single function.

Examples:

- helpers-media.php
- helpers-taxonomies.php
- helpers-video.php
- helpers-pros-cons.php

As the project evolves, new helper functions should be added to an existing domain module when they share the same responsibility, instead of creating a new helper file for every individual function.

## Recommendation Engine (Version 1.0)

The Similar Tools feature uses a weighted scoring algorithm rather than a simple taxonomy query.

Each candidate tool is evaluated using multiple relevance signals (category, tags, platform, pricing, and developer). The final score determines ranking, allowing the recommendation engine to evolve without changing the rendering layer.

### Recommendation Engine Implementation

The recommendation engine is being implemented incrementally.

Phase 1:
- Candidate selection
- Category-based scoring

Additional relevance signals (tags, platform, pricing, developer) are added in subsequent phases to keep each implementation step small, testable, and easy to debug.

## Related Tools Engine

Related tools are generated dynamically using a weighted scoring system.

Current weights:

- Same Category: 100
- Shared Tags: 20 each (max 60)
- Same Tool Type: 15
- Same Platform: 10
- Same Pricing: 10
- Featured Tool: 5

Only tools with a minimum score of 100 are displayed. Results are sorted by descending score.

### Related Tool Candidate Selection

The related tools engine does not scan the entire Tool database.

Candidates are first restricted to the same `tool_category`, after which the weighted similarity algorithm ranks the results.

This reduces database load and scales efficiently as the Tool database grows.

## Comparison Engine

The comparison engine is built on top of the Tool Data Layer.

Each comparison receives two Tool Data Arrays and renders all comparison rows using shared template parts.

This avoids duplicate ACF lookups and guarantees consistency with Tool Detail pages.

## Comparison Engine

The comparison engine is built using the same architecture as the Tool Detail page.

All comparison views consume Tool Data Arrays produced by the Tool Data Layer. Rendering is delegated to modular template parts, ensuring consistency, maintainability, and minimal duplication.

##Data Provider Layer – 

Explain that tool data is assembled from specialized provider functions instead of one monolithic function.