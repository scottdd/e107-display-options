<?php
/**
 * e107_options — Admin Configuration UI (admin_config.php)
 * Provides 3 rich tabs/views:
 *   1. Selectors & Ordering (main/selectors)
 *   2. Theme & Color Mode Settings (main/themes)
 *   3. Help & Companion Tools (main/help)
 */

require_once('../../class2.php');
if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

require_once(__DIR__ . '/includes/e107_options_manager.php');
$_e107optAdminLang = defined('e_LANGUAGE') ? e_LANGUAGE : 'English';
if (is_readable(__DIR__ . '/languages/' . $_e107optAdminLang . '/' . $_e107optAdminLang . '_admin.php'))
{
	e107::includeLan(__DIR__ . '/languages/' . $_e107optAdminLang . '/' . $_e107optAdminLang . '_admin.php');
}
elseif (is_readable(__DIR__ . '/languages/English/English_admin.php'))
{
	e107::includeLan(__DIR__ . '/languages/English/English_admin.php');
}
if (is_readable(__DIR__ . '/languages/English/English_admin.php'))
{
	include_once(__DIR__ . '/languages/English/English_admin.php');
}

class e107_options_adminArea extends e_admin_dispatcher
{
	protected $modes = array(
		'main' => array(
			'controller' => 'e107_options_ui',
			'path'       => null,
			'ui'         => 'e107_options_form_ui',
			'uipath'     => null,
		),
	);

	protected $adminMenu = array(
		'main/selectors' => array('caption' => 'Selectors & Ordering',        'perm' => 'P', 'icon' => 'fa-sliders'),
		'main/themes'    => array('caption' => 'Theme & Color Mode Settings', 'perm' => 'P', 'icon' => 'fa-paint-brush'),
		'main/help'      => array('caption' => 'Help & Companion Tools',      'perm' => 'P', 'icon' => 'fa-question-circle'),
	);

	protected $adminMenuAliases = array(
		'main/prefs' => 'main/selectors',
	);

	protected $menuTitle = 'e107 display options';
}

class e107_options_ui extends e_admin_ui
{
	protected $pluginTitle = 'e107 display options';
	protected $pluginName  = 'e107_options';
	protected $table       = '';
	protected $pid         = '';

	public function init()
	{
		$this->pluginName = e107_options_manager::getPluginFolder();
		$this->handlePostSave();
	}

	/**
	 * Handle saving forms from both the Selectors & Ordering tab and Theme & Color Mode tab.
	 */
	protected function handlePostSave()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		{
			return;
		}

		$cfg = e107::getPlugConfig(e107_options_manager::getPluginFolder());
		$mes = e107::getMessage();

		if (!empty($_POST['save_selectors']))
		{
			$updates = array(
				'default_mode'           => ($_POST['default_mode'] ?? 'popover') === 'inline' ? 'inline' : 'popover',
				'auto_navbar_inject'     => !empty($_POST['auto_navbar_inject']) ? 1 : 0,
				'navbar_target_selector' => trim((string) ($_POST['navbar_target_selector'] ?? '#theme-toggle, .site-header .d-flex, .navbar-nav, #navigation, #subnav, #slink')),
				'replace_theme_toggle'   => !empty($_POST['replace_theme_toggle']) ? 1 : 0,
			);

			$selectorIds = array('colormode', 'theme', 'language', 'fontsize', 'layoutwidth');
			foreach ($selectorIds as $id)
			{
				$updates["sel_{$id}_order"]   = max(1, min(99, (int) ($_POST["sel_{$id}_order"] ?? 1)));
				$updates["sel_{$id}_blocked"] = !empty($_POST["sel_{$id}_blocked"]) ? 1 : 0;
				$updates["sel_{$id}_class"]   = (int) ($_POST["sel_{$id}_class"] ?? e_UC_PUBLIC);
			}

			foreach ($updates as $k => $v)
			{
				$cfg->set($k, $v);
			}
			$cfg->save(false, true, false);
			$mes->addSuccess('Selectors, display ordering, and navbar popover preferences saved.');
		}
		elseif (!empty($_POST['save_themes']))
		{
			$allowed = isset($_POST['allowed_themes']) && is_array($_POST['allowed_themes'])
				? array_values(array_filter(array_map('trim', $_POST['allowed_themes'])))
				: array();
			$cmThemes = isset($_POST['colormode_themes']) && is_array($_POST['colormode_themes'])
				? array_values(array_filter(array_map('trim', $_POST['colormode_themes'])))
				: array();

			$cfg->set('allowed_themes', implode(',', $allowed));
			$cfg->set('colormode_themes', implode(',', $cmThemes));
			$cfg->set('colormode_force_all', !empty($_POST['colormode_force_all']) ? 1 : 0);
			$cfg->save(false, true, false);
			$mes->addSuccess('Allowed visitor themes and color-mode settings saved.');
		}
	}

	/**
	 * Default page redirect/alias to SelectorsPage.
	 */
	public function SelectorsPage()
	{
		$prefs = e107_options_manager::getPrefs();
		$selectors = e107_options_manager::getSelectorsStatus();
		$uc = e107::getUserClass();

		$html = '
		<form method="post" action="' . e_SELF . '?mode=main&amp;action=selectors">
			<div class="panel panel-default" style="margin-bottom:20px;">
				<div class="panel-heading">
					<h4 style="margin:0;"><i class="fa fa-sliders"></i> Frontend Option Selectors — Auto-Detection, Ordering &amp; Blocking</h4>
				</div>
				<div class="panel-body">
					<p class="text-muted" style="margin-bottom:14px;">
						Each selector automatically inspects your active theme and site configuration. It renders on the frontend only when
						<strong>Supported</strong> and <strong>Not Blocked</strong>. Adjust the numeric <strong>Order (1–5)</strong> to control the exact sequence in both the Navbar Popover and Sidebar Menu Card.
					</p>
					<div class="table-responsive">
						<table class="table table-striped table-bordered" style="margin-bottom:0;">
							<thead>
								<tr>
									<th style="width:90px;">Order</th>
									<th style="width:240px;">Selector</th>
									<th>Auto-Detected Capability Status</th>
									<th style="width:210px;">Admin Override (Block / Allow)</th>
									<th style="width:190px;">Visible To (Userclass)</th>
								</tr>
							</thead>
							<tbody>';

		foreach ($selectors as $id => $sel)
		{
			$isBlocked = !empty($sel['blocked']);
			$isSupported = !empty($sel['supported']);

			if ($isBlocked)
			{
				$badge = '<span class="label label-danger">Blocked by Admin</span>';
			}
			elseif ($isSupported)
			{
				$badge = '<span class="label label-success">Supported &amp; Active</span>';
			}
			else
			{
				$badge = '<span class="label label-warning">Auto-Hidden (Not Configured)</span>';
			}

			$blockSelect = '
				<select name="sel_' . $id . '_blocked" class="form-control input-sm">
					<option value="0"' . (!$isBlocked ? ' selected="selected"' : '') . '>Active (When Supported)</option>
					<option value="1"' . ($isBlocked ? ' selected="selected"' : '') . '>Blocked (Force Hidden)</option>
				</select>';

			$ucDropdown = $uc->uc_dropdown("sel_{$id}_class", $sel['userclass'], 'public,member,admin,nobody,classes', "class='form-control input-sm'");

			$html .= '
								<tr>
									<td>
										<input type="number" name="sel_' . $id . '_order" value="' . (int) $sel['order'] . '" min="1" max="20" class="form-control input-sm" style="width:70px;font-weight:bold;text-align:center;" />
									</td>
									<td>
										<strong>' . htmlspecialchars($sel['title'], ENT_QUOTES, 'UTF-8') . '</strong>
										<div class="small text-muted" style="margin-top:3px;"><code>' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '</code></div>
									</td>
									<td>
										<div style="margin-bottom:4px;">' . $badge . '</div>
										<div class="small text-muted">' . htmlspecialchars($sel['status_text'], ENT_QUOTES, 'UTF-8') . '</div>
										<div class="small" style="color:#64748b;margin-top:2px;">' . htmlspecialchars($sel['description'], ENT_QUOTES, 'UTF-8') . '</div>
									</td>
									<td>' . $blockSelect . '</td>
									<td>' . $ucDropdown . '</td>
								</tr>';
		}

		$defMode = ($prefs['default_mode'] ?? 'popover');
		$autoInj = !empty($prefs['auto_navbar_inject']);
		$repToggle = !empty($prefs['replace_theme_toggle']);
		$targetSel = htmlspecialchars((string) ($prefs['navbar_target_selector'] ?? ''), ENT_QUOTES, 'UTF-8');

		$html .= '
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="panel panel-default">
				<div class="panel-heading">
					<h4 style="margin:0;"><i class="fa fa-cog"></i> Global Display &amp; Navbar Injection Settings</h4>
				</div>
				<div class="panel-body">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label><strong>Default Shortcode Mode (<code>{E107_OPTIONS}</code>)</strong></label>
								<select name="default_mode" class="form-control">
									<option value="popover"' . ($defMode === 'popover' ? ' selected="selected"' : '') . '>Navbar Popover Dropdown (Compact Trigger Button + Floating Panel)</option>
									<option value="inline"' . ($defMode === 'inline' ? ' selected="selected"' : '') . '>Inline Sidebar Card (Expanded Controls)</option>
								</select>
								<span class="help-block small">You can also override per placement with <code>{E107_OPTIONS: mode=popover}</code> or <code>{E107_OPTIONS: mode=inline}</code>, or assign <code>e107_options_menu</code> in Menu Manager.</span>
							</div>
							<div class="form-group">
								<label>
									<input type="checkbox" name="auto_navbar_inject" value="1"' . ($autoInj ? ' checked="checked"' : '') . ' />
									<strong>Automatically inject Navbar Popover button into the active theme header</strong>
								</label>
								<span class="help-block small">Places the Display Options trigger button automatically beside <code>#theme-toggle</code> or inside the header/navbar of any theme (with a top-right floating pill fallback on legacy themes).</span>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label><strong>Navbar Target CSS Selectors (comma-separated priority list)</strong></label>
								<input type="text" name="navbar_target_selector" value="' . $targetSel . '" class="form-control" />
								<span class="help-block small">Used when auto-injecting the popover button if <code>{E107_OPTIONS}</code> is not manually placed in the theme layout.</span>
							</div>
							<div class="form-group">
								<label>
									<input type="checkbox" name="replace_theme_toggle" value="1"' . ($repToggle ? ' checked="checked"' : '') . ' />
									<strong>Hide theme\'s standalone <code>#theme-toggle</code> button when Navbar Popover is injected</strong>
								</label>
								<span class="help-block small">Since the popover includes Light / Dark / Auto mode at the top, check this if you want a single unified button instead of both buttons side by side.</span>
							</div>
						</div>
					</div>
					<div style="margin-top:10px;">
						<button type="submit" name="save_selectors" value="1" class="btn btn-primary"><i class="fa fa-check"></i> Save Selector &amp; Display Settings</button>
					</div>
				</div>
			</div>
		</form>';

		return $html;
	}

	/**
	 * Tab 2: Theme & Color Mode Settings
	 */
	public function ThemesPage()
	{
		$prefs = e107_options_manager::getPrefs();
		$installed = e107_options_manager::getInstalledThemes();
		$allowedList = e107_options_manager::parseCsvList($prefs['allowed_themes']);
		$colormodeList = e107_options_manager::parseCsvList($prefs['colormode_themes']);
		$forceAllColor = !empty($prefs['colormode_force_all']);
		$defaultSiteTheme = e107::getPref('sitetheme', 'moderndark');

		// Filter to v2 themes first, then v1 themes
		$v2Themes = array();
		$v1Themes = array();
		foreach ($installed as $folder => $info)
		{
			if ($folder === '_blank')
			{
				continue;
			}
			if ($info['is_v2'])
			{
				$v2Themes[$folder] = $info;
			}
			else
			{
				$v1Themes[$folder] = $info;
			}
		}

		$html = '
		<form method="post" action="' . e_SELF . '?mode=main&amp;action=themes">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4 style="margin:0;"><i class="fa fa-paint-brush"></i> Allowed Visitor Themes &amp; Stylesheet Variants</h4>
				</div>
				<div class="panel-body">
					<p class="text-muted">
						Select which installed themes appear in the visitor <strong>Site Theme</strong> dropdown. When a visitor chooses a theme that defines multiple
						<code>&lt;stylesheets&gt;</code> in <code>theme.xml</code> (such as <code>i3theme</code>\'s 5 color schemes or <code>bootstrap5</code>\'s Bootswatch palettes),
						a <strong>Color Scheme</strong> pill selector automatically appears beneath the theme dropdown.
					</p>
					<div class="checkbox" style="margin-bottom:15px;padding:10px 14px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);border-radius:6px;">
						<label>
							<input type="checkbox" name="colormode_force_all" value="1"' . ($forceAllColor ? ' checked="checked"' : '') . ' />
							<strong>Enable Universal Light/Dark Mode fallback across all themes</strong> (keeps the Light/Dark/Auto selector active even when switching to classic themes)
						</label>
					</div>
					<h5><strong>e107 v2.x Compatible Themes (' . count($v2Themes) . ' detected)</strong></h5>
					<div class="table-responsive">
						<table class="table table-striped table-bordered">
							<thead>
								<tr>
									<th style="width:90px;text-align:center;">Allow in Selector</th>
									<th>Theme Name &amp; Folder</th>
									<th style="width:120px;">Version / Spec</th>
									<th>Detected <code>theme.xml</code> Color Schemes (Variants)</th>
									<th style="width:160px;text-align:center;">Native Light/Dark Support</th>
								</tr>
							</thead>
							<tbody>';

		foreach ($v2Themes as $folder => $t)
		{
			$isAllowed = empty($allowedList) || in_array($folder, $allowedList, true) || ($folder === $defaultSiteTheme);
			$isCm = (empty($colormodeList) && !empty($t['native_dark'])) || in_array($folder, $colormodeList, true);
			$defBadge = ($folder === $defaultSiteTheme) ? ' <span class="label label-primary">Site Default</span>' : '';

			$variantBadges = '';
			if (count($t['variants']) > 1)
			{
				$names = array();
				foreach ($t['variants'] as $v)
				{
					$names[] = '<span class="label label-info" style="display:inline-block;margin:2px;">' . htmlspecialchars($v['label'], ENT_QUOTES, 'UTF-8') . '</span>';
				}
				$variantBadges = '<strong>' . count($t['variants']) . ' variants:</strong> ' . implode(' ', $names);
			}
			else
			{
				$variantBadges = '<span class="text-muted">Single default stylesheet (<code>style.css</code>)</span>';
			}

			$html .= '
								<tr>
									<td style="text-align:center;">
										<input type="checkbox" name="allowed_themes[]" value="' . htmlspecialchars($folder, ENT_QUOTES, 'UTF-8') . '"' . ($isAllowed ? ' checked="checked"' : '') . ' />
									</td>
									<td>
										<strong>' . htmlspecialchars($t['display_name'], ENT_QUOTES, 'UTF-8') . '</strong>' . $defBadge . '
										<div class="small text-muted"><code>e107_themes/' . htmlspecialchars($folder, ENT_QUOTES, 'UTF-8') . '/</code></div>
									</td>
									<td>
										<span class="label label-success">v' . htmlspecialchars($t['version'], ENT_QUOTES, 'UTF-8') . ' (e107 v' . htmlspecialchars($t['compatibility'], ENT_QUOTES, 'UTF-8') . ')</span>
									</td>
									<td>' . $variantBadges . '</td>
									<td style="text-align:center;">
										<input type="checkbox" name="colormode_themes[]" value="' . htmlspecialchars($folder, ENT_QUOTES, 'UTF-8') . '"' . ($isCm ? ' checked="checked"' : '') . ' />
									</td>
								</tr>';
		}

		$html .= '
							</tbody>
						</table>
					</div>
					<div style="margin-top:12px;">
						<button type="submit" name="save_themes" value="1" class="btn btn-primary"><i class="fa fa-check"></i> Save Theme &amp; Color Mode Settings</button>
					</div>
				</div>
			</div>
		</form>';

		return $html;
	}

	/**
	 * Tab 3: Help & Companion Tools
	 */
	public function HelpPage()
	{
		$langs = e107_options_manager::getInstalledLanguages();
		$allowed = e107_options_manager::getAllowedThemes();

		$html = '
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 style="margin:0;"><i class="fa fa-book"></i> Quick Placement Guide — Navbar Popover &amp; Sidebar Menu Card</h4>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6">
						<h5><strong>1. Navbar Popover Mode (Dropdown)</strong></h5>
						<ul>
							<li><strong>Automatic Header Injection:</strong> Enabled by default! Appears automatically in the header/navbar of the active theme without editing theme files.</li>
							<li><strong>Manual Shortcode in Theme Layouts:</strong> Place <code>{E107_OPTIONS}</code> or <code>{E107_OPTIONS: mode=popover}</code> inside any <code>theme.php</code> or <code>theme.html</code> navbar container.</li>
						</ul>
					</div>
					<div class="col-md-6">
						<h5><strong>2. Sidebar Menu Card Mode (Inline)</strong></h5>
						<ul>
							<li><strong>e107 Menu Manager:</strong> Open <a href="' . e_ADMIN_ABS . 'menus.php"><strong>Admin &rarr; Menu Manager</strong></a> and assign <code>e107_options_menu</code> to any sidebar area (e.g. Area 1 or Area 2).</li>
							<li><strong>Manual Shortcode:</strong> Place <code>{E107_OPTIONS: mode=inline}</code> in any layout or custom page.</li>
							<li><strong>Real-Time Sync:</strong> If both the Navbar Popover and Sidebar Menu Card are on the page, toggling a setting in either widget updates both instantaneously!</li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 style="margin:0;"><i class="fa fa-wrench"></i> Companion e107 Admin Tools &amp; Official Resources</h4>
			</div>
			<div class="panel-body">
				<table class="table table-bordered table-striped" style="margin-bottom:0;">
					<thead>
						<tr>
							<th style="width:240px;">Tool / Resource</th>
							<th>Purpose &amp; How It Connects with <code>e107-options</code></th>
							<th style="width:200px;">Action Link</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong>e107 Language Manager</strong><br /><span class="small text-muted">Currently installed: ' . count($langs) . ' (' . htmlspecialchars(implode(', ', $langs), ENT_QUOTES, 'UTF-8') . ')</span></td>
							<td>Manage site languages and enable multi-language tables. As soon as a 2nd language pack is installed in <code>e107_languages/</code>, the <strong>Language Selector</strong> in <code>e107-options</code> activates automatically!</td>
							<td><a href="' . e_ADMIN_ABS . 'language.php" class="btn btn-sm btn-default"><i class="fa fa-globe"></i> Open Language Manager</a></td>
						</tr>
						<tr>
							<td><strong>Official e107 Language Packs</strong></td>
							<td>Download official e107 v2.3.x translation packs (Spanish, French, German, etc.) to test or enable multi-language switching.</td>
							<td><a href="https://github.com/e107translations" target="_blank" rel="noopener" class="btn btn-sm btn-info"><i class="fa fa-external-link"></i> e107 Translations Repo</a></td>
						</tr>
						<tr>
							<td><strong>e107 Theme Manager</strong><br /><span class="small text-muted">Currently allowed: ' . count($allowed) . ' v2 themes</span></td>
							<td>Set the default site theme, configure theme layouts, or inspect <code>theme.xml</code> <code>&lt;stylesheets&gt;</code> variants.</td>
							<td><a href="' . e_ADMIN_ABS . 'theme.php" class="btn btn-sm btn-default"><i class="fa fa-picture-o"></i> Open Theme Manager</a></td>
						</tr>
						<tr>
							<td><strong>e107 Menu Manager</strong></td>
							<td>Place or reorder <code>e107_options_menu</code> in Sidebar Menu Area 1, 2, or 3, and deactivate legacy <code>usertheme_menu</code> if desired.</td>
							<td><a href="' . e_ADMIN_ABS . 'menus.php" class="btn btn-sm btn-default"><i class="fa fa-th-list"></i> Open Menu Manager</a></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>';

		return $html;
	}
}

class e107_options_form_ui extends e_admin_form_ui
{
}

new e107_options_adminArea();

require_once(e_ADMIN . 'auth.php');
e107::getAdminUI()->runPage();
require_once(e_ADMIN . 'footer.php');
