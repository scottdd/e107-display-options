# e107 Display Options (`e107_options`) v1.0.3

A unified, zero-FOUC **Display & Reader Options** plugin for **e107 v2.3+** (compatible with **PHP 7.4 through PHP 8.4+** and **Bootstrap 3, 4, and 5** themes).

Provides visitors with an accessible, persistent control panel — available as both a **Navbar Popover** (`{E107_OPTIONS}` / `{E107_OPTIONS_NAV}`) and a **Sidebar Menu** (`e107_options_menu`) — for customizing their reading and browsing experience without page-load flash (FOUC).

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

> **Important Note on Plugin Folder Name (`e107_options`):**
> - **Recommended:** Download **`e107-tools-display-options-v1.0.3.zip`** (or **`e107_options_v1.0.3.zip`**) from the [Releases page](https://github.com/scottdd/e107-tools-display-options/releases/latest). It is pre-packaged with the required **`e107_options/`** folder structure.
> - **If you use GitHub's green "Code → Download ZIP" button (`e107-tools-display-options-main.zip`) or `git clone`:** You **must** rename the extracted folder inside `e107_plugins/` to **`e107_options`** (or `e107_display_options` — **never use hyphens** like `e107-tools-display-options-main`). e107 v2's `e107_handlers/shortcode_handler.php` constructs the PHP shortcode class name directly from the folder name (`<folder>_shortcodes`), and PHP class names cannot contain hyphens (`-`).

### Option A: One-Click Upload via e107 Admin (Recommended)
1. Download **`e107-tools-display-options-v1.0.3.zip`** (or **`e107_options_v1.0.3.zip`**) from the [latest GitHub Release](https://github.com/scottdd/e107-tools-display-options/releases/latest).
2. In your e107 Admin area, go to **Admin → Plugin Manager → Upload Plugin** and upload the `.zip` archive.
3. Click **Install** next to **e107 display options** (`e107_options`).

### Option B: Clone or Extract into `e107_plugins/`
```bash
cd /path/to/e107/e107_plugins
git clone https://github.com/scottdd/e107-tools-display-options.git e107_options
```
Then visit **Admin → Plugin Manager** and install **e107 display options**.

---

## Placement & Shortcodes

- **Sidebar / Menu Manager:** Enable `e107_options_menu` in **Admin → Menu Manager** to display the collapsible card in any theme menu area.
- **Navbar Popover Trigger:** Add `{E107_OPTIONS}` (or `{E107_OPTIONS_NAV}`) to your theme's navbar/header template, or leave automatic navbar injection enabled in **Admin → e107 Display Options**.

---

## Changelog

### v1.0.3 (2026-10-26)
- **Fix Fatal `Call to undefined method e107_options_manager::getVisitorState()` in `e_module.php` (`#1`):**
  - Restored full `e_module.php` query-parameter and cookie handling (`?e107opt_reset`, `?e107opt_lang`, `?e107opt_theme`, `?e107opt_variant`) and userclass permission checks (`check_class()`), and implemented `e107_options_manager::getVisitorState()` in `includes/e107_options_manager.php`.
  - Fixed `$themeMeta['custompages']` key casing and single-stylesheet theme fallback (`$chosenVariant = 'style.css'`) in `e_module.php` so `https://` Bootswatch CDN URLs never leak across themes while keeping persistent `user_prefs` untouched.

### v1.0.2 (2026-10-26)
- **Hyphenated Folder & Stale `e_shortcode_list` Self-Healing (`#1`):**
  - Updated `e_shortcode.php` to rewrite `$classFunc = 'e107_options_shortcodes'` in `e_parse_shortcode::loadPluginShortcodes()` scope whenever the folder name is not a valid PHP class identifier (such as `e107-display-options` or `e107-tools-display-options-main`), completely preventing `get_class_methods('e107-display-options_shortcodes')` from throwing a fatal `TypeError` on PHP 8.0–8.4+ even if extracted into a hyphenated folder.
  - Added automatic self-healing in `e_module.php` and `e107_options_setup.php` (`e107_options_manager::healStaleFolderRegistrations()`) that runs on both frontend and `admin.php` requests before `e_parse_shortcode` initializes, stripping any leftover `'e107-display-options'` registrations from `e_shortcode_list`, `e_header_list`, `e_module_list`, `lan_global_list`, and `plug_installed` and upgrading any stale folder's `e_shortcode.php` in place.
- **Cross-Theme Stylesheet (`themecss`) Isolation (`#1`):**
  - Updated `e_module.php` so switching between multi-variant themes (such as `bootstrap5` with `https://` Bootswatch CDN stylesheets) and single-stylesheet themes (`bootstrap3`, `moderndark`, etc.) cleanly resets `themecss` to `style.css`, syncs `$GLOBALS['pref']` in memory without touching persistent `user_prefs`, and never leaks stylesheets across themes or into the admin area.

### v1.0.1 (2026-10-26)
- **Relative Path Resolution (`#1`):** Replaced all hardcoded `e_PLUGIN . 'e107_options/...'` file includes and asset/preference lookups with `__DIR__`-relative paths and `e107_options_manager::getPluginFolder()`, so the plugin works cleanly even if installed under an underscore-renamed folder such as `e107_display_options/`.
- **Shortcode Handler & PHP 8.2–8.4+ Compatibility (`#1`):** Added dynamic `class_alias('e107_options_shortcodes', '<folder>_shortcodes')` in `e_shortcode.php` and added `{E107_OPTIONS_NAV}` alongside `{E107_OPTIONS}`.
- **Menu Manager & Language Fallback Hardening (`#1`):** Added automatic `English_front.php` / `English_admin.php` fallback when a non-English site language pack does not yet include plugin translation files, preventing any `500` error when rendering `e107_options_menu`.

### v1.0.0 (2026-10-25)
- Initial public release.

---

## Compatibility & License

- **e107 CMS:** v2.3.0+
- **PHP:** 7.4, 8.0, 8.1, 8.2, 8.3, 8.4+
- **Frontend Frameworks:** Bootstrap 3, Bootstrap 4, Bootstrap 5, and custom e107 v2 themes
- **License:** [GPL-2.0-or-later](LICENSE)

---

*Imagined by human, built with AI.*
