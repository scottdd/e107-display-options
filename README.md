# e107 Display Options (`e107_options`) v1.0.0

A unified, zero-FOUC **Display & Reader Options** plugin for **e107 v2.3+** (compatible with **PHP 7.4 through PHP 8.3+** and **Bootstrap 3, 4, and 5** themes).

Provides visitors with an accessible, persistent control panel — available as both a **Navbar Popover** (`{E107_OPTIONS_NAV}`) and a **Sidebar Menu** (`e107_options_menu`) — for customizing their reading and browsing experience without page-load flash (FOUC).

> *Imagined by human, built with AI.*

---

## Key Features

- **5 Integrated Display & Reader Selectors** (individually orderable, collapsible, or restrictable by e107 userclass):
  1. **Color Mode (`light` / `dark` / `auto`)** — Supports native Bootstrap 5 / `data-bs-theme` dark mode AND includes a **Universal Dark/Light Mode CSS Engine** that brings dark/light switching to classic Bootstrap 3/4 and legacy v2 themes.
  2. **Frontend Theme Switcher** — Automatically discovers installed e107 v2 frontend themes (or restricts to an admin-curated list) so visitors can preview or switch themes on the fly without altering the site default for other visitors.
  3. **Theme Color Variant / Palette Switcher** — Automatically detects sub-palettes/stylesheets inside the active theme (such as `i3theme` color schemes or custom theme variants).
  4. **Language Switcher** — Integrates with e107 multi-language support when multiple languages are installed.
  5. **Reader Typography & Layout Width Controls** — Adjustable text size (`90%` – `125%`) and comfortable reading container width.
- **Zero-FOUC Early Head Injection (`e_header.php`)** — Applies saved visitor preferences (`localStorage` + cookie sync) in `<head>` before first paint.
- **Automatic Capability Detection & Force-Blocking** — Automatically hides selectors that don't apply to the current site/theme (e.g., single-language site or theme without variants), with admin overrides.

---

## Screenshots

### Frontend Navbar Popover & Sidebar Widget (`moderndark` / Bootstrap 5)
![Frontend Popover and Sidebar](docs/screenshots/e107-options-moderndark-popover-and-sidebar.png)

### Theme Variant Auto-Detection (`i3theme` Sub-Palettes)
![Theme Variants](docs/screenshots/e107-options-i3theme-rubyred-variants.png)

### Admin Configuration — Selectors, Ordering & Status
![Admin Selectors](docs/screenshots/e107-options-admin-selectors-and-help.png)

### Admin Configuration — Allowed Themes & Color Mode Engine
![Admin Themes](docs/screenshots/e107-options-admin-themes.png)

---

## Installation

### Option A: One-Click Upload via e107 Admin (Recommended)
1. Download **`e107_options_v1.0.0.zip`** from the [latest GitHub Release](https://github.com/scottdd/e107-display-options/releases/latest).
2. In your e107 Admin area, go to **Admin → Plugin Manager → Upload Plugin** and upload `e107_options_v1.0.0.zip`.
3. Click **Install** next to **e107 display options** (`e107_options`).

### Option B: Clone or Extract into `e107_plugins/`
```bash
cd /path/to/e107/e107_plugins
git clone https://github.com/scottdd/e107-display-options.git e107_options
```
Then visit **Admin → Plugin Manager** and install **e107 display options**.

---

## Placement & Shortcodes

- **Sidebar / Menu Manager:** Enable `e107_options_menu` in **Admin → Menu Manager** to display the collapsible card in any theme menu area.
- **Navbar Popover Trigger:** Add `{E107_OPTIONS_NAV}` to your theme's navbar/header template (or enable automatic navbar injection in **Admin → e107 Display Options**).

---

## Compatibility & License

- **e107 CMS:** v2.3.0+
- **PHP:** 7.4, 8.0, 8.1, 8.2, 8.3+
- **Frontend Frameworks:** Bootstrap 3, Bootstrap 4, Bootstrap 5, and custom e107 v2 themes
- **License:** [GPL-2.0-or-later](LICENSE)

---

*Imagined by human, built with AI.*
