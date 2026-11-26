jQuery(document).ready(function($) {
	/**
	 * Tisch-Dropdown mit Belegungsstatus befüllen.
	 *
	 * @param {jQuery} $select    Tisch-Select-Element
	 * @param {Array}  tables     Array von {id, title, occupied}
	 * @param {string} emptyLabel Label für "kein Tisch"
	 */
	function renderTableOptions($select, tables, emptyLabel) {
		$select.empty().append($('<option>').val('').text(emptyLabel));
		tables.forEach(function(table) {
			var label = table.occupied ? '\u{1F534} ' + table.title : '\u{1F7E2} ' + table.title;
			$select.append($('<option>').val(table.id).text(label).attr('data-occupied', table.occupied ? '1' : '0'));
		});
		syncTableGrid();
	}

	/**
	 * Tisch-Button-Grid aus dem (unsichtbaren) Tisch-Select ableiten.
	 *
	 * Das Select bleibt die einzige Datenquelle (server-gerendert und von
	 * renderTableOptions() befüllt) — das Grid ist nur eine antippbare
	 * Projektion davon, damit auf dem Tablet kein natives Dropdown nötig ist.
	 */
	function tableName($opt) {
		return $.trim($opt.text().replace(/^[\u{1F534}\u{1F7E2}]\s*/u, ''));
	}

	function syncTableGrid() {
		var $select = $('#lbite-pos-table');
		var $grid = $('#lbite-pos-table-grid');
		if (!$grid.length) {
			return;
		}
		var currentVal = $select.val();
		$grid.empty();
		$select.find('option').each(function() {
			var $opt = $(this);
			var val = $opt.val();
			var $btn = $('<button type="button" class="lbite-pos-table-btn"></button>')
				.attr('data-value', val);
			if ('' === val) {
				$btn.addClass('lbite-pos-table-btn-none').text($opt.text());
			} else {
				// Status als eigener Punkt statt Emoji im Text: Emojis stellen
				// nicht alle Systeme dar (Kassen-Tablets mit älterem Android).
				var occupied = '1' === $opt.attr('data-occupied');
				$btn.toggleClass('lbite-pos-table-btn-occupied', occupied)
					.append($('<span class="lbite-pos-table-dot" aria-hidden="true"></span>'))
					.append($('<span class="lbite-pos-table-name"></span>').text(tableName($opt)));
			}
			if (val === currentVal) {
				$btn.addClass('active');
			}
			$grid.append($btn);
		});

		// Beschriftung des Knopfs: gewählter Tisch ohne Status-Emoji.
		var $selected = $select.find('option:selected');
		var name = $selected.length && $selected.val() ? tableName($selected) : '';
		var noTable = (lbiteAdmin.strings && lbiteAdmin.strings.noTable) ? lbiteAdmin.strings.noTable : '';
		$('#lbite-pos-table-trigger-label').text(name || noTable);
		$('#lbite-pos-table-trigger').toggleClass('has-table', !!name);
	}

	var $trigger = $('#lbite-pos-table-trigger');
	var $popover = $('#lbite-pos-table-popover');

	function setPopover(open) {
		if (!$popover.length) {
			return;
		}
		$popover.prop('hidden', !open);
		$trigger.attr('aria-expanded', open ? 'true' : 'false');
		if (open) {
			var $active = $popover.find('.lbite-pos-table-btn.active');
			($active.length ? $active : $popover.find('.lbite-pos-table-btn').first()).trigger('focus');
		}
	}

	$trigger.on('click', function(e) {
		e.stopPropagation();
		setPopover($popover.prop('hidden'));
	});

	// Ausserhalb klicken oder Escape schliesst das Auswahlfenster.
	$(document).on('click', function(e) {
		if (!$popover.prop('hidden') && !$(e.target).closest('#lbite-pos-table-popover, #lbite-pos-table-trigger').length) {
			setPopover(false);
		}
	});
	$(document).on('keydown', function(e) {
		if ('Escape' === e.key && !$popover.prop('hidden')) {
			setPopover(false);
			$trigger.trigger('focus');
		}
	});

	$(document).on('click', '.lbite-pos-table-btn', function() {
		$('#lbite-pos-table').val($(this).attr('data-value')).trigger('change');
		setPopover(false);
	});

	$('#lbite-pos-table').on('change', syncTableGrid);
	syncTableGrid();

	// Standort-Auswahl speichern (ohne Seitenneulad)
	$('#lbite-pos-location').on('change', function() {
		var locationId = $(this).val();
		var $tableContainer = $('#lbite-pos-table-selector-container');
		var $tableSelect = $('#lbite-pos-table');
		var noTableLabel = (lbiteAdmin.strings && lbiteAdmin.strings.noTable) ? lbiteAdmin.strings.noTable : 'Kein Tisch';

		// Standort per AJAX speichern
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lbite_save_pos_location',
				nonce: lbiteAdmin.nonce,
				location_id: locationId
			},
			success: function(response) {
				if (response.success) {
					if (locationId && $tableContainer.length) {
						// Tische mit Belegungsstatus laden; Sichtbarkeit überlässt updateTableSelectorVisibility()
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'lbite_get_location_tables',
								nonce: lbiteAdmin.nonce,
								location_id: locationId
							},
							success: function(tableResponse) {
								if (tableResponse.success) {
									renderTableOptions($tableSelect, tableResponse.data.tables, noTableLabel);
								}
								if (typeof POS !== 'undefined' && POS.updateTableSelectorVisibility) {
									POS.updateTableSelectorVisibility();
								}
							}
						});
					} else if ($tableContainer.length) {
						$tableContainer.hide();
						$tableSelect.empty().append($('<option>').val('').text(noTableLabel));
					}

					// No-Location-Overlay aktualisieren
					if (!locationId) {
						$('.lbite-pos-products').addClass('lbite-pos-no-location');
					} else {
						$('.lbite-pos-products').removeClass('lbite-pos-no-location');
					}

					// Produkte nach neuem Standort filtern
					if (typeof POS !== 'undefined' && POS.filterByLocation) {
						POS.filterByLocation(locationId);
					}

					// Standort-Farb-Highlighting aktualisieren
					var $locationSelect = $('#lbite-pos-location');
					var colors = (typeof lbitePos !== 'undefined' && lbitePos.locationColors) ? lbitePos.locationColors : {};
					var color = locationId ? colors[locationId] : null;
					if (color) {
						$locationSelect.css({ 'border-color': color, 'border-width': '2px', 'box-shadow': '0 0 0 1px ' + color });
					} else {
						$locationSelect.css({ 'border-color': '', 'border-width': '', 'box-shadow': '' });
					}
				}
			}
		});
	});

	// Beim Seitenaufruf bestehende Tische mit Status versehen (vorgewählter Standort)
	var $initialTableSelect = $('#lbite-pos-table');
	var $initialLocation = $('#lbite-pos-location');
	if ($initialTableSelect.length && $initialLocation.val()) {
		var initialLocationId = $initialLocation.val();
		var initialNoTableLabel = (lbiteAdmin.strings && lbiteAdmin.strings.noTable) ? lbiteAdmin.strings.noTable : 'Kein Tisch';
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lbite_get_location_tables',
				nonce: lbiteAdmin.nonce,
				location_id: initialLocationId
			},
			success: function(tableResponse) {
				if (tableResponse.success) {
					renderTableOptions($initialTableSelect, tableResponse.data.tables, initialNoTableLabel);
				}
				// Sichtbarkeit nach Order-Type bestimmen (pos.js init() läuft parallel;
				// setTimeout(0) stellt sicher, dass localStorage-Restore bereits abgeschlossen ist)
				setTimeout(function() {
					if (typeof POS !== 'undefined' && POS.updateTableSelectorVisibility) {
						POS.updateTableSelectorVisibility();
					}
				}, 0);
			}
		});
	}
});
