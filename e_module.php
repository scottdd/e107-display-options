<?php
/**
 * e107_options — Early Boot Module (e_module.php) (v1.0.2)
 * Loaded during class2.php initialization BEFORE init_session() and BEFORE USERTHEME / e_LANGUAGE
 * and BEFORE e_parse_shortcode is initialized.
 *
 * Responsibilities:
 *  0. Self-heal any stale hyphenated plugin registrations (e.g. 'e107-display-options' left over in
 *     e_shortcode_list / e_header_list / e_module_list / plug_installed from an earlier GitHub archive
 *     extraction) on both frontend and admin requests before shortcode_handler.php runs.
 *  1. Read visitor's chosen theme cookie (e107opt_theme) and validate against admin-allowed themes.
 *  2. Override core sitetheme, themecss, sitetheme_deflayout, and sitetheme_custompages cleanly for
 *     the current request so both single-stylesheet themes (bootstrap3, moderndark) and multi-variant
 *     themes (bootstrap5 Bootswatch, i3theme) load their exact CSS and layout without leaking across
 *     themes or into the admin area.
 */

if (!defined('e107_INIT')) { exit; }

require_once(__DIR__ . '/includes/e107_options_manager.php');

// Always heal any stale hyphenated folder registrations before e_ADMIN_AREA returns,
// because admin.php (adminstyle_flexpanel->render) and frontend themes both initialize e_parse_shortcode.
e107_options_manager::healStaleFolderRegistrations();

// Never override frontend themes or cookies inside the e107 Admin area.
if (deftrue('e_ADMIN_AREA'))
{
	return;
}

$prefs = e107_options_manager::getPrefs();
$state = e107_options_manager::getVisitorState();

$chosenTheme   = $state['theme'];
$chosenVariant = $state['variant'];

// 1. Validate & apply Theme + Variant override if Theme Selector is not force-blocked
if (empty($prefs['sel_theme_blocked']))
{
	$allowedThemes = e107_options_manager::getAllowedThemes();

	if ($chosenTheme !== null && isset($allowedThemes[$chosenTheme]))
	{
		$themeMeta = $allowedThemes[$chosenTheme];

		// Validate variant against this theme's declared variants; if none or invalid, reset to default stylesheet
		$validVariant = null;
		if (!empty($themeMeta['variants']))
		{
			foreach ($themeMeta['variants'] as $v)
			{
				if ($chosenVariant !== null && $v['file'] === $chosenVariant)
				{
					$validVariant = $chosenVariant;
					break;
				}
			}
			if ($validVariant === null)
			{
				$validVariant = $themeMeta['variants'][0]['file'];
			}
		}
		else
		{
			// Single-stylesheet theme (e.g. bootstrap3, moderndark): explicitly reset to 'style.css'
			// so an https:// Bootswatch URL from bootstrap5 never leaks into THEME_STYLE.
			$validVariant = 'style.css';
		}

		// Prevent class2.php from overriding our theme selection via a logged-in user's old USERTHEME pref
		if (!defined('USERTHEME'))
		{
			define('USERTHEME', false);
		}

		// Sync both e107::getConfig('core') and $GLOBALS['pref'] in memory for this request only
		// (never mutate e107::getUser()->getConfig() so visitor previews cannot be saved to admin user_prefs)
		$coreCfg = e107::getConfig('core');
		if ($coreCfg)
		{
			$coreCfg->set('sitetheme', $chosenTheme);
			$coreCfg->set('themecss', $validVariant);

			if (!empty($themeMeta['defaultLayout']))
			{
				$coreCfg->set('sitetheme_deflayout', $themeMeta['defaultLayout']);
			}
			if (is_array($themeMeta['customPages']))
			{
				$coreCfg->set('sitetheme_custompages', $themeMeta['customPages']);
			}
		}

		if (isset($GLOBALS['pref']) && is_array($GLOBALS['pref']))
		{
			$GLOBALS['pref']['sitetheme'] = $chosenTheme;
			$GLOBALS['pref']['themecss']  = $validVariant;
			if (!empty($themeMeta['defaultLayout']))
			{
				$GLOBALS['pref']['sitetheme_deflayout'] = $themeMeta['defaultLayout'];
			}
			if (is_array($themeMeta['customPages']))
			{
				$GLOBALS['pref']['sitetheme_custompages'] = $themeMeta['customPages'];
			}
		}
	}
	elseif ($chosenVariant !== null)
	{
		// Visitor stayed on the site's default theme, but chose a stylesheet variant
		$siteTheme = e107::getPref('sitetheme', '');
		if ($siteTheme !== '' && isset($allowedThemes[$siteTheme]) && !empty($allowedThemes[$siteTheme]['variants']))
		{
			foreach ($allowedThemes[$siteTheme]['variants'] as $v)
			{
				if ($v['file'] === $chosenVariant)
				{
					$coreCfg = e107::getConfig('core');
					if ($coreCfg)
					{
						$coreCfg->set('themecss', $chosenVariant);
					}
					if (isset($GLOBALS['pref']) && is_array($GLOBALS['pref']))
					{
						$GLOBALS['pref']['themecss'] = $chosenVariant;
					}
					break;
				}
			}
		}
	}
}
