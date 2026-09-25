# Toolntip Core v1.0 — Foundation Complete

## 1.2.0-dev.10.4 — Storage Replica Save Intelligence & Messaging

- Added verification-aware Storage Replica save behavior: new replicas and provider/file-reference changes automatically run provider verification after persistence.
- Routing-only edits (enabled, priority, weight) preserve the last explicit verification result while immediately invalidating live delivery-health routing cache.
- Explicit Verify now invalidates stale live delivery-health cache so recovered providers can re-enter routing immediately.
- Added provider-aware, self-explanatory Storage Replica notices for add/update/verify/remove operations and safe verification failures.
- Added warning-state admin feedback when a replica saves successfully but automatic provider verification needs attention.
- Product database schema remains version 1.


## Release Summary

Toolntip Core v1.0 establishes the architectural foundation for the Toolntip platform. This release focuses on building a scalable, maintainable plugin architecture rather than maximizing feature count.

## Highlights

### Core Architecture

* Modular plugin bootstrap
* Centralized loader architecture
* Component-based rendering engine
* Normalized Tool Data Model
* Reusable helper modules

### Tool Components

* Hero
* Rating
* Gallery
* Features
* Pros & Cons
* FAQ
* Buttons
* Schema support

### Custom Fields

* Advanced Custom Fields Pro integration
* Repeater support
* Gallery support
* Flexible helper architecture

### Documentation

* Architecture review completed
* Product roadmap established
* Backlog introduced
* Tool Detail Page blueprint completed

## Design Principles

* Templates contain presentation only.
* Data preparation belongs in helper functions.
* Components are modular and reusable.
* Every feature must solve a real user problem before it solves a technical problem.

## Status

Foundation complete.

Future development will focus on expanding the product using the established architecture rather than redesigning it.

##############
Added

Changed

Fixed

Removed

### Added

- Introduced the Discovery Engine public API:
  - `tnt_get_related_tools()`
- Added candidate retrieval stage as the first step in the Related Tools pipeline.
- Established a stable public API before implementing recommendation logic.

##Related Tools Engine:
Related Tools Engine:
 Added weighted relevance scoring (Category, Tags, Tool Type, Platform, Pricing, Featured), score-based sorting, and filtering to return only relevant tools.
 
 ##Version 1.2 – 
 
 Introduced modular Tool Data Provider architecture.