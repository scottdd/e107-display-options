<?php
/**
 * e107_options — Install & Upgrade Setup Handler (v1.0.4)
 * Automatically cleans up any stale hyphenated folder registrations (e.g. e107-display-options)
 * in core prefs and the plugin table during install or upgrade.
 */

if (!defined('e107_INIT')) { exit; }

class e107_options_setup
{
	public function install_pre($var)
	{
		require_once(__DIR__ . '/includes/e107_options_manager.php');
		e107_options_manager::healStaleFolderRegistrations();
	}

	public function install_post($var)
	{
		require_once(__DIR__ . '/includes/e107_options_manager.php');
		e107_options_manager::healStaleFolderRegistrations();
	}

	public function upgrade_post($var)
	{
		require_once(__DIR__ . '/includes/e107_options_manager.php');
		e107_options_manager::healStaleFolderRegistrations();
	}
}
