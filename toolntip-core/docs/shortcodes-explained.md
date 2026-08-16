# ToolNTip Core Shortcode Reference

**Package reviewed:**
`toolntip-core(20260816-multi-parameter-shortcodes).zip`\
**Purpose:** Runtime shortcode reference for the ToolNTip Core package.

This document lists the shortcodes that are actually loaded by
`shortcodes/load.php` in the reviewed package. Examples use
representative Tool slugs such as `base64-encoder-decoder`; replace them
with real Tool slugs from the site.

> **Context resolution:** Most Tool Shell, Application Page, and Tool
> Detail component shortcodes accept `post_id` and `tool_slug`. When
> neither is supplied, ToolNTip attempts to resolve the current
> Tool/Application Page context. Explicit values are useful in Elementor
> or test pages.

------------------------------------------------------------------------

## 1. Legacy Full Tool Shortcode

### `[tnt_tool]`

Renders a complete Tool Detail view or a Tool Card using the established
Tool data/template system.

**Attributes**

  Attribute    Default    Description
  ------------ ---------- ---------------------
  `slug`       empty      Required Tool slug.
  `template`   `detail`   `detail` or `card`.

**Examples**

``` text
[tnt_tool slug="base64-encoder-decoder"]
```

``` text
[tnt_tool slug="base64-encoder-decoder" template="card"]
```

If `slug` is omitted, the shortcode returns `No tool slug provided.` If
the Tool cannot be resolved, it returns `Tool not found.`

------------------------------------------------------------------------

## 2. Multi-Parameter Collection Shortcodes

### `[tnt_tool_archive]`

Renders the interactive Tool archive with optional search, filters,
sorting, pagination, and configurable grid columns.

**Attributes**

  Attribute            Default Accepted / purpose
  ------------------ --------- ----------------------------------
  `posts_per_page`        `16` Integer, normalized to 1--100.
  `columns`                `4` `2`, `3`, `4`, or `6`.
  `show_search`         `true` Boolean.
  `show_filters`        `true` Boolean.
  `show_sorting`        `true` Boolean.
  `layout`              `grid` `grid` or `compact`.
  `tool_search`          empty Initial search fallback.
  `category`             empty Initial category fallback.
  `type`                 empty Initial Tool type fallback.
  `pricing`              empty Initial pricing fallback.
  `featured`             empty Initial featured-state fallback.
  `sort`                 empty Initial sorting fallback.

For the filter-state attributes, matching URL GET parameters take
precedence over shortcode fallbacks. Supported URL state includes
`tool_search`, `category`, `pricing`, `type`, `featured`, and `sort`.

**Examples**

``` text
[tnt_tool_archive]
```

``` text
[tnt_tool_archive posts_per_page="16" columns="3" show_search="true" show_filters="true" show_sorting="true" layout="grid"]
```

``` text
[tnt_tool_archive columns="3" category="developer-tools" pricing="free"]
```

Example URL-driven filtering:

``` text
/tools/?tool_search=json&category=developer-tools&pricing=free&type=internal&featured=1&sort=rating
```

### `[tnt_tools]`

Renders a curated Tool grid without the live archive search/filter
controls.

**Attributes**

  --------------------------------------------------------------------------
  Attribute                                  Default Accepted / purpose
  --------------------- ---------------------------- -----------------------
  `category`                                   empty Taxonomy slug or
                                                     comma/space-separated
                                                     slugs.

  `type`                                       empty Tool type, including
                                                     multiple
                                                     comma/space-separated
                                                     values.

  `pricing`                                    empty Pricing value,
                                                     including multiple
                                                     comma/space-separated
                                                     values.

  `featured`                                   empty `true` / `false` style
                                                     value.

  `tag`                                        empty Tag slug or supported
                                                     multi-value input.

  `limit`                                        `8` Number of Tools to
                                                     return.

  `columns`                                      `4` `2`, `3`, or `4`.

  `orderby`                                   `date` Supported query order
                                                     such as `date`,
                                                     `title`, `rating`,
                                                     `rand`.

  `order`                                     `DESC` `ASC` or `DESC`.
  --------------------------------------------------------------------------

**Examples**

``` text
[tnt_tools category="developer-tools" featured="true" limit="4" columns="4" orderby="date" order="DESC"]
```

``` text
[tnt_tools pricing="free" limit="9" columns="3" orderby="rating" order="DESC"]
```

``` text
[tnt_tools tag="formatter" limit="8" columns="4" orderby="title" order="ASC"]
```

Multiple values:

``` text
[tnt_tools category="developer-tools,ai-tools" pricing="free freemium" type="internal,external" limit="12" columns="3"]
```

------------------------------------------------------------------------

## 3. Tool Shell / Granular Data Shortcodes

Except where noted, these shortcodes accept the common context
attributes:

  Attribute       Default Description
  ------------- --------- ------------------------
  `post_id`           `0` Explicit Tool post ID.
  `tool_slug`       empty Explicit Tool slug.

A basic context example:

``` text
[tnt_tool_title tool_slug="base64-encoder-decoder"]
```

### Identity and summary

  -----------------------------------------------------------------------------------------------------------
  Shortcode                 Output                  Example
  ------------------------- ----------------------- ---------------------------------------------------------
  `[tnt_tool_title]`        Tool title              `[tnt_tool_title tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_tagline]`      Tool tagline            `[tnt_tool_tagline tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_hero_image]`   Current Application     `[tnt_tool_hero_image]`
                            Page hero/featured
                            image

  `[tnt_tool_excerpt]`      Tool excerpt            `[tnt_tool_excerpt tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_icon]`         Tool identity icon/logo `[tnt_tool_icon tool_slug="base64-encoder-decoder"]`
  -----------------------------------------------------------------------------------------------------------

`[tnt_tool_hero_image]` is context-driven in the current implementation
and does not parse `post_id` / `tool_slug` attributes.

### Rating and verification

  ------------------------------------------------------------------------------------------------------------------
  Shortcode                   Output                  Example
  --------------------------- ----------------------- --------------------------------------------------------------
  `[tnt_tool_rating]`         Numeric rating          `[tnt_tool_rating tool_slug="base64-encoder-decoder"]`
                              formatted to one
                              decimal

  `[tnt_tool_review_count]`   Community review count  `[tnt_tool_review_count tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_verified]`       Verified-state markup   `[tnt_tool_verified tool_slug="base64-encoder-decoder"]`
                              when applicable
  ------------------------------------------------------------------------------------------------------------------

### Classification and metadata

  ------------------------------------------------------------------------------------------------------------
  Shortcode                Output                  Example
  ------------------------ ----------------------- -----------------------------------------------------------
  `[tnt_tool_category]`    Tool category names     `[tnt_tool_category tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_pricing]`     Pricing value           `[tnt_tool_pricing tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_platform]`    Platform list           `[tnt_tool_platform tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_developer]`   Developer               `[tnt_tool_developer tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_type]`        Tool type               `[tnt_tool_type tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_tags]`        Tool tag names          `[tnt_tool_tags tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_meta]`        Compact combined Tool   `[tnt_tool_meta tool_slug="base64-encoder-decoder"]`
                           metadata
  ------------------------------------------------------------------------------------------------------------

### URLs

  ------------------------------------------------------------------------------------------------------------
  Shortcode                Output                  Example
  ------------------------ ----------------------- -----------------------------------------------------------
  `[tnt_tool_url]`         Tool's configured Use   `[tnt_tool_url tool_slug="base64-encoder-decoder"]`
                           Tool/action URL

  `[tnt_tool_permalink]`   Native Tool CPT         `[tnt_tool_permalink tool_slug="base64-encoder-decoder"]`
                           permalink
  ------------------------------------------------------------------------------------------------------------

### Content

  ----------------------------------------------------------------------------------------------------------------
  Shortcode                  Output                  Example
  -------------------------- ----------------------- -------------------------------------------------------------
  `[tnt_tool_features]`      Feature list            `[tnt_tool_features tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_pros]`          Pros list               `[tnt_tool_pros tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_cons]`          Cons list               `[tnt_tool_cons tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_description]`   Native Tool post        `[tnt_tool_description tool_slug="base64-encoder-decoder"]`
                             content/description

  `[tnt_tool_screenshots]`   Tool screenshots        `[tnt_tool_screenshots tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_video]`         Tool video              `[tnt_tool_video tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_faq]`           Tool FAQ                `[tnt_tool_faq tool_slug="base64-encoder-decoder"]`
  ----------------------------------------------------------------------------------------------------------------

The granular Tool Shell shortcodes return empty output when the
requested optional data/context is unavailable, except for the separate
legacy `[tnt_tool]` behavior documented earlier.

------------------------------------------------------------------------

## 4. Application Page Composition Shortcodes

These are thin adapters over the existing ToolNTip application-page
renderers. They accept the same `post_id` / `tool_slug` context
attributes used by the Tool Shell resolver.

### `[tnt_internal_tool_top]`

Renders the top composition for an Internal Tool application page.

``` text
[tnt_internal_tool_top]
```

Explicit Tool:

``` text
[tnt_internal_tool_top tool_slug="base64-encoder-decoder"]
```

### `[tnt_internal_tool_bottom]`

Renders the bottom composition for an Internal Tool application page.

``` text
[tnt_internal_tool_bottom]
```

``` text
[tnt_internal_tool_bottom tool_slug="base64-encoder-decoder"]
```

### `[tnt_external_tool_page]`

Renders the External Tool page composition.

``` text
[tnt_external_tool_page tool_slug="example-external-tool"]
```

------------------------------------------------------------------------

## 5. Tool Detail Component Shortcodes

These shortcodes render the existing finalized Tool Detail components.
They use the Tool Shell resolver, so `post_id` and `tool_slug` can be
supplied when explicit context is needed.

### Canonical `tnt_detail_*` shortcodes

  ------------------------------------------------------------------------------------------------------------------------
  Shortcode                      Component               Example
  ------------------------------ ----------------------- -----------------------------------------------------------------
  `[tnt_detail_hero]`            Tool Detail hero        `[tnt_detail_hero tool_slug="base64-encoder-decoder"]`
                                 identity

  `[tnt_detail_nav]`             Tool Detail navigation  `[tnt_detail_nav tool_slug="base64-encoder-decoder"]`

  `[tnt_detail_about]`           About section           `[tnt_detail_about tool_slug="base64-encoder-decoder"]`

  `[tnt_detail_features]`        Key Features component  `[tnt_detail_features tool_slug="base64-encoder-decoder"]`

  `[tnt_detail_screenshots]`     Canonical screenshots   `[tnt_detail_screenshots tool_slug="base64-encoder-decoder"]`
                                 component

  `[tnt_detail_video]`           Canonical Video         `[tnt_detail_video tool_slug="base64-encoder-decoder"]`
                                 Overview component

  `[tnt_detail_pros_cons]`       Pros & Cons component   `[tnt_detail_pros_cons tool_slug="base64-encoder-decoder"]`

  `[tnt_detail_faq]`             FAQ component           `[tnt_detail_faq tool_slug="base64-encoder-decoder"]`

  `[tnt_detail_reviews]`         Reviews component       `[tnt_detail_reviews tool_slug="base64-encoder-decoder"]`

  `[tnt_detail_information]`     Tool Information        `[tnt_detail_information tool_slug="base64-encoder-decoder"]`
                                 component

  `[tnt_detail_similar_tools]`   Similar Tools component `[tnt_detail_similar_tools tool_slug="base64-encoder-decoder"]`

  `[tnt_detail_schema]`          Schema component/output `[tnt_detail_schema tool_slug="base64-encoder-decoder"]`
  ------------------------------------------------------------------------------------------------------------------------

### Tool Detail aliases

The package also registers these aliases where they do not conflict with
an existing shortcode:

  --------------------------------------------------------------------------------------------------------------------
  Alias                        Equivalent component    Example
  ---------------------------- ----------------------- ---------------------------------------------------------------
  `[tnt_tool_detail_hero]`     Tool Detail hero        `[tnt_tool_detail_hero tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_detail_nav]`      Tool Detail navigation  `[tnt_tool_detail_nav tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_about]`           About                   `[tnt_tool_about tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_pros_cons]`       Pros & Cons             `[tnt_tool_pros_cons tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_reviews]`         Reviews                 `[tnt_tool_reviews tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_information]`     Tool Information        `[tnt_tool_information tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_similar_tools]`   Similar Tools           `[tnt_tool_similar_tools tool_slug="base64-encoder-decoder"]`

  `[tnt_tool_schema]`          Schema                  `[tnt_tool_schema tool_slug="base64-encoder-decoder"]`
  --------------------------------------------------------------------------------------------------------------------

### `[tnt_detail_after_hero_monetization]`

Renders the existing Tool Detail `external-after-hero` monetization
placement for the resolved Tool.

``` text
[tnt_detail_after_hero_monetization tool_slug="example-external-tool"]
```

If the Tool cannot be resolved, monetization support is unavailable, or
the placement resolves to empty output, nothing is rendered.

------------------------------------------------------------------------

## 6. Manual Monetization Shortcodes

These shortcodes render administrator-managed reusable ad units. They do
**not** accept arbitrary HTML attributes; the content comes from
ToolNTip's centralized monetization settings.

  ------------------------------------------------------------------------
  Shortcode                Ad unit                 Typical size / role
  ------------------------ ----------------------- -----------------------
  `[tnt_ad_leaderboard]`   `leaderboard`           Wide leaderboard,
                                                   e.g.Â 970Ã—90

  `[tnt_ad_rectangle]`     `rectangle`             Medium rectangle,
                                                   e.g.Â 300Ã—250

  `[tnt_ad_horizontal]`    `horizontal`            Horizontal unit,
                                                   e.g.Â 728Ã—90

  `[tnt_ad_sidebar]`       `sidebar`               Tall sidebar unit,
                                                   e.g.Â 300Ã—600

  `[tnt_ad_mobile]`        `mobile`                Mobile unit,
                                                   e.g.Â 320Ã—100
  ------------------------------------------------------------------------

**Examples**

``` text
[tnt_ad_leaderboard]
```

``` text
[tnt_ad_rectangle]
```

``` text
[tnt_ad_horizontal]
```

``` text
[tnt_ad_sidebar]
```

``` text
[tnt_ad_mobile]
```

These are intended for manual placement, including Elementor pages.
Automatic Tool Directory in-grid monetization is handled separately by
the archive monetization policy and should not be reproduced manually
inside the archive loop.

------------------------------------------------------------------------

## 7. Practical Composition Examples

### Internal Tool application page

``` text
[tnt_internal_tool_top]

<!-- The actual browser-based application can sit here. -->

[tnt_internal_tool_bottom]
```

### Custom Tool landing page using granular fields

``` text
[tnt_tool_icon tool_slug="base64-encoder-decoder"]
[tnt_tool_title tool_slug="base64-encoder-decoder"]
[tnt_tool_tagline tool_slug="base64-encoder-decoder"]
[tnt_tool_meta tool_slug="base64-encoder-decoder"]
[tnt_tool_description tool_slug="base64-encoder-decoder"]
[tnt_tool_features tool_slug="base64-encoder-decoder"]
[tnt_tool_pros tool_slug="base64-encoder-decoder"]
[tnt_tool_cons tool_slug="base64-encoder-decoder"]
[tnt_tool_faq tool_slug="base64-encoder-decoder"]
```

### Tool Detail assembled from canonical components

``` text
[tnt_detail_hero tool_slug="base64-encoder-decoder"]
[tnt_detail_nav tool_slug="base64-encoder-decoder"]
[tnt_detail_about tool_slug="base64-encoder-decoder"]
[tnt_detail_features tool_slug="base64-encoder-decoder"]
[tnt_detail_screenshots tool_slug="base64-encoder-decoder"]
[tnt_detail_video tool_slug="base64-encoder-decoder"]
[tnt_detail_pros_cons tool_slug="base64-encoder-decoder"]
[tnt_detail_faq tool_slug="base64-encoder-decoder"]
[tnt_detail_reviews tool_slug="base64-encoder-decoder"]
[tnt_detail_information tool_slug="base64-encoder-decoder"]
[tnt_detail_similar_tools tool_slug="base64-encoder-decoder"]
[tnt_detail_schema tool_slug="base64-encoder-decoder"]
```

### Curated homepage section

``` text
[tnt_tools featured="true" limit="6" columns="3" orderby="rating" order="DESC"]
```

### Three-column interactive archive

``` text
[tnt_tool_archive posts_per_page="12" columns="3" show_search="true" show_filters="true" show_sorting="true"]
```

------------------------------------------------------------------------

## 8. Runtime Shortcode Inventory

The reviewed package's active shortcode loader includes:

``` text
shortcodes/tool.php
shortcodes/tool-shell.php
shortcodes/tool-application-pages.php
shortcodes/tool-detail-components.php
shortcodes/monetization.php
shortcodes/tool-collections.php
```

This produces the following active shortcode families:

-   1 legacy Tool shortcode
-   2 collection/archive shortcodes
-   24 granular Tool Shell shortcodes
-   3 Application Page composition shortcodes
-   12 canonical Tool Detail component shortcodes
-   8 Tool Detail aliases
-   1 Tool Detail monetization placement shortcode
-   5 reusable manual monetization shortcodes

**Total documented active shortcode tags: 56.**

------------------------------------------------------------------------

## 9. Package Note: Dormant Test Shortcode

The package contains `shortcodes/hello.php`, which defines:

``` text
[tnt_hello]
```

However, `shortcodes/load.php` does **not** require `hello.php`.
Therefore `[tnt_hello]` is present in the source package but is **not an
active runtime shortcode in the reviewed build**. It is intentionally
not counted among the 56 active shortcode tags above.

If that file is explicitly loaded in a future build, usage would be:

``` text
[tnt_hello]
```

and it returns the package's simple "Hello from Toolntip Core" test
block.

------------------------------------------------------------------------

## 10. Quick Reference

``` text
LEGACY
[tnt_tool]

COLLECTIONS
[tnt_tool_archive]
[tnt_tools]

TOOL SHELL
[tnt_tool_title]
[tnt_tool_tagline]
[tnt_tool_hero_image]
[tnt_tool_excerpt]
[tnt_tool_rating]
[tnt_tool_review_count]
[tnt_tool_verified]
[tnt_tool_category]
[tnt_tool_pricing]
[tnt_tool_platform]
[tnt_tool_developer]
[tnt_tool_type]
[tnt_tool_tags]
[tnt_tool_url]
[tnt_tool_permalink]
[tnt_tool_meta]
[tnt_tool_features]
[tnt_tool_pros]
[tnt_tool_cons]
[tnt_tool_description]
[tnt_tool_screenshots]
[tnt_tool_video]
[tnt_tool_faq]
[tnt_tool_icon]

APPLICATION PAGES
[tnt_internal_tool_top]
[tnt_internal_tool_bottom]
[tnt_external_tool_page]

TOOL DETAIL
[tnt_detail_hero]
[tnt_detail_nav]
[tnt_detail_about]
[tnt_detail_features]
[tnt_detail_screenshots]
[tnt_detail_video]
[tnt_detail_pros_cons]
[tnt_detail_faq]
[tnt_detail_reviews]
[tnt_detail_information]
[tnt_detail_similar_tools]
[tnt_detail_schema]
[tnt_detail_after_hero_monetization]

TOOL DETAIL ALIASES
[tnt_tool_detail_hero]
[tnt_tool_detail_nav]
[tnt_tool_about]
[tnt_tool_pros_cons]
[tnt_tool_reviews]
[tnt_tool_information]
[tnt_tool_similar_tools]
[tnt_tool_schema]

MONETIZATION
[tnt_ad_leaderboard]
[tnt_ad_rectangle]
[tnt_ad_horizontal]
[tnt_ad_sidebar]
[tnt_ad_mobile]
```
