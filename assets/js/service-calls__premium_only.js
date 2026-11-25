/**
 * Kellnerruf für Gäste am Tisch (F48, Pro)
 *
 * Bewusst ohne jQuery: läuft auf beliebigen Theme-Seiten und soll das
 * Laden nicht verzögern. Nach einem Ruf bleiben die Knöpfe eine Minute
 * gesperrt, damit niemand aus Ungeduld mehrfach tippt.
 */
(function () {
	'use strict';

	if (typeof lbiteServiceCalls === 'undefined') {
		return;
	}

	var LOCK_MS = 60000;

	function init() {
		var bar = document.querySelector('.lbite-service-call');
		if (!bar) {
			return;
		}

		var status  = bar.querySelector('.lbite-service-call__status');
		var buttons = bar.querySelectorAll('[data-lbite-call]');

		function setBusy(busy) {
			buttons.forEach(function (b) { b.disabled = busy; });
		}

		function show(message, ok) {
			status.textContent = message;
			bar.classList.toggle('is-confirmed', !!ok);
			bar.classList.toggle('is-error', !ok);
		}

		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				setBusy(true);

				var body = new URLSearchParams();
				body.append('action', 'lbite_service_call');
				body.append('nonce', lbiteServiceCalls.nonce);
				body.append('type', button.getAttribute('data-lbite-call'));

				fetch(lbiteServiceCalls.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: body
				})
					.then(function (r) { return r.json(); })
					.then(function (res) {
						if (res && res.success) {
							show(res.data && res.data.status === 'already' ? lbiteServiceCalls.strings.already : lbiteServiceCalls.strings.sent, true);
							setTimeout(function () { setBusy(false); }, LOCK_MS);
						} else {
							show(lbiteServiceCalls.strings.error, false);
							setBusy(false);
						}
					})
					.catch(function () {
						show(lbiteServiceCalls.strings.error, false);
						setBusy(false);
					});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
