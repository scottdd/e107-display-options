<?php
/**
 * e107_options — Central Capability Detector, Preference Manager & Renderer
 * Compatible with e107 v2.3.x and PHP 7.4–8.4+ (v1.0.1)
 */

if (!defined('e107_INIT')) { exit; }

$_e107optPluginDir = dirname(__DIR__);
$_e107optLang = defined('e_LANGUAGE') ? e_LANGUAGE : 'English';
if (is_readable($_e107optPluginDir . '/languages/' . $_e107optLang . '/' . $_e107optLang . '_front.php'))
{
	e107::includeLan($_e107optPluginDir . '/languages/' . $_e107optLang . '/' . $_e107optLang . '_front.php');
}
elseif (is_readable($_e107optPluginDir . '/languages/English/English_front.php'))
{
	e107::includeLan($_e107optPluginDir . '/languages/English/English_front.php');
}

class e107_options_manager
{
	/**
	 * Return the actual directory name under e107_plugins/ (e.g. 'e107_options' or 'e107_display_options').
	 *
	 * @return string
	 */
	public static function getPluginFolder(): string
	{
		return basename(dirname(__DIR__));
	}

	/**
	 * Return plugin preferences merged with defaults.
	 *
	 * @return array
	 */
	public static function getPrefs(): array
	{
		$defaults = array(
			'default_mode'           => 'popover',
			'auto_navbar_inject'     => 1,
			'navbar_target_selector' => '#theme-toggle, .site-header .d-flex, .navbar-nav, #navigation, #subnav, #slink',
			'replace_theme_toggle'   => 0,
			'colormode_force_all'    => 1,
			'allowed_themes'         => '',
			'colormode_themes'       => '',
			'sel_colormode_blocked'  => 0,
			'sel_colormode_order'    => 1,
			'sel_colormode_class'    => e_UC_PUBLIC,
			'sel_theme_blocked'      => 0,
			'sel_theme_order'        => 2,
			'sel_theme_class'        => e_UC_PUBLIC,
			'sel_language_blocked'   => 0,
			'sel_language_order'     => 3,
			'sel_language_class'     => e_UC_PUBLIC,
			'sel_fontsize_blocked'   => 0,
			'sel_fontsize_order'     => 4,
			'sel_fontsize_class'     => e_UC_PUBLIC,
			'sel_layoutwidth_blocked'=> 0,
			'sel_layoutwidth_order'  => 5,
			'sel_layoutwidth_class'  => e_UC_PUBLIC,
		);

		$folder = self::getPluginFolder();
		$stored = e107::pref($folder);
		if ((!is_array($stored) || empty($stored)) && $folder !== 'e107_options')
		{
			$fallback = e107::pref('e107_options');
			if (is_array($fallback) && !empty($fallback))
			{
				$stored = $fallback;
			}
		}
		if (!is_array($stored))
		{
			$stored = array();
		}

		return array_merge($defaults, $stored);
	}

	/**
	 * Parse comma-separated list into clean array of strings.
	 *
	 * @param mixed $val
	 * @return array
	 */
	public static function parseCsvList($val): array
	{
		if (is_array($val))
		{
			return array_values(array_filter(array_map('trim', $val)));
		}
		if (!is_string($val) || trim($val) === '')
		{
			return array();
		}
		return array_values(array_filter(array_map('trim', explode(',', $val))));
	}

	/**
	 * Inspect all installed themes in e107_themes/ and return metadata + stylesheet variants.
	 *
	 * @return array
	 */
	public static function getInstalledThemes(): array
	{
		static $cache = null;
		if ($cache !== null)
		{
			return $cache;
		}

		$themes = array();
		$dirs = @scandir(e_THEME);
		if (!is_array($dirs))
		{
			return array();
		}

		sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
		foreach ($dirs as $dir)
		{
			if ($dir === '.' || $dir === '..' || $dir === 'templates' || $dir === 'CVS')
			{
				continue;
			}
			$themePath = e_THEME . $dir;
			if (!is_dir($themePath) || !is_readable($themePath . '/theme.php'))
			{
				continue;
			}

			$info = array(
				'folder'        => $dir,
				'name'          => $dir,
				'version'       => '1.0',
				'compatibility' => '1.0',
				'is_v2'         => false,
				'defaultLayout' => 'default',
				'custompages'   => array(),
				'variants'      => array(),
				'native_dark'   => false,
			);

			$xmlPath = $themePath . '/theme.xml';
			if (is_readable($xmlPath))
			{
				$info['is_v2'] = true;
				$xml = @simplexml_load_file($xmlPath);
				if ($xml instanceof SimpleXMLElement)
				{
					$attrs = $xml->attributes();
					if (!empty($attrs['name']))
					{
						$info['name'] = (string) $attrs['name'];
					}
					if (!empty($attrs['version']))
					{
						$info['version'] = (string) $attrs['version'];
					}
					if (!empty($attrs['compatibility']))
					{
						$info['compatibility'] = (string) $attrs['compatibility'];
					}

					// Layouts
					if (isset($xml->layouts->layout))
					{
						$firstLayout = null;
						foreach ($xml->layouts->layout as $lay)
						{
							$lAttrs = $lay->attributes();
							$lName = isset($lAttrs['name']) ? (string) $lAttrs['name'] : '';
							if ($lName === '')
							{
								continue;
							}
							if ($firstLayout === null)
							{
								$firstLayout = $lName;
							}
							if (isset($lAttrs['default']) && strtolower((string) $lAttrs['default']) === 'true')
							{
								$info['defaultLayout'] = $lName;
							}
							if (isset($lay->custompages))
							{
								$cpStr = trim((string) $lay->custompages);
								if ($cpStr !== '')
								{
									$info['custompages'][$lName] = array_values(array_filter(explode(' ', $cpStr)));
								}
							}
						}
						if ($firstLayout !== null && empty($info['defaultLayout']))
						{
							$info['defaultLayout'] = $firstLayout;
						}
					}

					// Stylesheet variants
					if (isset($xml->stylesheets->css))
					{
						foreach ($xml->stylesheets->css as $css)
						{
							$cAttrs = $css->attributes();
							$file = isset($cAttrs['file']) ? trim((string) $cAttrs['file']) : '';
							$label = isset($cAttrs['name']) ? trim((string) $cAttrs['name']) : $file;
							$scope = isset($cAttrs['scope']) ? trim((string) $cAttrs['scope']) : 'front';
							$isDef = isset($cAttrs['default']) && strtolower((string) $cAttrs['default']) === 'true';

							if ($file === '' || $file === '*' || $scope === 'admin' || $scope === 'wysiwyg')
							{
								continue;
							}
							$info['variants'][$file] = array(
								'file'    => $file,
								'label'   => ($label !== '' && $label !== '*') ? $label : $file,
								'default' => $isDef,
							);
						}
					}
				}
			}
			else
			{
				// Legacy v1 theme.php metadata
				$phpContent = @file_get_contents($themePath . '/theme.php');
				if (is_string($phpContent))
				{
					if (preg_match('/\$themename\s*=\s*[\'"]([^\'"]+)[\'"]/i', $phpContent, $m))
					{
						$info['name'] = trim($m[1]);
					}
					if (preg_match('/\$themeversion\s*=\s*[\'"]([^\'"]+)[\'"]/i', $phpContent, $m))
					{
						$info['version'] = trim($m[1]);
					}
				}
				$info['defaultLayout'] = 'legacyDefault';
			}

			// Friendly display title
			if ($info['name'] === 'sd_moderndark')
			{
				$info['display_name'] = 'ModernDark (v2.0)';
			}
			else
			{
				$info['display_name'] = $info['name'];
			}

			// Check if theme has native dark mode support (Bootstrap 5.3 / data-bs-theme)
			$phpContent = @file_get_contents($themePath . '/theme.php');
			if ($dir === 'moderndark' || $dir === 'bootstrap5' || (is_string($phpContent) && strpos($phpContent, 'bootstrap@5') !== false))
			{
				$info['native_dark'] = true;
			}

			$themes[$dir] = $info;
		}

		$cache = $themes;
		return $themes;
	}

	/**
	 * Return allowed themes that actually exist in e107_themes/.
	 *
	 * @return array
	 */
	public static function getAllowedThemes(): array
	{
		$prefs = self::getPrefs();
		$allowedList = self::parseCsvList($prefs['allowed_themes']);
		$installed = self::getInstalledThemes();

		$result = array();
		if (empty($allowedList))
		{
			foreach ($installed as $folder => $info)
			{
				if ($folder !== '_blank' && !empty($info['is_v2']))
				{
					$result[$folder] = $info;
				}
			}
		}
		else
		{
			foreach ($allowedList as $folder)
			{
				if (isset($installed[$folder]) && $folder !== '_blank')
				{
					$result[$folder] = $installed[$folder];
				}
			}
		}

		// Ensure the current site default theme is always included if valid
		$defaultSiteTheme = e107::getPref('sitetheme', 'moderndark');
		if (isset($installed[$defaultSiteTheme]) && !isset($result[$defaultSiteTheme]))
		{
			$result = array($defaultSiteTheme => $installed[$defaultSiteTheme]) + $result;
		}

		return $result;
	}

	/**
	 * Return currently active frontend theme folder.
	 *
	 * @return string
	 */
	public static function getActiveTheme(): string
	{
		if (!empty($GLOBALS['E107OPT_ACTIVE_THEME']))
		{
			return (string) $GLOBALS['E107OPT_ACTIVE_THEME'];
		}
		if (defined('USERTHEME') && USERTHEME !== false && USERTHEME !== '')
		{
			return (string) USERTHEME;
		}
		return (string) e107::getPref('sitetheme', 'moderndark');
	}

	/**
	 * Return currently active stylesheet variant for the active theme.
	 *
	 * @return string
	 */
	public static function getActiveVariant(): string
	{
		if (!empty($GLOBALS['E107OPT_ACTIVE_VARIANT']))
		{
			return (string) $GLOBALS['E107OPT_ACTIVE_VARIANT'];
		}
		if (defined('THEME_STYLE') && THEME_STYLE !== '')
		{
			return (string) THEME_STYLE;
		}
		return (string) e107::getPref('themecss', 'style.css');
	}

	/**
	 * Return installed e107 languages in e107_languages/.
	 *
	 * @return array
	 */
	public static function getInstalledLanguages(): array
	{
		$langs = array();
		$dirs = @scandir(e_LANGUAGEDIR);
		if (is_array($dirs))
		{
			foreach ($dirs as $d)
			{
				if ($d === '.' || $d === '..')
				{
					continue;
				}
				if (is_dir(e_LANGUAGEDIR . $d) && is_readable(e_LANGUAGEDIR . $d . '/' . $d . '.php'))
				{
					$langs[] = $d;
				}
			}
		}
		if (empty($langs))
		{
			$langs[] = 'English';
		}
		sort($langs);
		return $langs;
	}

	/**
	 * Return metadata, capability detection status, admin block state, order, and userclass for all 5 selectors.
	 *
	 * @return array
	 */
	public static function getSelectorsStatus(): array
	{
		$prefs = self::getPrefs();
		$installedThemes = self::getInstalledThemes();
		$allowedThemes = self::getAllowedThemes();
		$activeTheme = self::getActiveTheme();
		$colormodeThemes = self::parseCsvList($prefs['colormode_themes']);
		$forceAllColor = !empty($prefs['colormode_force_all']);
		$langs = self::getInstalledLanguages();

		$activeThemeInfo = $installedThemes[$activeTheme] ?? null;
		$variantCount = ($activeThemeInfo && count($activeThemeInfo['variants']) > 1) ? count($activeThemeInfo['variants']) : 0;

		// 1. Color Mode
		$isNativeCm = in_array($activeTheme, $colormodeThemes, true) || ($activeThemeInfo && !empty($activeThemeInfo['native_dark']));
		$cmSupported = $isNativeCm || $forceAllColor;
		$cmStatusText = $isNativeCm
			? "Supported natively by {$activeTheme} (Light / Dark / System Auto)"
			: ($forceAllColor ? "Supported via Universal Dark/Light Mode CSS engine (Active theme: {$activeTheme})" : "Inactive on {$activeTheme} (Not in native color-mode list)");

		// 2. Theme & Variant Switcher
		$thCount = count($allowedThemes);
		$thSupported = ($thCount > 1) || ($variantCount > 1);
		$thStatusText = "{$thCount} allowed v2 themes ready" . ($variantCount > 1 ? " + {$variantCount} color schemes on {$activeTheme}" : "");

		// 3. Language Switcher
		$langCount = count($langs);
		$langSupported = ($langCount > 1);
		$langStatusText = ($langCount > 1)
			? "{$langCount} languages installed (" . implode(', ', $langs) . ") — Active"
			: "Only 1 language installed (" . ($langs[0] ?? 'English') . ") — Auto-Hidden on frontend until a 2nd language pack is installed";

		// 4. Font Size / Accessibility Scaler
		$fsSupported = true;
		$fsStatusText = "Supported on all themes (A- 90%, A 100%, A+ 112%, A++ 125%)";

		// 5. Layout Width & Reader Focus
		$lwSupported = true;
		$lwStatusText = "Supported on all themes (Standard, Wide Fluid, & Distraction-Free Reader Focus)";

		$selectors = array(
			'colormode' => array(
				'id'          => 'colormode',
				'title'       => 'Light / Dark / Auto Mode',
				'icon'        => 'bi-circle-half',
				'description' => 'Instant zero-FOUC Light, Dark, and OS Auto color theme switcher. Syncs with ModernDark and Bootstrap 5.3 data-bs-theme.',
				'supported'   => $cmSupported,
				'status_text' => $cmStatusText,
				'blocked'     => (int) ($prefs['sel_colormode_blocked'] ?? 0),
				'order'       => (int) ($prefs['sel_colormode_order'] ?? 1),
				'userclass'   => (int) ($prefs['sel_colormode_class'] ?? e_UC_PUBLIC),
			),
			'theme' => array(
				'id'          => 'theme',
				'title'       => 'Theme & Color Scheme Selector',
				'icon'        => 'bi-palette2',
				'description' => 'Allows visitors to switch between admin-approved e107 v2 themes and pick theme.xml stylesheet variants (e.g. i3theme\'s 5 color schemes).',
				'supported'   => $thSupported,
				'status_text' => $thStatusText,
				'blocked'     => (int) ($prefs['sel_theme_blocked'] ?? 0),
				'order'       => (int) ($prefs['sel_theme_order'] ?? 2),
				'userclass'   => (int) ($prefs['sel_theme_class'] ?? e_UC_PUBLIC),
			),
			'language' => array(
				'id'          => 'language',
				'title'       => 'Language Selector',
				'icon'        => 'bi-translate',
				'description' => 'Switches the active e107 site language session/cookie. Automatically hides when only 1 language is installed.',
				'supported'   => $langSupported,
				'status_text' => $langStatusText,
				'blocked'     => (int) ($prefs['sel_language_blocked'] ?? 0),
				'order'       => (int) ($prefs['sel_language_order'] ?? 3),
				'userclass'   => (int) ($prefs['sel_language_class'] ?? e_UC_PUBLIC),
			),
			'fontsize' => array(
				'id'          => 'fontsize',
				'title'       => 'Text Size / Accessibility Scaler',
				'icon'        => 'bi-type',
				'description' => 'Scales root typography cleanly (A- 90%, A 100%, A+ 112%, A++ 125%) with instant preview and localStorage persistence.',
				'supported'   => $fsSupported,
				'status_text' => $fsStatusText,
				'blocked'     => (int) ($prefs['sel_fontsize_blocked'] ?? 0),
				'order'       => (int) ($prefs['sel_fontsize_order'] ?? 4),
				'userclass'   => (int) ($prefs['sel_fontsize_class'] ?? e_UC_PUBLIC),
			),
			'layoutwidth' => array(
				'id'          => 'layoutwidth',
				'title'       => 'Container Width & Reader Focus',
				'icon'        => 'bi-layout-sidebar-inset-reverse',
				'description' => 'Toggles between Standard container width, Wide fluid width, and Reader Focus (collapses sidebars and centers articles).',
				'supported'   => $lwSupported,
				'status_text' => $lwStatusText,
				'blocked'     => (int) ($prefs['sel_layoutwidth_blocked'] ?? 0),
				'order'       => (int) ($prefs['sel_layoutwidth_order'] ?? 5),
				'userclass'   => (int) ($prefs['sel_layoutwidth_class'] ?? e_UC_PUBLIC),
			),
		);

		uasort($selectors, function ($a, $b) {
			if ($a['order'] === $b['order'])
			{
				return strcmp($a['id'], $b['id']);
			}
			return $a['order'] <=> $b['order'];
		});

		return $selectors;
	}

	/**
	 * Return only selectors that are supported, not blocked by admin, and visible to the current user's userclass.
	 *
	 * @return array
	 */
	public static function getVisibleFrontendSelectors(): array
	{
		$all = self::getSelectorsStatus();
		$visible = array();
		foreach ($all as $id => $sel)
		{
			if (!$sel['supported'] || !empty($sel['blocked']))
			{
				continue;
			}
			if (!check_class($sel['userclass']))
			{
				continue;
			}
			$visible[$id] = $sel;
		}
		return $visible;
	}

	/**
	 * Render the inner controls HTML for all active selectors in configured sort order.
	 *
	 * @param string $context 'popover' or 'inline'
	 * @return string
	 */
	public static function renderControlsInner(string $context = 'popover'): string
	{
		$visible = self::getVisibleFrontendSelectors();
		if (empty($visible))
		{
			return '';
		}

		$allowedThemes = self::getAllowedThemes();
		$activeTheme = self::getActiveTheme();
		$activeVariant = self::getActiveVariant();
		$installedThemes = self::getInstalledThemes();
		$activeThemeInfo = $installedThemes[$activeTheme] ?? null;
		$variants = ($activeThemeInfo && count($activeThemeInfo['variants']) > 1) ? $activeThemeInfo['variants'] : array();
		$langs = self::getInstalledLanguages();
		$currentLang = defined('e_LANGUAGE') ? e_LANGUAGE : 'English';

		$html = '';

		foreach ($visible as $id => $sel)
		{
			if ($id === 'colormode')
			{
				$html .= '
				<div class="e107opt-group" data-selector="colormode">
					<div class="e107opt-label-row">
						<span class="e107opt-label"><span class="e107opt-icon">&#9788;</span> ' . LAN_E107OPT_COLORMODE . '</span>
						<span class="e107opt-value-badge" data-e107opt-badge="colormode">Dark</span>
					</div>
					<div class="e107opt-segmented" role="group" aria-label="' . LAN_E107OPT_COLORMODE . '">
						<button type="button" class="e107opt-seg-btn" data-e107opt-colormode="light" title="Light Mode">&#9728; ' . LAN_E107OPT_MODE_LIGHT . '</button>
						<button type="button" class="e107opt-seg-btn" data-e107opt-colormode="dark" title="Dark Mode">&#9790; ' . LAN_E107OPT_MODE_DARK . '</button>
						<button type="button" class="e107opt-seg-btn" data-e107opt-colormode="auto" title="Match System OS">&#9881; ' . LAN_E107OPT_MODE_AUTO . '</button>
					</div>
				</div>';
			}
			elseif ($id === 'theme')
			{
				$html .= '
				<div class="e107opt-group" data-selector="theme">
					<div class="e107opt-label-row">
						<span class="e107opt-label"><span class="e107opt-icon">&#127912;</span> ' . LAN_E107OPT_THEME . '</span>
						<span class="e107opt-value-badge">' . htmlspecialchars($activeThemeInfo['display_name'] ?? $activeTheme, ENT_QUOTES, 'UTF-8') . '</span>
					</div>
					<select class="e107opt-select" data-e107opt-theme-select aria-label="' . LAN_E107OPT_THEME . '">';
				foreach ($allowedThemes as $folder => $tInfo)
				{
					$selected = ($folder === $activeTheme) ? ' selected="selected"' : '';
					$vCount = count($tInfo['variants']);
					$suffix = ($vCount > 1) ? " ({$vCount} schemes)" : '';
					$label = htmlspecialchars($tInfo['display_name'] . $suffix, ENT_QUOTES, 'UTF-8');
					$html .= '<option value="' . htmlspecialchars($folder, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . $label . '</option>';
				}
				$html .= '</select>';

				if (!empty($variants))
				{
					$html .= '
					<div class="e107opt-sub-group">
						<div class="e107opt-label-row e107opt-sub-label">
							<span class="e107opt-label">' . LAN_E107OPT_VARIANT . '</span>
						</div>
						<div class="e107opt-variant-pills" role="group" aria-label="' . LAN_E107OPT_VARIANT . '">';
					foreach ($variants as $vFile => $vInfo)
					{
						$isAct = ($vFile === $activeVariant || (empty($variants[$activeVariant]) && !empty($vInfo['default'])));
						$actClass = $isAct ? ' is-active' : '';
						$swatchColor = self::guessVariantColor($vFile, $vInfo['label']);
						$html .= '<button type="button" class="e107opt-variant-btn' . $actClass . '" data-e107opt-variant="' . htmlspecialchars($vFile, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($vInfo['label'], ENT_QUOTES, 'UTF-8') . '">'
							. '<span class="e107opt-swatch" style="background:' . $swatchColor . ';"></span>'
							. '<span class="e107opt-variant-name">' . htmlspecialchars($vInfo['label'], ENT_QUOTES, 'UTF-8') . '</span>'
							. '</button>';
					}
					$html .= '</div></div>';
				}
				$html .= '</div>';
			}
			elseif ($id === 'language')
			{
				$html .= '
				<div class="e107opt-group" data-selector="language">
					<div class="e107opt-label-row">
						<span class="e107opt-label"><span class="e107opt-icon">&#127760;</span> ' . LAN_E107OPT_LANGUAGE . '</span>
					</div>
					<select class="e107opt-select" data-e107opt-lang-select aria-label="' . LAN_E107OPT_LANGUAGE . '">';
				foreach ($langs as $lang)
				{
					$selAttr = ($lang === $currentLang) ? ' selected="selected"' : '';
					$html .= '<option value="' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '"' . $selAttr . '>' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '</option>';
				}
				$html .= '</select></div>';
			}
			elseif ($id === 'fontsize')
			{
				$html .= '
				<div class="e107opt-group" data-selector="fontsize">
					<div class="e107opt-label-row">
						<span class="e107opt-label"><span class="e107opt-icon">Aa</span> ' . LAN_E107OPT_FONTSIZE . '</span>
						<span class="e107opt-value-badge" data-e107opt-badge="fontsize">100%</span>
					</div>
					<div class="e107opt-segmented" role="group" aria-label="' . LAN_E107OPT_FONTSIZE . '">
						<button type="button" class="e107opt-seg-btn" data-e107opt-fontsize="90" title="Small (90%)">A-</button>
						<button type="button" class="e107opt-seg-btn" data-e107opt-fontsize="100" title="Standard (100%)">A</button>
						<button type="button" class="e107opt-seg-btn" data-e107opt-fontsize="112" title="Large (112%)">A+</button>
						<button type="button" class="e107opt-seg-btn" data-e107opt-fontsize="125" title="Extra Large (125%)">A++</button>
					</div>
				</div>';
			}
			elseif ($id === 'layoutwidth')
			{
				$html .= '
				<div class="e107opt-group" data-selector="layoutwidth">
					<div class="e107opt-label-row">
						<span class="e107opt-label"><span class="e107opt-icon">&#9635;</span> ' . LAN_E107OPT_LAYOUTWIDTH . '</span>
						<span class="e107opt-value-badge" data-e107opt-badge="layoutwidth">Standard</span>
					</div>
					<div class="e107opt-segmented" role="group" aria-label="' . LAN_E107OPT_LAYOUTWIDTH . '">
						<button type="button" class="e107opt-seg-btn" data-e107opt-width="standard" title="Standard Container">' . LAN_E107OPT_WIDTH_STD . '</button>
						<button type="button" class="e107opt-seg-btn" data-e107opt-width="wide" title="Wide Fluid Container">' . LAN_E107OPT_WIDTH_WIDE . '</button>
						<button type="button" class="e107opt-seg-btn" data-e107opt-width="reader" title="Hide Sidebars &amp; Center Article">' . LAN_E107OPT_WIDTH_READER . '</button>
					</div>
				</div>';
			}
		}

		$html .= '
		<div class="e107opt-footer-row">
			<button type="button" class="e107opt-reset-btn" data-e107opt-reset>' . LAN_E107OPT_RESET . '</button>
		</div>';

		return $html;
	}

	/**
	 * Render complete widget in either 'popover' or 'inline' mode.
	 *
	 * @param string $mode 'popover' or 'inline'
	 * @return string
	 */
	public static function renderWidget(string $mode = 'popover'): string
	{
		$inner = self::renderControlsInner($mode);
		if ($inner === '')
		{
			return '';
		}

		if ($mode === 'inline')
		{
			return '
			<div class="e107opt-widget e107opt-inline-card" data-e107opt-widget="inline">
				' . $inner . '
			</div>';
		}

		// Popover mode (navbar trigger + dropdown panel)
		return '
		<div class="e107opt-widget e107opt-popover-wrap" data-e107opt-widget="popover">
			<button type="button" class="e107opt-trigger-btn" data-e107opt-toggle aria-expanded="false" aria-haspopup="true" title="' . LAN_E107OPT_PANEL_TITLE . '">
				<span class="e107opt-trigger-icon" aria-hidden="true">&#9881;</span>
				<span class="e107opt-trigger-text">' . LAN_E107OPT_BTN_LABEL . '</span>
				<span class="e107opt-trigger-caret" aria-hidden="true">&#9662;</span>
			</button>
			<div class="e107opt-popover-panel" data-e107opt-panel hidden>
				<div class="e107opt-popover-header">
					<span class="e107opt-popover-title">&#9881; ' . LAN_E107OPT_PANEL_TITLE . '</span>
					<button type="button" class="e107opt-popover-close" data-e107opt-close aria-label="Close">&times;</button>
				</div>
				<div class="e107opt-popover-body">
					' . $inner . '
				</div>
			</div>
		</div>';
	}

	/**
	 * Pick a representative CSS swatch color for a stylesheet variant name.
	 *
	 * @param string $file
	 * @param string $label
	 * @return string
	 */
	protected static function guessVariantColor(string $file, string $label): string
	{
		$hay = strtolower($file . ' ' . $label);
		if (strpos($hay, 'green') !== false) { return '#2e9e44'; }
		if (strpos($hay, 'midnight') !== false || strpos($hay, 'slate') !== false || strpos($hay, 'dark') !== false) { return '#1e3a5f'; }
		if (strpos($hay, 'ruby') !== false || strpos($hay, 'red') !== false || strpos($hay, 'journal') !== false) { return '#c92a2a'; }
		if (strpos($hay, 'pink') !== false || strpos($hay, 'quartz') !== false) { return '#d6336c'; }
		if (strpos($hay, 'aqua') !== false || strpos($hay, 'blue') !== false || strpos($hay, 'lumen') !== false) { return '#0ca678'; }
		if (strpos($hay, 'superhero') !== false) { return '#df691a'; }
		return '#2563eb';
	}
}
