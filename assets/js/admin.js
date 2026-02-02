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
				class: 'lb-notification ' + type,
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
	window.lbNotify = notify;

	/**
	 * AJAX Helper
	 */
	function lbAjax(action, data, callback) {
		$.ajax({
			url: lbAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: action,
				nonce: lbAdmin.nonce,
				...data
			},
			success: function(response) {
				if (callback) {
					callback(response);
				}
			},
			error: function() {
				notify.error(lbAdmin.strings.saveError);
			}
		});
	}

	window.lbAjax = lbAjax;

	/**
	 * Bestätigungs-Dialoge
	 */
	$(document).on('click', '[data-confirm]', function(e) {
		const message = $(this).data('confirm') || lbAdmin.strings.confirmDelete;
		if (!confirm(message)) {
			e.preventDefault();
			return false;
		}
	});

	/**
	 * Auto-Save für Forms
	 */
	$('.lb-auto-save').on('change', 'input, select, textarea', function() {
		const $form = $(this).closest('form');
		const data = $form.serialize();

		lbAjax('lb_save_settings', { settings: data }, function(response) {
			if (response.success) {
				notify.success(lbAdmin.strings.saveSuccess);
			}
		});
	});

})(jQuery);
