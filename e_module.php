<?php
/**
 * e107_options — Early Request Hook (e_module.php)
 * Loaded in class2.php before theme initialization so visitor theme & variant choices
 * apply cleanly on the current request for both guests and logged-in users.
 */

if (!defined('e107_INIT')) { exit; }

if (deftrue('e_ADMIN_AREA'))
{
	return;
}

require_once(__DIR__ . '/includes/e107_options_manager.php');

$e107optPrefs = e107_options_manager::getPrefs();
$cookiePath = defined('e_HTTP') ? e_HTTP : '/';
$cookieExpire = time() + (86400 * 90); // 90 days

// Handle full reset request
if (!empty($_GET['e107opt_reset']))
{
	setcookie('e107opt_theme', '', time() - 3600, $cookiePath);
	setcookie('e107opt_variant', '', time() - 3600, $cookiePath);
	unset($_COOKIE['e107opt_theme'], $_COOKIE['e107opt_variant']);
}

// 1. Language Switcher (?e107opt_lang=...)
if (empty($e107optPrefs['sel_language_blocked']) && check_class((int) ($e107optPrefs['sel_language_class'] ?? e_UC_PUBLIC)))
{
	if (!empty($_GET['e107opt_lang']) && is_string($_GET['e107opt_lang']))
	{
		$reqLang = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['e107opt_lang']);
		$installedLangs = e107_options_manager::getInstalledLanguages();
		if (in_array($reqLang, $installedLangs, true))
		{
			e107::getSession()->set('e_language', $reqLang);
			setcookie('e107_language', $reqLang, $cookieExpire, $cookiePath);
			$_COOKIE['e107_language'] = $reqLang;
		}
	}
}

// 2. Theme & Variant Switcher (?e107opt_theme=... & ?e107opt_variant=... or cookie)
if (empty($e107optPrefs['sel_theme_blocked']) && check_class((int) ($e107optPrefs['sel_theme_class'] ?? e_UC_PUBLIC)))
{
	$allowedThemes = e107_options_manager::getAllowedThemes();

	$chosenTheme = null;
	if (!empty($_GET['e107opt_theme']) && is_string($_GET['e107opt_theme']))
	{
		$candidate = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['e107opt_theme']);
		if (isset($allowedThemes[$candidate]))
		{
			$chosenTheme = $candidate;
			setcookie('e107opt_theme', $chosenTheme, $cookieExpire, $cookiePath);
			$_COOKIE['e107opt_theme'] = $chosenTheme;
			// Reset variant cookie if theme changed and no explicit variant was passed
			if (empty($_GET['e107opt_variant']))
			{
				setcookie('e107opt_variant', '', time() - 3600, $cookiePath);
				unset($_COOKIE['e107opt_variant']);
			}
		}
	}
	elseif (!empty($_COOKIE['e107opt_theme']) && is_string($_COOKIE['e107opt_theme']))
	{
		$candidate = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_COOKIE['e107opt_theme']);
		if (isset($allowedThemes[$candidate]))
		{
			$chosenTheme = $candidate;
		}
	}

	// Determine effective theme (chosen or site default)
	$effectiveTheme = $chosenTheme ?: e107::getPref('sitetheme', 'moderndark');
	$installedThemes = e107_options_manager::getInstalledThemes();
	$themeMeta = $installedThemes[$effectiveTheme] ?? null;

	// Determine stylesheet variant if the effective theme has variants
	$chosenVariant = null;
	if ($themeMeta && !empty($themeMeta['variants']))
	{
		if (!empty($_GET['e107opt_variant']) && is_string($_GET['e107opt_variant']))
		{
			$vCandidate = trim($_GET['e107opt_variant']);
			if (isset($themeMeta['variants'][$vCandidate]))
			{
				$chosenVariant = $vCandidate;
				setcookie('e107opt_variant', $chosenVariant, $cookieExpire, $cookiePath);
				$_COOKIE['e107opt_variant'] = $chosenVariant;
			}
		}
		elseif (!empty($_COOKIE['e107opt_variant']) && is_string($_COOKIE['e107opt_variant']))
		{
			$vCandidate = trim($_COOKIE['e107opt_variant']);
			if (isset($themeMeta['variants'][$vCandidate]))
			{
				$chosenVariant = $vCandidate;
			}
		}

		// Default to theme's default variant if none chosen
		if ($chosenVariant === null)
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
				$firstKey = array_key_first($themeMeta['variants']);
				$chosenVariant = $firstKey ?: 'style.css';
			}
		}
	}

	// Apply in-memory theme override (without saving to DB) so class2.php & e_theme load it cleanly
	if ($chosenTheme !== null && $themeMeta !== null)
	{
		$coreCfg = e107::getConfig('core');
		$coreCfg->set('sitetheme', $chosenTheme);
		$coreCfg->set('sitetheme_deflayout', $themeMeta['defaultLayout'] ?: 'default');
		$coreCfg->set('sitetheme_custompages', $themeMeta['custompages'] ?: array());

		if (!defined('USERTHEME'))
		{
			define('USERTHEME', $chosenTheme);
		}

		if (e107::getUser()->isUser())
		{
			$uCfg = e107::getUser()->getConfig();
			$uCfg->set('sitetheme', $chosenTheme);
			$uCfg->set('sitetheme_deflayout', $themeMeta['defaultLayout'] ?: 'default');
			$uCfg->set('sitetheme_custompages', $themeMeta['custompages'] ?: array());
		}

		$GLOBALS['E107OPT_ACTIVE_THEME'] = $chosenTheme;
	}

	if ($chosenVariant !== null)
	{
		e107::getConfig('core')->set('themecss', $chosenVariant);
		$GLOBALS['E107OPT_ACTIVE_VARIANT'] = $chosenVariant;
	}
}
