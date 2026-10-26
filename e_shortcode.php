<?php
/**
 * e107_options — Global Shortcodes {E107_OPTIONS} and {E107_OPTIONS_NAV} (v1.0.1)
 * Usage in theme templates:
 *   {E107_OPTIONS}
 *   {E107_OPTIONS: mode=popover}
 *   {E107_OPTIONS: mode=inline}
 *   {E107_OPTIONS_NAV}
 */

if (!defined('e107_INIT')) { exit; }

class e107_options_shortcodes extends e_shortcode
{
	public function sc_e107_options($parm = null)
	{
		require_once(__DIR__ . '/includes/e107_options_manager.php');
		$prefs = e107_options_manager::getPrefs();
		$mode = $prefs['default_mode'] ?? 'popover';

		if (is_array($parm) && !empty($parm['mode']))
		{
			$mode = ($parm['mode'] === 'inline') ? 'inline' : 'popover';
		}
		elseif (is_string($parm) && trim($parm) !== '')
		{
			if (strpos($parm, 'inline') !== false)
			{
				$mode = 'inline';
			}
			elseif (strpos($parm, 'popover') !== false)
			{
				$mode = 'popover';
			}
		}

		return e107_options_manager::renderWidget($mode);
	}

	public function sc_e107_options_nav($parm = null)
	{
		require_once(__DIR__ . '/includes/e107_options_manager.php');
		return e107_options_manager::renderWidget('popover');
	}
}

// Support alternate valid PHP identifier folder names (e.g., e107_display_options)
// so e107's shortcode_handler.php ($key . '_shortcodes') always resolves the class on PHP 7.4 and PHP 8.x.
$_e107optFolder = basename(__DIR__);
$_e107optDynamicClass = preg_replace('/[^a-zA-Z0-9_]/', '_', $_e107optFolder) . '_shortcodes';
if ($_e107optDynamicClass !== 'e107_options_shortcodes' && preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $_e107optDynamicClass) && !class_exists($_e107optDynamicClass, false))
{
	class_alias('e107_options_shortcodes', $_e107optDynamicClass);
}
