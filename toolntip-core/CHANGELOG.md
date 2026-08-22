## 1.0.38 — WEB-007.4 / 4.5.8.1 Resource Featured Image Presentation
- Refined Resource Card featured-image presentation to reduce excessive built-in canvas whitespace.
- Preserved image proportions and the established Resource Card media frame.
- Kept Featured ribbon, Resource Type badge, queries, Hub monetization, routing and A6 relationship behavior unchanged.

## 1.0.37 — WEB-007.4 / 4.5.8 Featured Resource Support
- Added Core-owned `tnt_resource_featured` editorial metadata with ACF Pro and native fallback controls.
- Added featured-only Resource query support and `[tnt_resources featured="yes"]`.
- Added Featured state to canonical Resource Card data.
- Added the established ToolNTip `★ FEATURED` ribbon treatment to Resource Cards.
- Preserved Resource routing, Hub monetization, pagination and A6 relationship-query behavior.

## 1.0.36
- Integrated the Resource Hub directly with the native `/resources/` CPT archive.
- Added plugin-owned `archive-resource.php` routing.
- Reused the WordPress main Resource archive query to avoid duplicate collection queries.
- Added archive search alignment for `resource_search` and canonical 12-item pagination.

## 1.0.35 — WEB-007.4 / 4.5 Resource Hub

- Added the `[tnt_resource_hub]` discovery surface for `/resources/`.
- Added GET-based Resource search using the frozen Resource Query Engine.
- Added Resource Type and Resource-only Topic discovery using canonical routes.
- Added bounded Hub pagination with search-state preservation.
- Reused the frozen Resource Card and Resource Collection renderer without duplicating query or presentation ownership.
- Added responsive Resource Hub presentation styles and kept Tool/Resource isolation intact.
- Preserved the A6 optimized relationship lookup path; no catalog-wide Resource relationship scan was introduced.

## 1.0.34 — WEB-007.4 / 4.3 Resource Query Engine

- Added the canonical Resource query helper with filtering, ordering, pagination and relationship-aware queries.
- Added Resource → Tool reverse discovery and bidirectional Resource ↔ Resource relationship query support.
- Added the `[tnt_resources]` shortcode with Resource Type, Topic, Tag, search, ordering and limit controls.
- Added fail-closed validation for invalid relationship query targets.
- Preserved Resource and Tool shortcode post-type isolation.
- Restored the approved three-column default for Tool collections while preserving explicit column overrides.
- Completed Resource Query Engine validation with the query matrix, relationship regression and platform regression gates passing.
## 1.0.14 — WEB-007.4 / 4.2 Resource Editorial Foundation

- Added single-selection Resource Type editorial UX with publication enforcement.
- Added controlled Resource admin taxonomy filters.
- Added Core-owned ordered Resource → Tool and Resource → Resource relationship metadata.
- Added ACF Pro relationship selectors with Core validation and a native fallback editor.
- Added self-reference protection and published-target filtering for relationship selection.


## 1.0.13 — WEB-007.4 / 4.1 taxonomy routing patch

- Added explicit canonical rewrite rules for Resource Type and Resource Topic archives.
- Added pagination-aware taxonomy rewrite rules.
- Bumped Resource domain schema to 1.1 to perform a one-time rewrite flush.
v1.0.0
- Initial plugin
- Plugin constants
- Asset loading
- First shortcode

v1.1.0
- Tool Card component