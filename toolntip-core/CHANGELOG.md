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