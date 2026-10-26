<?php
if (!defined('e107_INIT')) { exit; }

define('LAN_PLUGIN_E107OPT_NAME',    'e107 display options');
define('LAN_PLUGIN_E107OPT_SUMMARY', 'Unified Display & Reader Options control panel (Color Mode, Theme & Variant Switcher, Language Switcher, Text Size, and Reader Width) in Navbar Popover and Sidebar Menu modes.');
define('LAN_PLUGIN_E107OPT_DESC',    'Provides a zero-FOUC Display & Reader Options widget for e107 v2.3+ (PHP 7.4 & PHP 8.x) with automatic capability detection, per-selector ordering, force-blocking, and userclass permissions. Imagined by human, built with AI.');
define('LAN_PLUGIN_E107OPT_MENU',    'Display & Reader Options');

if (defined('e_ADMIN_AREA') && e_ADMIN_AREA && class_exists('e107'))
{
	try
	{
		$sql = e107::getDb();
		if ($sql && $sql->select('plugin', 'plugin_id, plugin_category', "plugin_path = 'e107_options' AND plugin_category = 'menu' LIMIT 1"))
		{
			$sql->update('plugin', "plugin_category = 'settings' WHERE plugin_path = 'e107_options' AND plugin_category = 'menu'");
		}
	}
	catch (Exception $e) {}
}
