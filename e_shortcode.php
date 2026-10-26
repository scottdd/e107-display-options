<?php
/**
 * e107_options — Global Shortcode {E107_OPTIONS}
 * Usage in theme templates:
 *   {E107_OPTIONS}
 *   {E107_OPTIONS: mode=popover}
 *   {E107_OPTIONS: mode=inline}
 */

if (!defined('e107_INIT')) { exit; }

class e107_options_shortcodes extends e_shortcode
{
	public function sc_e107_options($parm = null)
	{
		require_once(e_PLUGIN . 'e107_options/includes/e107_options_manager.php');
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
}
