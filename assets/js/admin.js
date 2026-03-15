/**
 * Admin-JavaScript für Libre Bite
 */

(function($) {
	'use strict';

	/**
	 * Notification Helper
	 */
	const notify = {
		show: function(message, type = 'success') {
			const $notification = $('<div>', {
				class: 'lbite-notification ' + type,
				text: message
			});

			$('body').append($notification);

			setTimeout(function() {
				$notification.fadeOut(function() {
					$(this).remove();
				});
			}, 3000);
		},

		success: function(message) {
			this.show(message, 'success');
		},

		error: function(message) {
			this.show(message, 'error');
		}
	};

	// Global verfügbar machen
	window.lbiteNotify = notify;

	/**
	 * AJAX Helper
	 */
	function lbiteAjax(action, data, callback) {
		$.ajax({
			url: lbiteAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: action,
				nonce: lbiteAdmin.nonce,
				...data
			},
			success: function(response) {
				if (callback) {
					callback(response);
				}
			},
			error: function() {
				notify.error(lbiteAdmin.strings.saveError);
			}
		});
	}

	window.lbiteAjax = lbiteAjax;

	/**
	 * Bestätigungs-Dialoge
	 */
	$(document).on('click', '[data-confirm]', function(e) {
		const message = $(this).data('confirm') || lbiteAdmin.strings.confirmDelete;
		if (!confirm(message)) {
			e.preventDefault();
			return false;
		}
	});

	/**
	 * Auto-Save für Forms
	 */
	$('.lbite-auto-save').on('change', 'input, select, textarea', function() {
		const $form = $(this).closest('form');
		const data = $form.serialize();

		lbiteAjax('lbite_save_settings', { settings: data }, function(response) {
			if (response.success) {
				notify.success(lbiteAdmin.strings.saveSuccess);
			}
		});
	});

})(jQuery);

/* Hilfe-Button & Support-Panel */
(function() {
	var btn   = document.getElementById('lbite-help-btn');
	var panel = document.getElementById('lbite-help-panel');
	if (!btn || !panel) return;

	function openPanel() {
		panel.classList.add('is-open');
		btn.setAttribute('aria-expanded', 'true');
	}
	function closePanel() {
		panel.classList.remove('is-open');
		btn.setAttribute('aria-expanded', 'false');
	}

	btn.addEventListener('click', function(e) {
		if (e.target.classList.contains('lbite-help-panel-close')) {
			closePanel();
		} else {
			panel.classList.contains('is-open') ? closePanel() : openPanel();
		}
	});

	document.addEventListener('click', function(e) {
		if (!btn.contains(e.target)) closePanel();
	});
})();
