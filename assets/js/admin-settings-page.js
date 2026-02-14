jQuery(document).ready(function($) {
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
});
