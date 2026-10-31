<?php
/**
 * e107_options — Sidebar Menu Item (e107_options_menu.php) (v1.0.4)
 * Can be placed in any Menu Area via e107 Admin -> Menu Manager.
 */

if (!defined('e107_INIT')) { exit; }

require_once(__DIR__ . '/includes/e107_options_manager.php');

$text = e107_options_manager::renderWidget('inline');
if ($text !== '')
{
	$caption = defined('LAN_E107OPT_PANEL_TITLE') ? LAN_E107OPT_PANEL_TITLE : 'Display & Reader Options';
	e107::getRender()->tablerender($caption, $text, 'e107_options_menu');
}
