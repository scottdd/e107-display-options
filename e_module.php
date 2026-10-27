<?php
/**
 * e107_options — Early Boot Module (e_module.php) (v1.0.3)
 *
 * Loaded during class2.php initialization BEFORE theme initialization (e_theme::initTheme).
 * Allows visitors with permission to switch frontend Theme, Stylesheet Variant, or Language
 * via query parameters or persistent cookies without altering global database preferences
 * or mutating persistent user_prefs. Also heals stale plugin folder registrations.
 */

if (!defined('e107_INIT')) { exit; }

require_once(__DIR__ . '/includes/e107_options_manager.php');

// Self-heal stale folder registrations in core plugin table and e_module_list / e_header_list
e107_options_manager::healStaleFolderRegistrations();

// Never override admin area theme or language
if (deftrue('e_ADMIN_AREA'))
{
	return;
}

$e107optPrefs = e107_options_manager::getPrefs();
$cookieExpire = time() + (180 * 86400); // 180 days
$cookiePath   = defined('e_HTTP') ? e_HTTP : '/';

// ---------------------------------------------------------------------------
// 1. Handle Reset Action (?e107opt_reset=1)
// ---------------------------------------------------------------------------
if (isset($_GET['e107opt_reset']))
{
	@setcookie('e107opt_theme', '', time() - 3600, $cookiePath);
	@setcookie('e107opt_variant', '', time() - 3600, $cookiePath);
	unset($_COOKIE['e107opt_theme'], $_COOKIE['e107opt_variant']);
}

// ---------------------------------------------------------------------------
// 2. Language Switcher (Query Param ?e107opt_lang=...)
// ---------------------------------------------------------------------------
$canSwitchLang = empty($e107optPrefs['sel_language_blocked'])
	&& (!function_exists('check_class') || check_class((int) ($e107optPrefs['sel_language_class'] ?? e_UC_PUBLIC)));

if ($canSwitchLang && !empty($_GET['e107opt_lang']))
{
	$reqLang = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $_GET['e107opt_lang']);
	$installedLangs = e107_options_manager::getInstalledLanguages();
	if (in_array($reqLang, $installedLangs, true))
	{
		e107::getSession()->set('e_language', $reqLang);
		@setcookie('e107_language', $reqLang, $cookieExpire, $cookiePath, '', false, false);
		$_COOKIE['e107_language'] = $reqLang;
	}
}

// ---------------------------------------------------------------------------
// 3. Frontend Theme & Stylesheet Variant Switcher
// ---------------------------------------------------------------------------
$canSwitchTheme = empty($e107optPrefs['sel_theme_blocked'])
	&& (!function_exists('check_class') || check_class((int) ($e107optPrefs['sel_theme_class'] ?? e_UC_PUBLIC)));

if ($canSwitchTheme)
{
	$allowedThemes = e107_options_manager::getAllowedThemes();
	$chosenTheme   = null;
	$chosenVariant = null;

	// A. Check Query Parameter for Theme Switch
	if (!empty($_GET['e107opt_theme']))
	{
		$reqTheme = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $_GET['e107opt_theme']);
		if (isset($allowedThemes[$reqTheme]))
		{
			$chosenTheme = $reqTheme;
			@setcookie('e107opt_theme', $chosenTheme, $cookieExpire, $cookiePath, '', false, false);
			$_COOKIE['e107opt_theme'] = $chosenTheme;

			// Reset variant cookie when switching themes unless explicitly passed
			if (!isset($_GET['e107opt_variant']))
			{
				@setcookie('e107opt_variant', '', time() - 3600, $cookiePath);
				unset($_COOKIE['e107opt_variant']);
			}
		}
	}
	elseif (!empty($_COOKIE['e107opt_theme']))
	{
		$cookieTheme = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $_COOKIE['e107opt_theme']);
		if (isset($allowedThemes[$cookieTheme]))
		{
			$chosenTheme = $cookieTheme;
		}
	}

	// Determine the effective theme to validate variants against
	$activeThemeFolder = $chosenTheme ?: e107::getPref('sitetheme', 'moderndark');
	$installedThemes   = e107_options_manager::getInstalledThemes();
	$themeMeta         = $installedThemes[$activeThemeFolder] ?? null;

	// B. Check Query Parameter or Cookie for Stylesheet Variant
	if ($themeMeta && !empty($themeMeta['variants']))
	{
		if (isset($_GET['e107opt_variant']) && $_GET['e107opt_variant'] !== '')
		{
			$vCandidate = trim((string) $_GET['e107opt_variant']);
			if (isset($themeMeta['variants'][$vCandidate]))
			{
				$chosenVariant = $vCandidate;
				@setcookie('e107opt_variant', $chosenVariant, $cookieExpire, $cookiePath, '', false, false);
				$_COOKIE['e107opt_variant'] = $chosenVariant;
			}
		}
		elseif (!empty($_COOKIE['e107opt_variant']))
		{
			$vCandidate = trim((string) $_COOKIE['e107opt_variant']);
			if (isset($themeMeta['variants'][$vCandidate]))
			{
				$chosenVariant = $vCandidate;
			}
		}

		// If theme was switched but no variant chosen, pick the theme's default variant
		if ($chosenVariant === null && $chosenTheme !== null)
		{
			foreach ($themeMeta['variants'] as $vFile => $vInfo)
			{
				if (!empty($vInfo['default']))
				{
					$chosenVariant = $vFile;
					break;
				}
			}
			if ($chosenVariant === null)
			{
				$chosenVariant = (string) array_key_first($themeMeta['variants']);
			}
		}
	}
	elseif ($themeMeta && empty($themeMeta['variants']) && $chosenTheme !== null)
	{
		// Single-stylesheet theme: force style.css so an https:// Bootswatch CDN URL from
		// another theme (e.g. bootstrap5) never leaks into THEME_STYLE / themecss.
		$chosenVariant = 'style.css';
	}

	// C. Apply Runtime Overrides to e107 Core Config & $GLOBALS['pref'] (in-memory only, never saved to DB)
	if ($chosenTheme !== null)
	{
		$GLOBALS['E107OPT_ACTIVE_THEME'] = $chosenTheme;

		if (!defined('USERTHEME'))
		{
			define('USERTHEME', $chosenTheme);
		}

		$coreConfig = e107::getConfig('core');
		$coreConfig->set('sitetheme', $chosenTheme);
		$GLOBALS['pref']['sitetheme'] = $chosenTheme;

		if ($themeMeta)
		{
			$defLayout = $themeMeta['defaultLayout'] ?: 'default';
			$customPages = is_array($themeMeta['custompages'] ?? null) ? $themeMeta['custompages'] : array();
			$coreConfig->set('sitetheme_deflayout', $defLayout);
			$coreConfig->set('sitetheme_custompages', $customPages);
			$GLOBALS['pref']['sitetheme_deflayout'] = $defLayout;
			$GLOBALS['pref']['sitetheme_custompages'] = $customPages;
		}
	}

	if ($chosenVariant !== null)
	{
		$GLOBALS['E107OPT_ACTIVE_VARIANT'] = $chosenVariant;
		$coreConfig = e107::getConfig('core');
		$coreConfig->set('themecss', $chosenVariant);
		$GLOBALS['pref']['themecss'] = $chosenVariant;
	}
}
