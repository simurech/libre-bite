jQuery(document).ready(function($) {
	// Welcome-Notice schliessen
	$('#lbite-welcome-notice').on('click', '.lbite-welcome-notice__dismiss', function() {
		var $notice = $('#lbite-welcome-notice');
		$notice.fadeOut(200);
		$.post(ajaxurl, {
			action: 'lbite_dismiss_welcome_notice',
			nonce: lbiteAdminSettings.nonce
		});
	});

	// "Alle auswählen/abwählen" Funktionalität
	$('.lbite-toggle-all-menus').on('change', function() {
		var roleKey = $(this).data('role');
		var isChecked = $(this).prop('checked');
		var roleSection = $('.lbite-role-section[data-role="' + roleKey + '"]');

		// Alle Checkboxen in dieser Rolle an-/abwählen
		roleSection.find('.lbite-menu-items input[type="checkbox"]').prop('checked', isChecked);
	});

	// Status der "Alle auswählen" Checkbox aktualisieren
	$('.lbite-menu-items input[type="checkbox"]').on('change', function() {
		var roleSection = $(this).closest('.lbite-role-section');
		var roleKey = roleSection.data('role');
		var totalCheckboxes = roleSection.find('.lbite-menu-items input[type="checkbox"]').length;
		var checkedCheckboxes = roleSection.find('.lbite-menu-items input[type="checkbox"]:checked').length;

		// "Alle auswählen" Checkbox aktualisieren
		var toggleAll = roleSection.find('.lbite-toggle-all-menus');
		if (checkedCheckboxes === totalCheckboxes) {
			toggleAll.prop('checked', true);
			toggleAll.prop('indeterminate', false);
		} else if (checkedCheckboxes === 0) {
			toggleAll.prop('checked', false);
			toggleAll.prop('indeterminate', false);
		} else {
			toggleAll.prop('indeterminate', true);
		}
	});

	// Initialen Status der "Alle auswählen" Checkboxen setzen
	$('.lbite-role-section').each(function() {
		var roleSection = $(this);
		var totalCheckboxes = roleSection.find('.lbite-menu-items input[type="checkbox"]').length;
		var checkedCheckboxes = roleSection.find('.lbite-menu-items input[type="checkbox"]:checked').length;

		var toggleAll = roleSection.find('.lbite-toggle-all-menus');
		if (checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
			toggleAll.prop('checked', true);
		} else if (checkedCheckboxes > 0) {
			toggleAll.prop('indeterminate', true);
		}
	});

	// Rolle deaktivieren - Namensfeld deaktivieren
	$('.lbite-disable-role-checkbox').on('change', function() {
		var roleKey = $(this).data('role');
		var isDisabled = $(this).prop('checked');
		var nameInput = $('#lbite_role_name_' + roleKey);

		if (isDisabled) {
			nameInput.prop('disabled', true).css('opacity', '0.5');
		} else {
			nameInput.prop('disabled', false).css('opacity', '1');
		}
	});

	// Initial deaktivierte Rollen
	$('.lbite-disable-role-checkbox:checked').each(function() {
		var roleKey = $(this).data('role');
		var nameInput = $('#lbite_role_name_' + roleKey);
		nameInput.css('opacity', '0.5');
	});

	// POS-Produktreihenfolge per Drag & Drop
	var posOrderList = document.getElementById('lbite-pos-product-order');
	if (posOrderList && typeof Sortable !== 'undefined') {
		Sortable.create(posOrderList, {
			handle: '.dashicons-menu',
			animation: 150
		});

		$('#lbite-save-pos-product-order').on('click', function() {
			var $btn = $(this);
			var $status = $('#lbite-pos-product-order-status');

			var order = [];
			$('#lbite-pos-product-order li').each(function() {
				order.push($(this).data('id'));
			});

			$btn.prop('disabled', true);
			$status.text('');

			$.post(ajaxurl, {
				action: 'lbite_save_pos_product_order',
				nonce: lbiteAdminSettings.nonce,
				order: order
			}, function(response) {
				$btn.prop('disabled', false);
				if (response.success) {
					$status.css('color', '#3c763d').text('✓ ' + response.data.message);
					setTimeout(function() { $status.text(''); }, 3000);
				} else {
					$status.css('color', '#a94442').text('✗ ' + (response.data ? response.data.message : 'Error'));
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.css('color', '#a94442').text('✗ Connection error');
			});
		});
	}
});
