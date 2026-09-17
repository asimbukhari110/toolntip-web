# ToolNTip Application Package Manifest — Schema 1

This contract defines declarative client-side application packages consumed by ToolNTip Core.
It intentionally excludes PHP and arbitrary server-side executable code.

## Required package manifest

Each package must contain a root `manifest.json` following schema `1`.

```json
{
  "schema": 1,
  "id": "vlan_designer",
  "name": "VLAN Designer",
  "version": "1.0.0",
  "runtime": {
    "entry": "runtime.html",
    "supported_layouts": ["single"],
    "default_layout": "single"
  },
  "assets": {
    "styles": ["assets/runtime.css"],
    "scripts": ["assets/runtime.js"]
  },
  "page": {
    "title": "VLAN Designer Online",
    "slug": "vlan-designer-online"
  },
  "capabilities": ["client-side", "clipboard", "download", "print"]
}
```

## Frozen rules

- `schema` must equal the Core-supported schema version.
- `id` is immutable application identity and must contain only lowercase letters, numbers, `_` or `-` separators.
- `version` uses semantic versioning.
- `runtime.entry` must be a relative `.html` file.
- Workspace layouts must come from the existing ToolNTip Application Shell contract.
- CSS/JS asset paths must be relative and may not traverse outside the package.
- Runtime Page title and slug are mandatory. First installation will later use these to provision one Draft WordPress Page.
- Page slug collisions will fail closed; Core must not silently create `-2` variants.
- Client-side package files are allowlisted. PHP and other executable server-side payloads are prohibited.
- This manifest layer validates configuration only. ZIP extraction, file existence checks, transactional installation, activation, rollback and housekeeping belong to subsequent package-platform stages.
