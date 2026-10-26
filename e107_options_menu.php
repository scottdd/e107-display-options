<?php
/**
 * e107_options — Sidebar Menu Item (e107_options_menu.php)
 * Can be placed in any Menu Area via e107 Admin -> Menu Manager.
 */

if (!defined('e107_INIT')) { exit; }

require_once(e_PLUGIN . 'e107_options/includes/e107_options_manager.php');

$text = e107_options_manager::renderWidget('inline');
if ($text !== '')
{
	e107::getRender()->tablerender(LAN_E107OPT_PANEL_TITLE, $text, 'e107_options_menu');
}
