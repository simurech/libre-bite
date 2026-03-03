/**
 * Onboarding-Seite JavaScript
 */

(function($) {
	'use strict';

	/**
	 * Aktuellen Feature-Zustand aus den Toggle-Buttons lesen
	 */
	function collectFeatures() {
		var features = {};
		$('.lbite-onboarding-toggle').each(function() {
			var key = $(this).data('feature');
			features[key] = $(this).hasClass('is-active');
		});
		return features;
	}

	/**
	 * Toggle-Klick
	 */
	$(document).on('click', '.lbite-onboarding-toggle:not(:disabled)', function() {
		var $btn = $(this);
		var isActive = $btn.hasClass('is-active');

		$btn.toggleClass('is-active', !isActive);
		$btn.attr('aria-pressed', !isActive ? 'true' : 'false');
		$btn.find('.lbite-onboarding-toggle-label').text(!isActive ? 'AN' : 'AUS');
	});

	/**
	 * Onboarding abschliessen
	 */
	$('#lbite-onboarding-complete').on('click', function() {
		var $btn = $(this);

		$btn.addClass('is-loading').prop('disabled', true).text(lbiteOnboarding.strings.saving);

		$.ajax({
			url: lbiteOnboarding.ajaxUrl,
			type: 'POST',
			data: {
				action: 'lbite_complete_onboarding',
				nonce: lbiteOnboarding.nonce,
				features: JSON.stringify(collectFeatures())
			},
			success: function(response) {
				if (response.success) {
					$btn.text(lbiteOnboarding.strings.success);
					window.location.href = response.data.redirect;
				} else {
					$btn.removeClass('is-loading').prop('disabled', false).text('Einrichtung abschliessen');
					alert(lbiteOnboarding.strings.error);
				}
			},
			error: function() {
				$btn.removeClass('is-loading').prop('disabled', false).text('Einrichtung abschliessen');
				alert(lbiteOnboarding.strings.error);
			}
		});
	});

})(jQuery);
