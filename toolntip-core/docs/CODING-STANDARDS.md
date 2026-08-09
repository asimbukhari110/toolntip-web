## Repeatable Data Convention

Repeatable collections should return arrays of associative arrays rather than primitive values.

Preferred:

```php
[
    [
        'title' => 'AI Powered',
    ],
]
```

Avoid:

```php
[
    'AI Powered',
]
```

### Benefits

- Easier extensibility
- Consistent template structure
- Backward-compatible evolution
- Predictable helper APIs

## Component Naming

Presentation components should use kebab-case filenames that match the product terminology.

Examples:

- hero.php
- screenshots.php
- about.php
- features.php
- pros-cons.php
- video.php
- faq.php
- similar-tools.php

This keeps templates easy to locate and aligned with the UI.

##Coding Standards

No function should exceed a reasonable size (around 100–150 lines). Split responsibilities into dedicated providers.
