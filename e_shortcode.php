<?php
/**
 * e107_options — Global Shortcodes {E107_OPTIONS} and {E107_OPTIONS_NAV} (v1.0.3)
 * Usage in theme templates or HTML:
 *   {E107_OPTIONS}               -> Renders default mode (popover)
 *   {E107_OPTIONS: mode=popover} -> Compact navbar popover button + dropdown
 *   {E107_OPTIONS: mode=sidebar} -> Full sidebar panel card
 *   {E107_OPTIONS_NAV}           -> Explicit alias for navbar popover mode
 */

if (!defined('e107_INIT')) { exit; }

require_once(__DIR__ . '/includes/e107_options_manager.php');

class e107_options_shortcodes extends e_shortcode
{
	/**
	 * Primary shortcode: {E107_OPTIONS} or {E107_OPTIONS: mode=popover|sidebar}
	 *
	 * @param mixed $parm
	 * @return string
	 */
	public function sc_e107_options($parm = null)
	{
		$mode = 'popover';
		if (is_array($parm) && !empty($parm['mode']))
		{
			$mode = ($parm['mode'] === 'sidebar') ? 'sidebar' : 'popover';
		}
		elseif (is_string($parm) && strpos($parm, 'sidebar') !== false)
		{
			$mode = 'sidebar';
		}
		else
		{
			$prefs = e107_options_manager::getPrefs();
			if (!empty($prefs['default_mode']) && $prefs['default_mode'] === 'sidebar')
			{
				$mode = 'sidebar';
			}
		}

		return e107_options_manager::renderWidget($mode);
	}

	/**
	 * Explicit navbar popover shortcode: {E107_OPTIONS_NAV}
	 *
	 * @param mixed $parm
	 * @return string
	 */
	public function sc_e107_options_nav($parm = null)
	{
		return e107_options_manager::renderWidget('popover');
	}
}

// 1) Support alternate valid PHP identifier folder names (e.g., e107_display_options)
// via class_alias so direct class lookups succeed on PHP 7.4–8.4+.
$_e107OptFolder = basename(__DIR__);
$_e107OptDynamicClass = preg_replace('/[^a-zA-Z0-9_]/', '_', $_e107OptFolder) . '_shortcodes';
if ($_e107OptDynamicClass !== 'e107_options_shortcodes' && preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $_e107OptDynamicClass) && !class_exists($_e107OptDynamicClass, false))
{
	class_alias('e107_options_shortcodes', $_e107OptDynamicClass);
}

// 2) Support hyphenated folder names (e.g., e107-display-options or e107-tools-display-options-main).
// PHP class names cannot contain hyphens, so class_alias() cannot create 'e107-display-options_shortcodes'.
// However, e107_handlers/shortcode_handler.php::loadPluginShortcodes() executes include_once($path) in its own
// local scope right before calling $this->registerClassMethods($classFunc, $path, false). Rewriting $classFunc
// to the canonical 'e107_options_shortcodes' class prevents get_class_methods($classFunc) from throwing a fatal
// TypeError on PHP 8.0–8.4+ even when the plugin was extracted into a hyphenated folder.
if (isset($classFunc) && is_string($classFunc) && !class_exists($classFunc, false))
{
	$classFunc = 'e107_options_shortcodes';
}
