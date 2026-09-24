# Settings CSS custom properties

The full `--pp-cs-*` → `--ppcart-*` token register for Pro settings CSS. Part of [Pro prefix compliance](../prefix-pro.md).

---

Free settings UI tokens live on `.ppcart-settings-page` in
`admin/css/ppcart-settings.css`. Hard cutover from abbreviated `--pp-cs-*` to
`--ppcart-*`. No Compatibility Mode aliases. Pro settings CSS that overrides or
extends the Free settings screen must use the canonical names.

| Leftover (no-op in Pro) | Canonical (Pro must match Free) |
|-------------------------|----------------------------------|
| `--pp-cs-bg` | `--ppcart-bg` |
| `--pp-cs-surface` | `--ppcart-surface` |
| `--pp-cs-surface-alt` | `--ppcart-surface-alt` |
| `--pp-cs-border` | `--ppcart-border` |
| `--pp-cs-border-strong` | `--ppcart-border-strong` |
| `--pp-cs-text` | `--ppcart-text` |
| `--pp-cs-text-muted` | `--ppcart-text-muted` |
| `--pp-cs-text-soft` | `--ppcart-text-soft` |
| `--pp-cs-primary` | `--ppcart-primary` |
| `--pp-cs-primary-hover` | `--ppcart-primary-hover` |
| `--pp-cs-primary-soft` | `--ppcart-primary-soft` |
| `--pp-cs-section-header` | `--ppcart-section-header` |
| `--pp-cs-section-header-border` | `--ppcart-section-header-border` |
| `--pp-cs-sidebar-primary` | `--ppcart-sidebar-primary` |
| `--pp-cs-sidebar-bg` | `--ppcart-sidebar-bg` |
| `--pp-cs-sidebar-hover` | `--ppcart-sidebar-hover` |
| `--pp-cs-sidebar-border` | `--ppcart-sidebar-border` |
| `--pp-cs-accent` | `--ppcart-accent` |
| `--pp-cs-success` | `--ppcart-success` |
| `--pp-cs-success-soft` | `--ppcart-success-soft` |
| `--pp-cs-warning` | `--ppcart-warning` |
| `--pp-cs-warning-soft` | `--ppcart-warning-soft` |
| `--pp-cs-danger` | `--ppcart-danger` |
| `--pp-cs-radius-sm` | `--ppcart-radius-sm` |
| `--pp-cs-radius` | `--ppcart-radius` |
| `--pp-cs-radius-lg` | `--ppcart-radius-lg` |
| `--pp-cs-shadow-sm` | `--ppcart-shadow-sm` |
| `--pp-cs-shadow` | `--ppcart-shadow` |
| `--pp-cs-sidebar-width` | `--ppcart-sidebar-width` |
| `--pp-cs-header-height` | `--ppcart-header-height` |
| `--pp-cs-admin-bar-height` | `--ppcart-admin-bar-height` |
| `--pp-cs-savebar-height` | `--ppcart-savebar-height` |
| `--pp-cs-content-pad` | `--ppcart-content-pad` |
