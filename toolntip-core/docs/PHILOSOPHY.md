# Toolntip Development Philosophy

## Product First

Help users discover the right tool faster.

## User-Centered Design

Every feature must solve a real user problem before it solves a technical problem.

## Clean Architecture

Templates are responsible for presentation.

Helpers are responsible for data preparation.

## Simplicity

Choose the simplest design that still scales.

## Consistency

Consistency is more valuable than cleverness.

## Documentation

If a decision required discussion, it probably deserves documentation.

## Definition of Done

A feature is complete only when:

- Product design is approved.
- Code is implemented.
- Architecture is reviewed.
- Documentation is updated.
- The feature is ready for commit.

## Product-First Architecture

Toolntip is designed as a software product that runs on WordPress, not as a collection of WordPress templates.

### Principle

Everything enters Toolntip as a WordPress object and leaves as a Toolntip object.

Examples:

- `WP_Post` → Tool Data
- `WP_Term` → Category Data
- ACF Gallery → Gallery Data

Templates and business logic should consume Toolntip's normalized data structures rather than WordPress objects.

This creates a stable internal API, reduces coupling to WordPress, and simplifies future enhancements such as REST APIs, caching, headless frontends, or alternative data sources.