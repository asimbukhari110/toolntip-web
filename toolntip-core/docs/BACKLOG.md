Release v1.5.0 – Plugin Lifecycle

Add activation hook
Add deactivation hook
Add uninstall.php
Add plugin basename constant
Add text domain loading
Add upgrade/migration framework

## Architecture

- [ ] Move helper files into includes/helpers/
- [ ] Create helpers/load.php
- [ ] Create taxonomies/load.php
- [ ] Add schema/load.php
- [ ] Introduce PSR-4 autoloading when classes are introduced


## Helpers / Data Layer

- [ ] Remove duplicate gallery array key from `tnt_get_tool_data()`
- [ ] Group returned data into logical sections (Basic, Media, Reviews, Metadata)
- [ ] Create `tnt_format_date()` helper
- [ ] Reduce repeated ACF lookups where practical
- [ ] Support partial data loading (selective fields)
- [ ] Add object caching for normalized tool data
- [ ] Introduce a Tool Repository class when the plugin adopts OOP

## Platform

- [ ] Evaluate replacing CPT UI with native post type registration after Version 2.0.

## Taxonomies

- [ ] Add helper to return the primary category.

##🎯 One New ACF Field

I'd like to add one field in the future:

Label	Name	Type
Editor's Summary	editor_summary	WYSIWYG (or Textarea)

This powers the "Why We Like It" section.

##One more architectural thought

I think we've reached the point where we should stop thinking in terms of "helper files" alone.

Your plugin is evolving into a domain model. In future versions, I'd like to organize functionality into domains such as:

includes/
├── tool/
│   ├── data.php
│   ├── rating.php
│   ├── gallery.php
│   ├── related.php
│   └── schema.php
├── taxonomy/
├── render/
└── admin/

## Architecture v2 — Component View Models

Priority: Medium

Evaluate introducing dedicated view models for presentation components.

Current approach:
- Component helpers fetch their own WordPress/ACF data.

Proposed approach:
- `tnt_get_tool_data()` provides the canonical Tool Data Model.
- Component helpers transform the canonical model into component-specific view models.
- Presentation templates consume only view models.

Expected benefits:
- Reduced duplicate data access
- Clear separation between domain and presentation
- Easier testing and future API reuse

## Frontend Architecture

Priority: Medium

Create a shared component stylesheet (`components.css`) for reusable UI patterns.

Candidate shared classes:

- `.tnt-section`
- `.tnt-section-title`
- `.tnt-card`
- `.tnt-grid`
- `.tnt-button`
- `.tnt-badge`

Component-specific styles (e.g., `screenshots.css`, `hero.css`, `faq.css`) should contain only styles unique to that component.

## Features v2

Priority: Low

Extend feature items beyond plain text.

Potential fields:

- title
- icon
- highlight
- documentation_url

This enables richer presentation and future filtering while maintaining backward compatibility.

## Rendering Layer Improvements

Priority: Medium

Evaluate replacing `extract()` in `tnt_render_template()` with explicit variables or a view-model object in a future version.

Benefits:

- Better IDE support
- Easier debugging
- Reduced risk of variable collisions

## Future Improvement

When Toolntip Core replaces CPT UI and registers its own Custom Post Types, add an activation hook to flush rewrite rules once during plugin activation.

Reason:
- Automatically registers custom URLs.
- Prevents 404 errors after plugin activation.
- Avoids requiring administrators to manually save permalink settings.

## Template Naming Review

Priority: Medium

Consider simplifying template filenames:

- tool-card-default.php → tool-card.php

If multiple card layouts are introduced later, use descriptive names such as:

- tool-card-compact.php
- tool-card-horizontal.php
- tool-card-featured.php

## Tool Card Redesign

Priority: High

Redesign `tool-card-default.php` as a compact summary card rather than a mini detail page.

Suggested contents:

- Logo
- Tool Name
- Tagline
- Rating
- Pricing
- Platform
- Category
- 2–3 key feature highlights
- Use Tool button
- View Details button

Purpose:
- Homepage
- Search results
- Category archives
- Related Tools
- Widgets

Avoid including:
- Full screenshots gallery
- FAQ
- Video
- Complete Pros & Cons
- Long descriptions

