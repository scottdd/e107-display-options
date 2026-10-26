/**
 * e107_options — Frontend Controller (Popover + Inline Menu Sync, Zero-FOUC State & Theme/Variant Switching)
 */
(function () {
	'use strict';

	function getCfg() { return window.E107OptionsConfig || {}; }
	var root = document.documentElement;

	function resolveColorMode(mode) {
		if (mode === 'auto') {
			return (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
		}
		return (mode === 'light') ? 'light' : 'dark';
	}

	function setCookie(name, value, days) {
		var expires = '';
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = '; expires=' + date.toUTCString();
		}
		document.cookie = name + '=' + encodeURIComponent(value || '') + expires + '; path=/; SameSite=Lax';
	}

	function applyColorMode(mode) {
		var resolved = resolveColorMode(mode);
		try {
			localStorage.setItem('e107opt_colormode', mode);
			localStorage.setItem('moderndark-theme', resolved);
		} catch (e) {}

		root.setAttribute('data-e107-colormode', mode);
		root.setAttribute('data-e107-resolved-theme', resolved);
		root.setAttribute('data-bs-theme', resolved);

		// Sync ModernDark #theme-toggle icon if present
		var nativeBtn = document.getElementById('theme-toggle');
		if (nativeBtn) {
			var icon = nativeBtn.querySelector('i');
			if (icon) {
				icon.classList.remove('bi-sun-fill', 'bi-moon-stars-fill');
				icon.classList.add(resolved === 'dark' ? 'bi-moon-stars-fill' : 'bi-sun-fill');
			}
		}

		syncAllWidgets();
	}

	function applyFontSize(size) {
		var clean = String(size || '100');
		try {
			localStorage.setItem('e107opt_fontsize', clean);
		} catch (e) {}
		root.setAttribute('data-e107-fontsize', clean);
		root.style.fontSize = (clean === '100') ? '' : (clean + '%');
		syncAllWidgets();
	}

	function applyLayoutWidth(widthMode) {
		var clean = widthMode || 'standard';
		try {
			localStorage.setItem('e107opt_width', clean);
		} catch (e) {}
		root.setAttribute('data-e107-width', clean);
		syncAllWidgets();
	}

	function navigateWithParams(params) {
		var url = new URL(window.location.href);
		url.searchParams.delete('e107opt_reset');
		Object.keys(params).forEach(function (k) {
			if (params[k] === null) {
				url.searchParams.delete(k);
			} else {
				url.searchParams.set(k, params[k]);
			}
		});
		window.location.href = url.toString();
	}

	function syncAllWidgets() {
		var cm = root.getAttribute('data-e107-colormode') || 'dark';
		var fs = root.getAttribute('data-e107-fontsize') || '100';
		var lw = root.getAttribute('data-e107-width') || 'standard';

		document.querySelectorAll('[data-e107opt-colormode]').forEach(function (btn) {
			btn.classList.toggle('is-active', btn.getAttribute('data-e107opt-colormode') === cm);
		});
		document.querySelectorAll('[data-e107opt-badge="colormode"]').forEach(function (b) {
			b.textContent = cm.charAt(0).toUpperCase() + cm.slice(1);
		});

		document.querySelectorAll('[data-e107opt-fontsize]').forEach(function (btn) {
			btn.classList.toggle('is-active', btn.getAttribute('data-e107opt-fontsize') === fs);
		});
		document.querySelectorAll('[data-e107opt-badge="fontsize"]').forEach(function (b) {
			b.textContent = fs + '%';
		});

		document.querySelectorAll('[data-e107opt-width]').forEach(function (btn) {
			btn.classList.toggle('is-active', btn.getAttribute('data-e107opt-width') === lw);
		});
		var lwLabels = { standard: 'Standard', wide: 'Wide', reader: 'Reader Focus' };
		document.querySelectorAll('[data-e107opt-badge="layoutwidth"]').forEach(function (b) {
			b.textContent = lwLabels[lw] || 'Standard';
		});
	}

	function autoInjectNavbarPopover() {
		if (!getCfg().autoNavbarInject || !getCfg().popoverHtml) {
			return;
		}
		if (document.querySelector('[data-e107opt-widget="popover"]')) {
			return;
		}

		var temp = document.createElement('div');
		temp.innerHTML = getCfg().popoverHtml.trim();
		var popoverEl = temp.firstElementChild;
		if (!popoverEl) {
			return;
		}

		var nativeToggle = document.getElementById('theme-toggle');
		if (nativeToggle && nativeToggle.parentNode) {
			nativeToggle.parentNode.insertBefore(popoverEl, nativeToggle.nextSibling);
			if (getCfg().replaceThemeToggle) {
				nativeToggle.style.display = 'none';
			}
			return;
		}

		var selectors = (getCfg().navbarTargetSelector || '').split(',');
		for (var i = 0; i < selectors.length; i++) {
			var sel = selectors[i].trim();
			if (!sel) continue;
			var target = document.querySelector(sel);
			if (target) {
				target.appendChild(popoverEl);
				return;
			}
		}

		// Fallback: floating top-right trigger on themes without a standard navbar container
		popoverEl.classList.add('e107opt-floating-fallback');
		document.body.appendChild(popoverEl);
	}

	function bindEvents() {
		document.addEventListener('click', function (e) {
			var toggleBtn = e.target.closest('[data-e107opt-toggle]');
			if (toggleBtn) {
				e.preventDefault();
				var wrap = toggleBtn.closest('[data-e107opt-widget="popover"]');
				var panel = wrap ? wrap.querySelector('[data-e107opt-panel]') : null;
				if (panel) {
					var isHidden = panel.hasAttribute('hidden');
					if (isHidden) {
						panel.removeAttribute('hidden');
						toggleBtn.setAttribute('aria-expanded', 'true');
					} else {
						panel.setAttribute('hidden', '');
						toggleBtn.setAttribute('aria-expanded', 'false');
					}
				}
				return;
			}

			var closeBtn = e.target.closest('[data-e107opt-close]');
			if (closeBtn) {
				e.preventDefault();
				var pWrap = closeBtn.closest('[data-e107opt-widget="popover"]');
				if (pWrap) {
					var pPanel = pWrap.querySelector('[data-e107opt-panel]');
					var pBtn = pWrap.querySelector('[data-e107opt-toggle]');
					if (pPanel) pPanel.setAttribute('hidden', '');
					if (pBtn) pBtn.setAttribute('aria-expanded', 'false');
				}
				return;
			}

			var cmBtn = e.target.closest('[data-e107opt-colormode]');
			if (cmBtn) {
				e.preventDefault();
				applyColorMode(cmBtn.getAttribute('data-e107opt-colormode'));
				return;
			}

			var fsBtn = e.target.closest('[data-e107opt-fontsize]');
			if (fsBtn) {
				e.preventDefault();
				applyFontSize(fsBtn.getAttribute('data-e107opt-fontsize'));
				return;
			}

			var lwBtn = e.target.closest('[data-e107opt-width]');
			if (lwBtn) {
				e.preventDefault();
				applyLayoutWidth(lwBtn.getAttribute('data-e107opt-width'));
				return;
			}

			var varBtn = e.target.closest('[data-e107opt-variant]');
			if (varBtn) {
				e.preventDefault();
				var chosenVar = varBtn.getAttribute('data-e107opt-variant');
				setCookie('e107opt_variant', chosenVar, 90);
				navigateWithParams({ e107opt_variant: chosenVar });
				return;
			}

			var resetBtn = e.target.closest('[data-e107opt-reset]');
			if (resetBtn) {
				e.preventDefault();
				try {
					localStorage.removeItem('e107opt_colormode');
					localStorage.removeItem('e107opt_fontsize');
					localStorage.removeItem('e107opt_width');
					localStorage.setItem('moderndark-theme', 'dark');
				} catch (err) {}
				setCookie('e107opt_theme', '', -1);
				setCookie('e107opt_variant', '', -1);
				navigateWithParams({ e107opt_theme: null, e107opt_variant: null, e107opt_reset: '1' });
				return;
			}

			// Close open popover when clicking outside
			if (!e.target.closest('[data-e107opt-widget="popover"]')) {
				document.querySelectorAll('[data-e107opt-widget="popover"]').forEach(function (w) {
					var p = w.querySelector('[data-e107opt-panel]');
					var b = w.querySelector('[data-e107opt-toggle]');
					if (p && !p.hasAttribute('hidden')) {
						p.setAttribute('hidden', '');
						if (b) b.setAttribute('aria-expanded', 'false');
					}
				});
			}
		});

		document.addEventListener('change', function (e) {
			var themeSel = e.target.closest('[data-e107opt-theme-select]');
			if (themeSel) {
				var chosenTheme = themeSel.value;
				setCookie('e107opt_theme', chosenTheme, 90);
				setCookie('e107opt_variant', '', -1);
				navigateWithParams({ e107opt_theme: chosenTheme, e107opt_variant: null });
				return;
			}

			var langSel = e.target.closest('[data-e107opt-lang-select]');
			if (langSel) {
				var chosenLang = langSel.value;
				navigateWithParams({ e107opt_lang: chosenLang, elan: chosenLang });
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		autoInjectNavbarPopover();
		var urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get('e107opt_colormode')) {
			applyColorMode(urlParams.get('e107opt_colormode'));
		}
		if (urlParams.get('e107opt_fontsize')) {
			applyFontSize(urlParams.get('e107opt_fontsize'));
		}
		if (urlParams.get('e107opt_width')) {
			applyLayoutWidth(urlParams.get('e107opt_width'));
		}
		syncAllWidgets();
		bindEvents();
		if (urlParams.get('e107opt_open') === '1') {
			var wrap = document.querySelector('[data-e107opt-widget="popover"]');
			if (wrap) {
				var panel = wrap.querySelector('[data-e107opt-panel]');
				var btn = wrap.querySelector('[data-e107opt-toggle]');
				if (panel) panel.removeAttribute('hidden');
				if (btn) btn.setAttribute('aria-expanded', 'true');
			}
		}
	});
})();
