/**
 * Optionsgruppen in der Oberfläche (Produktseite, Menü-Popup, Kasse)
 *
 * Spiegelt die Regeln von LBite_Option_Groups::evaluate() nur für die
 * Bedienung: Maximum sperrt weitere Auswahl, die günstigsten N gewählten
 * Optionen einer Gruppe sind gratis. Massgebend bleibt immer der Server.
 *
 * Datenformat je Gruppe (vom Server):
 * { id, label, type: 'single'|'multiple', min, max, free, hint, error_min,
 *   error_max, options: [ { id, name, price } ] }
 *
 * Bewusst ohne jQuery und ohne Abhängigkeiten, damit dieselbe Datei auf der
 * Produktseite, in der Menü-Ansicht und an der Kasse läuft.
 */
(function (window, document) {
	'use strict';

	function el(tag, cls, text) {
		var node = document.createElement(tag);
		if (cls) { node.className = cls; }
		if (text !== undefined && text !== null) { node.textContent = text; }
		return node;
	}

	function inputsOf(groupEl) {
		return Array.prototype.slice.call(groupEl.querySelectorAll('input[data-option-id], input[name^="lbite_options"]'))
			.filter(function (i) { return i.value !== ''; });
	}

	/** Maximum durchsetzen: bei erreichtem Maximum weitere Checkboxen sperren. */
	function enforceMax(groupEl) {
		var max = parseInt(groupEl.getAttribute('data-max') || '0', 10);
		var boxes = inputsOf(groupEl).filter(function (i) { return i.type === 'checkbox'; });
		if (!max || !boxes.length) { return; }
		var count = boxes.filter(function (i) { return i.checked; }).length;
		boxes.forEach(function (i) {
			i.disabled = !i.checked && count >= max;
			var label = i.closest('label');
			if (label) { label.classList.toggle('is-disabled', i.disabled); }
		});
	}

	/** Gruppen-Elemente (fieldset/div) mit Regeln versehen. */
	function enhance(root) {
		Array.prototype.slice.call(root.querySelectorAll('[data-group]')).forEach(function (groupEl) {
			groupEl.addEventListener('change', function () {
				enforceMax(groupEl);
				var err = root.querySelector('.lbite-option-groups__error');
				if (err) { err.hidden = true; }
			});
			enforceMax(groupEl);
		});
	}

	/**
	 * Gruppen als DOM zeichnen (Popups).
	 *
	 * @param {Array}  groups   Gruppen vom Server.
	 * @param {Object} settings { prefix: Klassenpräfix, formatPrice: fn(number), noneLabel: string }
	 * @return {HTMLElement}
	 */
	function render(groups, settings) {
		settings = settings || {};
		var prefix = settings.prefix || 'lbite-option-groups';
		var formatPrice = settings.formatPrice || function (n) { return n.toFixed(2); };
		// Optionale Klassen, damit bestehende Stile (z. B. der Kasse) greifen.
		var cls = {
			group: settings.groupClass || prefix + '__group',
			label: settings.labelClass || prefix + '__label',
			option: settings.optionClass || prefix + '__option',
			name: settings.nameClass || prefix + '__name',
			price: settings.priceClass || prefix + '__price'
		};
		var wrap = el('div', prefix);
		wrap.setAttribute('data-lbite-option-groups', '');

		(groups || []).forEach(function (g, gi) {
			var single = g.type === 'single';
			var groupEl = el('div', cls.group);
			groupEl.setAttribute('data-group', g.id);
			groupEl.setAttribute('data-min', g.min || 0);
			groupEl.setAttribute('data-max', single ? 1 : (g.max || 0));
			groupEl.setAttribute('data-free', g.free || 0);
			groupEl.setAttribute('data-error-min', g.error_min || '');
			groupEl.setAttribute('data-error-max', g.error_max || '');

			var head = el('div', cls.label, g.label);
			if (g.hint) {
				head.appendChild(el('span', prefix + '__hint', ' ' + g.hint));
			}
			groupEl.appendChild(head);

			var name = 'lbite_og_' + gi + '_' + String(g.id).replace(/[^a-z0-9_]/gi, '');
			if (single && !(g.min > 0)) {
				var noneLabel = el('label', cls.option);
				var none = el('input');
				none.type = 'radio';
				none.name = name;
				none.value = '';
				none.checked = true;
				noneLabel.appendChild(none);
				noneLabel.appendChild(el('span', '', settings.noneLabel || '—'));
				groupEl.appendChild(noneLabel);
			}

			(g.options || []).forEach(function (o) {
				var label = el('label', cls.option);
				var input = el('input');
				input.type = single ? 'radio' : 'checkbox';
				input.name = name;
				input.value = String(o.id);
				input.setAttribute('data-option-id', o.id);
				input.setAttribute('data-price', o.price || 0);
				input.setAttribute('data-name', o.name);
				label.appendChild(input);
				label.appendChild(el('span', cls.name, o.name));
				if (o.price > 0) {
					label.appendChild(el('em', cls.price, '+' + formatPrice(parseFloat(o.price))));
				}
				groupEl.appendChild(label);
			});

			wrap.appendChild(groupEl);
		});

		enhance(wrap);
		return wrap;
	}

	/**
	 * Auswahl lesen, prüfen und Aufpreis berechnen.
	 *
	 * @param {HTMLElement} root
	 * @return {{valid:boolean,message:string,ids:number[],names:string[],extra:number}}
	 */
	function collect(root) {
		var result = { valid: true, message: '', ids: [], names: [], extra: 0 };

		Array.prototype.slice.call(root.querySelectorAll('[data-group]')).forEach(function (groupEl) {
			var chosen = inputsOf(groupEl).filter(function (i) { return i.checked; });
			var min = parseInt(groupEl.getAttribute('data-min') || '0', 10);
			var max = parseInt(groupEl.getAttribute('data-max') || '0', 10);
			var free = parseInt(groupEl.getAttribute('data-free') || '0', 10);

			if (result.valid && chosen.length < min) {
				result.valid = false;
				result.message = groupEl.getAttribute('data-error-min') || '';
			}
			if (result.valid && max > 0 && chosen.length > max) {
				result.valid = false;
				result.message = groupEl.getAttribute('data-error-max') || '';
			}

			var priced = chosen.map(function (i) {
				return { input: i, price: Math.max(0, parseFloat(i.getAttribute('data-price') || '0')) };
			}).sort(function (a, b) { return a.price - b.price; });

			priced.forEach(function (p, index) {
				var id = parseInt(p.input.getAttribute('data-option-id') || p.input.value, 10);
				if (id) { result.ids.push(id); }
				result.names.push(p.input.getAttribute('data-name') || (p.input.closest('label') ? p.input.closest('label').textContent.trim() : ''));
				if (index >= free) { result.extra += p.price; }
			});
		});

		result.extra = Math.round(result.extra * 100) / 100;
		return result;
	}

	window.LBiteOptionGroups = { render: render, enhance: enhance, collect: collect };

	// Produktseite: serverseitig gerenderte Gruppen erweitern und das Absenden
	// bei verletzten Regeln abfangen (der Server prüft trotzdem).
	function initProductPage() {
		var root = document.querySelector('.lbite-product-options[data-lbite-option-groups]');
		if (!root) { return; }
		enhance(root);
		var form = root.closest('form');
		if (!form) { return; }
		form.addEventListener('submit', function (e) {
			var r = collect(root);
			if (!r.valid) {
				e.preventDefault();
				var err = root.querySelector('.lbite-option-groups__error');
				if (err) {
					err.textContent = r.message;
					err.hidden = false;
					err.scrollIntoView({ block: 'center', behavior: 'smooth' });
				}
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initProductPage);
	} else {
		initProductPage();
	}
})(window, document);
