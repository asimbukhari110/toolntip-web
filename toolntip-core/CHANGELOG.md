## 1.0.55 — WEB-007.4 / 4.6-I DEF-002 Canonical Resource Canvas Alignment

- Promoted the existing 1440px Resource Detail outer canvas to the canonical structural width for identity, taxonomy, Featured Media, reading layout and comments.
- Preserved full-width Resource leaderboard monetization instead of reducing advertisement placement width.
- Preserved the separate 820px editorial content measure so long-form prose remains readable inside the wider structural layout.
- Preserved contextual/sidebar advertisement behavior; sidebar monetization remains constrained by the contextual sidebar rather than the 1440px page canvas.
- CSS-only corrective change; no PHP template, shortcode, monetization logic, Elementor boundary, data model or Resource architecture changes.

## 1.0.54 — WEB-007.4 / 4.6-I DEF-001 Resource Detail Structural Width Correction

- Corrected the Resource Detail identity and taxonomy wrappers to use the established 1180px structural width instead of the 820px editorial reading measure.
- Aligned Featured Media to the same 1180px structural width used by the Resource Detail reading layout.
- Preserved the separate 820px editorial content measure, title typography constraints, Elementor `the_content()` boundary, monetization, relationships, comments and responsive behavior.
- CSS-only corrective change; no PHP template, data model or Resource architecture changes.

## 1.0.53 — WEB-007.4 / 4.6-G4.2 Resource Placement Default Mapping & Upgrade Safety

- Mapped Resource Detail monetization defaults to the existing reusable ad-unit shortcodes: Leaderboard after hero, Rectangle in the sidebar, and Leaderboard before comments.
- Added nested settings merging so newly introduced placements and ad units become available on upgraded installations without overwriting existing administrator choices.
- Preserved the existing restricted `tnt_ad_*` shortcode bridge and fail-closed reusable ad-unit rendering.

## 1.0.52 — WEB-007.4 / 4.6-G4.1 Monetization Shortcode Bridge

- Added a narrow placement-rendering bridge for the existing `[tnt_ad_leaderboard]`, `[tnt_ad_rectangle]`, `[tnt_ad_horizontal]`, `[tnt_ad_sidebar]`, and `[tnt_ad_mobile]` reusable ad-unit shortcodes.
- Preserved trusted custom/ad-network HTML while restricting automatic shortcode expansion to ToolNTip's five registered monetization ad-unit shortcodes.
- Reused the existing centralized ad-unit renderer and settings; no new ad-unit mode, selector, storage model, or Resource-specific monetization engine was introduced.
- Preserved fail-closed placement behavior when a referenced reusable ad unit has no configured markup.

## 1.0.51 — WEB-007.4 / 4.6-G4 Resource Detail Monetization
- Added the three frozen Resource Detail monetization placements: `resource-after-hero`, `resource-sidebar`, and `resource-before-comments`.
- Reused the existing provider-neutral ToolNTip monetization registry, settings and placement renderer.
- Added Resource Detail placement spacing/disclosure treatment while preserving fail-closed rendering.
- Preserved the canonical `the_content()` boundary; no automatic mid-article injection was introduced.
- Kept advertising outside Related Tools/Related Resources and outside the Comments discussion.

## 1.0.50 — WEB-007.4 / 4.6-F4 Resource Comments
- Added ToolNTip Resource comments presentation on top of the native WordPress comment lifecycle; no custom comment storage or review/rating semantics were introduced.
- Enforced authenticated-only Resource comment submission independently of the global Discussion registration setting while keeping approved comments publicly readable.
- Added native threaded replies, avatars, moderation messaging, comment pagination, logged-in comment form and logged-out Sign In/Create Account CTA.
- Added the native `comment-reply` script only for singular Resources when threaded comments are enabled.
- Preserved the Resource reading/content boundary and the approved Related Tools/Related Resources contextual sidebar architecture.

## 1.0.49 — WEB-007.4 / 4.6-E2 Dynamic WordPress Site Icon Fallback
- Replaced the hardcoded site-root `/favicon.ico` fallback with the currently configured WordPress Site Icon resolved dynamically via `get_site_icon_url()`.
- Preserved Resource-specific icons as priority #1; if no Resource Icon or WordPress Site Icon exists, the resolver fails closed without emitting a broken image.
- Preserved all approved D/E relationship/sidebar behavior and Featured Image/editorial media semantics unchanged.

## 1.0.48 — WEB-007.4 / 4.6-E2 Resource Identity Icon Favicon Fallback
- Replaced the WordPress Site Icon → Custom Logo fallback chain with the site-root `/favicon.ico` fallback.
- Preserved Resource-specific icons as priority #1 and all approved D/E relationship/sidebar behavior unchanged.
- Featured Image/editorial media semantics remain unchanged.

## 1.0.47 — WEB-007.4 / 4.6-E2 Resource Identity Icon
- Added an optional ACF Resource Icon field for compact Resource identity without changing Featured Image/editorial media semantics.
- Added the canonical `tnt_get_resource_icon()` resolver: Resource Icon → WordPress Site Icon → Custom Logo fallback.
- Related Resources now consume the canonical resolved icon and no longer expose the generic grid placeholder.
- Resource archive/card featured-image behavior remains unchanged.

## 1.0.46 — WEB-007.4 / 4.6-D/E Contextual Sidebar Integration
- Replaced full-width Related Tools and Related Resources grids on Resource Detail with a compact contextual recommendation sidebar based on the established Similar Tools presentation strategy.
- Preserved all approved Resource → Tool and bidirectional Resource ↔ Resource relationship/query semantics, limits, ordering and A6 reverse-index behavior.
- Keeps the editorial body in the main reading column while ToolNTip owns the contextual sidebar outside `the_content()`.
- Related Tools reuse canonical Tool identity/logo data; Related Resources use canonical Resource identity/type/featured-media data in the same compact visual language.
- Added responsive behavior: desktop reading column + sidebar, tablet two contextual cards below content, mobile single-column stacking.
- Archive/collection Tool Cards and Resource Cards remain unchanged.

## 1.0.45 — WEB-007.4 / 4.6-E Related Resources
- Added read-only Related Resources presentation to single Resource Detail pages.
- Preserves direct canonical Resource relationships first in editorial order, then fills remaining capacity from the A6 scalar reverse index.
- Limits public presentation to three published Resources, excluding self references and duplicates.
- Reuses the canonical Resource Card and `.tnt-resource-grid` presentation without count-aware stretching or Detail-specific card duplication.
- Renders no public empty state and introduces no Resource Detail monetization behavior.

## 1.0.44 — WEB-007.4 / 4.6-D Canonical Tools Archive Grid Reuse
- Replaced the Resource Detail-specific Related Tools grid implementation with the existing Tools Archive grid contract.
- Related Tools now render through `.tnt-tool-directory__grid`, preserving the established three-card desktop formation and its existing responsive breakpoints.
- One or two Related Tools occupy the first one or two canonical grid positions without stretching or introducing Resource-specific card sizing.
- Preserved the canonical Tool Card renderer, relationship retrieval, published-only filtering, editorial order and maximum-three rule.

## 1.0.43 — WEB-007.4 / 4.6-D Related Tools Grid Contract Correction
- Restored the frozen three-column desktop Related Tools grid contract on Resource Detail pages.
- One or two eligible Related Tools now occupy the first one or two slots of the same three-column grid instead of expanding to full or half width.
- Preserved existing tablet two-column and mobile single-column responsive collapse behavior.
- Removed count-aware layout classes introduced in v1.0.42; relationship retrieval, editorial ordering, three-Tool limit, canonical Tool Card, A6 indexes and Resource Detail shell remain unchanged.

## 1.0.42 — WEB-007.4 / 4.6-D Related Tools Presentation Refinement
- Added count-aware Related Tools collection classes on Resource Detail pages.
- One eligible Tool now uses a balanced single-column collection width.
- Two eligible Tools use a balanced two-column collection; three retain the established three-column desktop layout.
- Preserved the canonical Tool Card, relationship retrieval, editorial ordering, three-Tool limit, A6 indexes and Resource Detail shell.
- Existing tablet and mobile breakpoints continue to collapse the collection responsively.

## 1.0.41 — WEB-007.4 / 4.6-D Related Tools
- Added read-only Related Tools presentation to single Resource Detail pages.
- Uses canonical ordered Resource → Tool relationships and exposes published Tool targets only.
- Limits public presentation to the first three eligible Tools while preserving editorial order.
- Reuses the canonical Tool Card component and existing Tool Card assets.
- Renders no public empty state when a Resource has no eligible Related Tools.
- Preserves the A6 reverse relationship index without misusing it for direct Resource → Tool lookup.
- Keeps Related Resources, comments, monetization and Elementor runtime enablement outside this checkpoint.

## 1.0.40 — WEB-007.4 / 4.6-C Editorial Reading Experience
- Refined the single Resource identity hierarchy and public author presentation.
- Added canonical featured-media presentation using the existing WordPress featured image.
- Added Resource Topic and Tag presentation using the frozen taxonomy ownership model.
- Added long-form reading typography for headings, lists, quotes, code, tables, figures and responsive layouts.
- Preserved `the_content()` as the body-rendering boundary for Gutenberg, shortcodes, embeds and optional Elementor authoring compatibility.
- Kept Related Tools, Related Resources, comments and monetization outside this checkpoint.

## 1.0.39 — WEB-007.4 / 4.6-B Resource Detail Foundation
- Added the canonical ToolNTip-owned single Resource template route.
- Added a focused Resource Detail identity data contract without duplicating Resource domain metadata.
- Established the Resource Detail CSS namespace and structural reading shell.
- Established `the_content()` as the canonical editorial-body rendering boundary for native WordPress content and future optional Elementor compatibility.
- Kept featured media, taxonomy presentation, relationships, comments and monetization outside this foundation checkpoint.

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