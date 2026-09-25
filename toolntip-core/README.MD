## 1.2.0-dev.11.8 - I-17 Single-Edition Metadata-First Composition

- Moves Edition status and pricing labels into a compact metadata row above the Edition title.
- Presents pricing as a neutral pill while retaining provider/status styling for lifecycle state.
- Keeps the Edition title as the primary identity with description directly beneath it.
- Preserves multi-Edition grids and responsive stacking; no schema, release, download, or provider changes.

## 1.2.0-dev.11.5

- Render governed Product capability icons on public Product Detail cards using Core-owned inline SVGs.
- Preserve icon selection from Product administration without depending on theme icon fonts or third-party libraries.
- No schema, Product data, Release, download, provider, or routing changes.

## 1.2.0-dev.11.4 - I-17 Product Hero Vertical Rhythm

- Increased the Product Detail hero spacing between category, Product name, development status and positioning copy to restore a clear identity hierarchy with short public Product names such as ToolNTip IGA.
- Kept Product archive cards, hero geometry, Product data/rendering contracts, release/download behavior and Product DB schema unchanged.

## 1.2.0-dev.11.3 - I-17 Cross-Surface Visual Convergence

- Reused the canonical Resource Card renderer inside Product related Resources so Product and Resource surfaces no longer maintain visually divergent supporting-content cards.
- Refined Product archive cards and Product Detail containers to follow the established Tool Card / Tool Detail geometry, spacing, borders, radii, surfaces and action hierarchy.
- Expanded Resource Detail headlines to use the available identity width instead of the previous 28-character measure cap.
- Refined the Tool Detail hero to a balanced two-column identity/preview composition with the promotional placement spanning the full hero width below, reducing desktop crowding.
- Preserved Product routes, download authorization, provider abstraction, release lifecycle and Product DB schema version 1.

## 1.2.0-dev.11.2 - I-17 Product Visual Language Alignment

- Aligned Product archive, Product Detail, capability, edition, release and related-resource presentation with the established ToolNTip Tool/Resource visual language.
- Replaced theme-palette-dependent card surfaces with the established white surface, navy text, neutral border, 14px radius and subtle navy shadow vocabulary.
- Aligned primary Product actions to ToolNTip navy and secondary/copy actions to the established subtle-surface treatment.
- Aligned status badges, fact panels, focus-visible treatment, responsive spacing and Resource-style supporting cards without changing Product data, routes, download authorization or provider behavior.
- Product DB schema remains version 1.

## 1.2.0-dev.11.1 - I-17 Guest Checksum Copy Compatibility

- Fixed public Product checksum Copy so a rejected Clipboard API request falls back to the legacy copy path instead of silently doing nothing.
- Copy remains informational and available to guests; download authorization remains unchanged.
- No schema, provider, release, routing, or download-policy changes.


## 1.2.0-dev.11 - WEB-007.9 I-17 Public Product Rendering

- Added Core-owned native `/products/` archive and `/products/{slug}/` Product detail templates.
- Added centralized normalized Product public view model and canonical Product card renderer.
- Added reusable `[tnt_products]` shortcode using the same card renderer as the native archive.
- Added conditional Product sections for overview, capabilities, platform/deployment, editions, published releases/downloads and related Resources.
- Added login-aware governed Release download actions, provider-agnostic public CTAs and SHA-256 copy interaction.
- Product public CSS/JS load only on native Product archive/single requests.
- Product DB schema remains version 1.
# toolntip-core



## WEB-007.9 development checkpoint
Current development package: `1.2.0-dev.11.4` (I-17 Public Product Rendering / Cross-Surface Visual Convergence).


## WEB-007.9 development note — 1.2.0-dev.6

Adds the governed Product download delivery service: authenticated ToolNTip download routes, exact/latest Published Release resolution, priority/weight provider selection, health-aware failover, mandatory redirect-event logging, and provider redirects without proxying artifact bytes through WordPress.


### 1.2.0-dev.11.7

I-17 refinement: balanced single-edition public presentation with the Edition name and status grouped as the primary identity, followed by pricing and descriptive copy. Multi-edition behavior is unchanged.

### 1.2.0-dev.11.6

- Refined single-Edition public Product presentation with a responsive horizontal layout; multi-Edition behavior and data contracts are unchanged.
