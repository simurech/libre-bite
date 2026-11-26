/**
 * Optionsgruppen im Produkt-Editor (Pro)
 *
 * - «Add group» blendet die nächste leere Gruppe ein.
 * - Eine Option kann pro Produkt nur in einer Gruppe stehen: In den übrigen
 *   Gruppen wird sie gesperrt, in der Extras-Liste oben steht ein Hinweis,
 *   welche Gruppenregeln gelten.
 */
(function (document) {
	'use strict';

	var wrap = document.getElementById('lbite-option-groups-admin');
	if (!wrap) {
		return;
	}
	var strings = window.lbiteOptionGroupsAdmin || {};

	function groups() {
		return Array.prototype.slice.call(wrap.querySelectorAll('.lbite-og-admin-group'));
	}

	function groupName(groupEl) {
		var input = groupEl.querySelector('.lbite-og-admin-label');
		return (input && input.value.trim()) || strings.unnamed || '';
	}

	function refresh() {
		// Option-ID -> Gruppe, in der sie angehakt ist.
		var owner = {};
		groups().forEach(function (g) {
			g.querySelectorAll('.lbite-og-admin-option:checked').forEach(function (box) {
				owner[box.getAttribute('data-option-id')] = g;
			});
		});

		groups().forEach(function (g) {
			g.querySelectorAll('.lbite-og-admin-option').forEach(function (box) {
				var id = box.getAttribute('data-option-id');
				var taken = owner[id] && owner[id] !== g;
				box.disabled = !!taken;
				if (box.parentNode) {
					box.parentNode.style.opacity = taken ? '0.5' : '';
					box.parentNode.title = taken ? String(strings.inGroup || '').replace('%s', groupName(owner[id])) : '';
				}
			});
		});

		document.querySelectorAll('.lbite-flat-option').forEach(function (label) {
			var hint = label.querySelector('.lbite-flat-option__group');
			var g = owner[label.getAttribute('data-option-id')];
			if (!hint) {
				return;
			}
			if (g) {
				hint.textContent = ' — ' + String(strings.inGroup || '').replace('%s', groupName(g));
				hint.style.display = 'inline';
			} else {
				hint.textContent = '';
				hint.style.display = 'none';
			}
		});
	}

	wrap.addEventListener('change', refresh);
	wrap.addEventListener('input', function (e) {
		if (e.target.classList.contains('lbite-og-admin-label')) {
			refresh();
		}
	});

	var add = document.getElementById('lbite-og-admin-add');
	if (add) {
		var toggleAdd = function () {
			add.hidden = !wrap.querySelector('.lbite-og-admin-group[hidden]');
		};
		add.addEventListener('click', function () {
			var next = wrap.querySelector('.lbite-og-admin-group[hidden]');
			if (next) {
				next.hidden = false;
				var input = next.querySelector('.lbite-og-admin-label');
				if (input) {
					input.focus();
				}
			}
			toggleAdd();
		});
		toggleAdd();
	}

	refresh();
})(document);
