<?php
/**
 * e107_options — Frontend Header Hook (e_header.php) (v1.0.4)
 * Injects the zero-FOUC early inline script and loads CSS/JS assets.
 */

if (!defined('e107_INIT')) { exit; }

if (deftrue('e_ADMIN_AREA'))
{
	return;
}

require_once(__DIR__ . '/includes/e107_options_manager.php');

$e107optPrefs = e107_options_manager::getPrefs();
$visibleSelectors = e107_options_manager::getVisibleFrontendSelectors();

if (empty($visibleSelectors))
{
	return;
}

$activeTheme = e107_options_manager::getActiveTheme();
$activeVariant = e107_options_manager::getActiveVariant();
$popoverHtml = '';
if (!empty($e107optPrefs['auto_navbar_inject']))
{
	$popoverHtml = e107_options_manager::renderWidget('popover');
}

$clientConfig = array(
	'activeTheme'          => $activeTheme,
	'activeVariant'        => $activeVariant,
	'defaultMode'          => $e107optPrefs['default_mode'] ?? 'popover',
	'autoNavbarInject'     => !empty($e107optPrefs['auto_navbar_inject']),
	'navbarTargetSelector' => $e107optPrefs['navbar_target_selector'] ?? '#theme-toggle, .site-header .d-flex, .navbar-nav, #navigation, #subnav, #slink',
	'replaceThemeToggle'   => !empty($e107optPrefs['replace_theme_toggle']),
	'colormodeForceAll'    => !empty($e107optPrefs['colormode_force_all']),
	'enabledSelectors'     => array_keys($visibleSelectors),
	'popoverHtml'          => $popoverHtml,
);

$jsonConfig = json_encode($clientConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

// Zero-FOUC early inline script: runs in <head> before body paint
$earlyScript = <<<JS
(function(){
	try {
		var root = document.documentElement;
		var cm = localStorage.getItem('e107opt_colormode') || localStorage.getItem('moderndark-theme') || 'dark';
		var resolved = cm;
		if (cm === 'auto') {
			resolved = (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
		}
		root.setAttribute('data-e107-colormode', cm);
		root.setAttribute('data-e107-resolved-theme', resolved);
		root.setAttribute('data-bs-theme', resolved);

		var fs = localStorage.getItem('e107opt_fontsize') || '100';
		root.setAttribute('data-e107-fontsize', fs);
		if (fs && fs !== '100') {
			root.style.fontSize = fs + '%';
		}

		var lw = localStorage.getItem('e107opt_width') || 'standard';
		root.setAttribute('data-e107-width', lw);
	} catch (e) {}
})();
window.E107OptionsConfig = {$jsonConfig};
JS;

e107::js('inline', $earlyScript);
$_e107optFolder = e107_options_manager::getPluginFolder();
e107::css($_e107optFolder, 'css/e107_options.css');
e107::js($_e107optFolder, 'js/e107_options.js', 'jquery');
