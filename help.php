<?php
/**
 * e107 Display Options (e107_options) - Admin Sidebar Help
 *
 * @package     e107_options
 * @version     1.0.4
 * @author      Scott Dowlen <https://github.com/scottdd/e107-tools-display-options>
 * @license     GPL-2.0-or-later
 * @note        Imagined by human, built with AI.
 */
if (!defined('e107_INIT')) { exit; }

$caption = 'e107 Display Options (v1.0.4)';
$text = '<strong>Display &amp; Reader Options</strong><br><br>'
	. '• <strong>Selectors &amp; Ordering:</strong> Reorder, enable/disable, or restrict Color Mode, Theme, Variant, Language, Text Size, and Reader Width controls.<br>'
	. '• <strong>Theme &amp; Color Mode Settings:</strong> Choose which installed frontend themes appear in the switcher and whether Universal Dark/Light Mode CSS applies to all themes.<br><hr style="margin:10px 0">'
	. '<small class="text-muted"><em>Imagined by human, built with AI.</em></small>';
e107::getRender()->tablerender($caption, $text);
