# Toolntip Core

**Version:** 1.1.0
**Status:** Early Development
**Repository:** Private

## Overview

Toolntip Core is the primary WordPress plugin that powers the Toolntip platform. It provides the application's core functionality, including custom post types, Advanced Custom Fields (ACF) integration, reusable helper functions, a template rendering engine, shortcodes, and frontend assets.

The plugin is designed with a modular architecture so new features can be added without affecting existing functionality.

## Current Features

* Plugin bootstrap and initialization
* Asset management (CSS & JavaScript)
* Custom helper functions
* Tool data layer
* Template rendering engine
* Dynamic Tool shortcode
* Reusable Tool Card template
* Advanced Custom Fields (ACF) integration

## Planned Features

### Tool Management

* Featured Tools
* Related Tools
* Tool Categories
* Tool Collections
* Tool Comparison

### Frontend Components

* Hero Sections
* Tool Cards
* Information Grid
* Badges
* CTA Buttons
* Responsive Layout

### Search & Filtering

* Live Search
* Category Filters
* Platform Filters
* Pricing Filters
* AJAX Search

### SEO & Performance

* Structured Data (JSON-LD)
* Automatic Meta Information
* Performance Optimizations
* Caching
* Sitemap Integration

### AI Features

* AI-generated Tool Summaries
* AI Recommendations
* Smart Related Tools

## Folder Structure

```text
toolntip-core/
│
├── assets/
│   ├── css/
│   └── js/
│
├── includes/
│
├── shortcodes/
│
├── templates/
│
├── toolntip-core.php
├── README.md
└── .gitignore
```

## Requirements

* WordPress 6.8+
* PHP 8.1+
* Advanced Custom Fields (ACF) Pro
* Elementor (recommended)

## Installation

1. Install the plugin in the WordPress plugins directory.
2. Activate the plugin.
3. Install and activate Advanced Custom Fields Pro.
4. Import the Tool field groups.
5. Create Tool posts.
6. Use the provided shortcodes to display Tools.

## Example Shortcode

```text
[tnt_tool slug="json-formatter"]
```

## Development Principles

* Modular architecture
* Reusable helper functions
* Separation of logic and presentation
* Clean, maintainable code
* WordPress coding standards

## Version History

### v0.1.0

* Initial plugin architecture
* Asset loader
* Helper functions
* Tool data layer
* Template renderer
* Dynamic Tool shortcode
* Tool Card template

## Author

**Syed Asim Raza**

Website: https://toolntip.com

---

© 2026 Toolntip. All rights reserved.
