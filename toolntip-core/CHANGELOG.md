## 1.0.60-dev.19 — ToolNTip Promotions Request De-duplication

- Adds request-scoped de-duplication for first-party Tool promotions in the existing ToolNTip Promotions resolver.
- Excludes Tool IDs already committed by an earlier Tool-promotion placement during the same PHP page request, while preserving each placement's existing eligibility configuration.
- Selects another eligible Tool when available; when distinct Tool inventory is exhausted, the later placement returns empty and preserves the existing zero-footprint behavior.
- Commits a Tool to the request ledger only after the promotion resolves to a valid renderable destination.
- Leaves Custom Code placements independent and unchanged; no Tool-ID de-duplication is applied to custom advertisements.
- Leaves the Application Shell, AD-A/AD-B/AD-C placement definitions, Similar Tools, routing, runtime and SEO/content architecture unchanged.

## 1.0.60-dev.18 — WEB-007.7 Application Similar Tools Integration

- Extends `[tnt_application_support]` with a compact Similar Tools discovery section.
- Reuses the existing `tnt_get_related_tools()` selection, scoring, eligibility and ordering logic; no new recommendation algorithm, relationship storage, taxonomy scoring or query engine is introduced.
- Adds application-specific compact rectangular cards with whole-card navigation, Tool logo, title and primary category metadata.
- Reuses the existing `featured_tool` state to add a small FEATURED ribbon without changing similarity eligibility or ranking semantics.
- Uses a fixed four-column desktop grid, two-column tablet layout and one-column mobile layout; one or two results occupy only their natural grid slots and never stretch to fill the row.
- Keeps Similar Tools separate from Promotions and preserves the frozen Application Shell, runtime, AD-A/AD-B/AD-C, SEO content-ownership model and dev.17 support composition.

## 1.0.60-dev.17 — WEB-007.7 Lean Application Support Integration

- Adds `[tnt_application_support]` as a shell-compatible, Page→Tool-aware supporting-content surface for internal application pages.
- Keeps identity, runtime and AD-A/AD-B/AD-C exclusively owned by `[tnt_application_shell]`; the support layer renders no monetization or duplicate application identity.
- Reuses canonical Tool description, feature and FAQ helpers as concise About This Application, Key Capabilities and application-specific FAQ sections.
- Deliberately does not migrate legacy Pros/Cons, screenshots/video or the legacy internal-after-app promotion wholesale.
- Defers Related Resources/How-To discovery until a governed relationship contract exists; no title/tag guessing or duplicate SEO content is introduced.
- Adds responsive, runtime-independent support presentation and fails closed when Tool context or enabled application context cannot be resolved.
- Preserves dev.16 validation/failure handling, runtime behavior, Promotions, routing and the frozen Application Shell.

## 1.0.60-dev.16 — WEB-007.7 Validation / Failure Handling

- Adds normalized application-context failure codes for missing runtimes, unregistered runtimes, and invalid workspace configuration while keeping visitor output generic.
- Hardens runtime failure logging so debug logs record only controlled Tool/runtime/reason identifiers and never exception messages, stack traces, visitor input, callback details, or filesystem paths.
- Distinguishes renderer exceptions, non-string output, and empty output while failing closed to the same safe unavailable state.
- Adds `aria-live` to controlled unavailable feedback for accessible status announcement.
- Extends ACF save-time workspace validation to reject layouts unsupported by the selected registered runtime when both values are submitted together.
- Preserves the frozen shell, Promotions, routing, runtime asset integration, and JSON Formatter behavior from dev.15.

## 1.0.60-dev.15 — WEB-007.7 Active Operation State Correction

- Replaces the legacy permanent primary-button class with a runtime-owned `is-active` state.
- Styles active Format/Minify/Validate controls from both `is-active` and `aria-pressed="true"` for resilient visual synchronization.
- Keeps Sample and Clear as non-persistent utility actions.
- No shell layout, Promotions, routing, ACF, monetization, or asset architecture changes.

## 1.0.60-dev.13 — WEB-007.7 Active Runtime Operation State

- Corrects the JSON Formatter operation-state model so Format, Minify, and Validate visibly transfer the solid-blue selected state to the last invoked primary operation.
- Keeps Sample and Clear as non-persistent utility actions.
- Adds `aria-pressed` state synchronization for the three primary operations.
- Preserves the approved shell layout, Promotions behavior, asset integration, routing, ACF configuration, and runtime output behavior.

## 1.0.60-dev.12 — WEB-007.7 Runtime Interaction State Polish

- Strengthens JSON Formatter `:focus-visible` styling so keyboard focus is clearly distinguishable from default and hover states.
- Adds explicit hover and pressed interaction feedback for runtime action buttons while preserving the approved primary action treatment.
- Adds matching hover/focus-visible feedback to the indentation selector for consistent keyboard accessibility.
- No layout, shell architecture, Promotions, asset ownership, routing, ACF, runtime behavior, or production cutover changes.

## 1.0.60-dev.11 — WEB-007.7 Runtime Asset Integration

- Enqueues Application Shell CSS only after Core resolves an enabled internal application, avoiding shell asset leakage from dormant/disabled shortcode surfaces.
- Makes the trusted runtime registry asset declaration authoritative for runtime CSS/JS enqueueing.
- Enqueues only already-registered WordPress asset handles; unknown declarations fail closed and cannot introduce arbitrary runtime URLs.
- Removes JSON Formatter-specific enqueue calls from its renderer while preserving its registered, versioned, runtime-scoped assets and approved dev.10 UI.
- No shell presentation, Promotions, routing, ACF, runtime behavior, or production cutover changes.

## 1.0.60-dev.10 — WEB-007.7 Pre-Runtime Tool Promotion Polish

- Added shell-scoped responsive presentation for the AD-B / `internal-hero` first-party Tool Promotion.
- Desktop/tablet now render AD-B as a compact wide campaign card; mobile uses a compact stacked card.
- Preserved Promotions as the content source of truth, Custom Code/reusable ad-unit rendering, zero-footprint behavior, and all frozen AD-A/AD-C rules.
- No routing, ACF, runtime logic, production cutover, or legacy Tool Detail presentation changes.

## 1.0.60-dev.9 — WEB-007.7 Responsive Application Shell Polish

- Refines Desktop Tool Meta proportions, contextual alignment, shell spacing, and runtime density without changing Promotions or application architecture.
- Tightens Tablet identity typography and spacing while preserving AD-A and improving runtime control wrapping.
- Compacts Mobile identity and runtime controls, keeps AD-A structurally omitted, and reduces unnecessary workspace height.
- Preserves all approved AD-A/AD-B/AD-C placement behavior, zero-footprint monetization, runtime actions, and responsive workspace rules.

## 1.0.60-dev.8 — WEB-007.7 Contextual Featured Tool Adaptation

- Adds a dedicated `contextual` Tool-promotion variant for Application Shell AD-A (`internal-contextual`).
- Keeps Promotions authoritative while adapting first-party Featured Tool content to the compact Desktop/Tablet contextual column.
- Prevents narrow-column title collapse and oversized contextual promotion layout without changing Custom Code or reusable ad-unit rendering.
- Preserves AD-A mobile omission, AD-B/AD-C behavior, and zero-footprint handling for disabled or empty placements.

## 1.0.60-dev.7 — WEB-007.7 Application Shell Placement Integration

- Routes Application Shell AD-A through the existing `internal-contextual` named monetization placement.
- Routes AD-B and AD-C through the existing `internal-hero` and `internal-after-app` placements instead of directly consuming reusable ad units.
- Preserves ToolNTip Promotions as the authoritative resolver for disabled placements, first-party Tool promotions, trusted custom campaign code, and reusable ad-unit shortcodes.
- Preserves zero-footprint behavior by emitting shell placement wrappers only when the established placement renderer returns markup.
- Keeps the approved responsive rule that AD-A is omitted on mobile while AD-B and AD-C remain available.

## 1.0.60-dev.6 — WEB-007.7 Application Shell Monetization Integration

- Wires Application Shell AD-A directly to the existing reusable Rectangle ad unit managed in ToolNTip Promotions.
- Wires AD-B and AD-C directly to the existing reusable Leaderboard ad unit, preserving the centralized provider-neutral ad configuration.
- Preserves the approved responsive rule that AD-A is omitted on mobile.
- Emits no application-shell ad wrapper when the mapped reusable ad unit is empty, so empty ads consume zero layout space.
- Keeps the existing placement/promotion APIs intact for legacy Tool and Resource rendering.

## 1.0.60-dev.5 — WEB-007.7 Responsive Application Shell UI/UX

- Implements the approved responsive application-shell visual baseline across desktop, tablet, and mobile.
- Adds AD-A contextual monetization beside the Tool identity/meta region for desktop and tablet only.
- Preserves AD-B pre-runtime and AD-C post-runtime monetization placements across supported breakpoints.
- Refines Tool identity sizing, responsive spacing, runtime card hierarchy, controls, editors, status states, and mobile stacking.
- Keeps AD-A structurally omitted on mobile and preserves the existing production migration boundary.

## 1.0.60-dev.4 - WEB-007.7 Core Shell Rendering

- Added the controlled `[tnt_application_shell]` integration surface for explicit non-production shell rendering.
- Added scoped Core application-shell presentation and responsive identity/runtime/monetization regions.
- Wired shell assets only when the Core shell renderer is invoked.
- Preserved the canonical Tool route and existing Elementor JSON Formatter production path; no automatic template cutover is performed.
- Supports explicit `post_id` or `tool_slug` context for controlled integration testing.

## 1.0.60-dev.3 - WEB-007.7 JSON Formatter Runtime

- Added the trusted `json_formatter` internal application runtime registration.
- Added client-side Format, Minify, Validate, Sample and Clear actions with controlled indentation.
- Added split/stacked input-output workspace, validation feedback, lightweight statistics, Copy and Download result actions.
- Added scoped JSON Formatter runtime CSS and JavaScript assets, enqueued only when the runtime renderer is invoked.
- Preserved the existing Elementor JSON Formatter production path; this build registers the new runtime but does not cut production rendering over to the Core shell.

## 1.0.60-dev.2 - WEB-007.7 ACF Application Configuration

- Added the minimal Internal Application Configuration ACF field group for Tool posts.
- Added controlled Runtime Module choices sourced from the trusted Core runtime registry.
- Added optional Workspace Layout override using the frozen Core layout vocabulary.
- Added save-time validation for runtime IDs and workspace layout values.
- Updated plugin identity to ToolNTip Technologies and https://toolntip.com/.
- Preserved existing Tool Details fields, routing, Elementor application pages, and dormant runtime behavior.

## 1.0.60-dev.1 — WEB-007.7 / 7.6-B.3 Application Shell Orchestration Foundation

- Added the Core-owned internal application shell orchestrator.
- Added compact application identity composition using existing Tool shell data.
- Added trusted runtime execution through the registered runtime renderer contract.
- Added controlled unavailable fallback without exposing runtime diagnostics to visitors.
- Reused existing internal monetization placements before and after the runtime boundary.
- Preserved existing Tool, Elementor, Resource, Labs, routing and supporting-content behavior; the new shell remains dormant until explicitly invoked for an enabled internal application.
- Began WEB-007.7 development versioning at `1.0.60-dev.1`.

## 1.0.59 — WEB-007.4 / 4.7-E LINK-01 Resource Topic Internal-Link Correction

- Corrected Resource-owned `tool_category` term links to use the canonical Resource Topic route under `/resources/topic/{slug}/`.
- Applied the same Resource-context URL contract to Resource Detail and Resource Card taxonomy normalization.
- Preserved native `/tool-category/{slug}/` links for Tool-context taxonomy archives; no global term-link filter, taxonomy rewrite, template, CSS, or query-engine change was introduced.

## 1.0.58 — WEB-007.4 / 4.7-D Resource Topic Sitemap Integration

- Added a narrow AIOSEO Additional Pages integration for canonical Resource Topic URLs under `/resources/topic/{slug}/`.
- Reuses the frozen Resource Topic URL helper and includes only Tool Category terms attached to published Resources.
- Preserves native shared `/tool-category/{slug}/` taxonomy URLs for Tool-context SEO; no taxonomy rewrites or global term-link filters were introduced.
- Adds bounded sitemap metadata with priority `0.5`, monthly change frequency, and the latest associated published Resource modification time.
- Deduplicates Resource Topic entries against existing AIOSEO Additional Pages and fails open when AIOSEO is absent or no published Resource Topics exist.
- Completes Resource Tag archive query-envelope alignment so Tag pagination follows the same 12-item published Resource contract as Type and Topic archives.

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
## 1.0.60-dev.15
- WEB-007.7 asset-cache correction: moved JSON Formatter runtime CSS/JS to new versioned asset paths so caches that ignore query-string versions cannot serve stale interaction logic.
- Preserves runtime-owned active operation state for Format, Minify, and Validate.
