/**
 * POS/Kassensystem JavaScript
 */

(function($) {
	'use strict';

	function escapeHtml(str) {
		if (str === null || str === undefined) {
			return '';
		}
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	const POS = {
		cart: [],
		coupons: [],
		currentCategory: 'all',
		currentProduct: null,
		isProcessingOrder: false,
		productsCache: {},
		productDetailsCache: {},
		isLoadingProducts: false,
		allProducts: [],
		filteredProducts: [],
		allCategories: [],
		dataLoaded: false,
		wakeLock: null,
		fullscreenDesired: false,
		activeTab: null,

		/**
		 * Initialisierung
		 */
		init: function() {
			this.bindEvents();
			this.bindModalEvents();
			this.loadSavedCart();
			this.bindFullscreenEvents();

			// Wake-Lock initialisieren
			const $wakeLock = $('#lbite-pos-wake-lock');
			$wakeLock.on('change', () => {
				if ($wakeLock.is(':checked')) {
					this.requestWakeLock();
				} else {
					this.releaseWakeLock();
				}
			});
			if ($wakeLock.is(':checked')) {
				this.requestWakeLock();
			}

			// Seitenlade-Zeitstempel für Stabilitätsprüfung
			const pageLoadTime = Date.now();

			// Tab-Aktivierung: Wake Lock neu anfordern + Seite nach 8h neu laden
			document.addEventListener('visibilitychange', () => {
				if (document.visibilityState === 'visible') {
					if ($wakeLock.is(':checked')) {
						this.requestWakeLock();
					}
					// Nach 8 Stunden Seite neu laden (Nonce-Ablauf, veraltete Daten)
					if (Date.now() - pageLoadTime > 8 * 60 * 60 * 1000) {
						location.reload();
					}
				}
			});

			// VAT-Typ aus localStorage wiederherstellen (überschreibt PHP-Default nach Reload)
			if ($('input[name="lbite_pos_vat_type"]').length) {
				const savedVatType = localStorage.getItem('lbite_pos_vat_type');
				if (savedVatType) {
					$('input[name="lbite_pos_vat_type"][value="' + savedVatType + '"]').prop('checked', true);
				}
				this.updateVatIndicator();
				this.updateTableSelectorVisibility();
				$(document).on('change', 'input[name="lbite_pos_vat_type"]', () => {
					const type = $('input[name="lbite_pos_vat_type"]:checked').val();
					if (type) {
						localStorage.setItem('lbite_pos_vat_type', type);
					}
					this.updateVatIndicator();
					this.updateTableSelectorVisibility();
				});
			}

			// Initiale Standort-Farbe und Overlay-Status setzen
			const initialLocation = $('#lbite-pos-location').val();
			this.applyLocationColor(initialLocation);
			this.updateNoLocationState(!initialLocation);

			// Eingebettete Daten verwenden (kein HTTP-Request nötig)
			if (lbitePos.preloadData && lbitePos.preloadData.products) {
				this.usePreloadedData(lbitePos.preloadData);
			} else {
				// Fallback auf AJAX
				this.loadProducts();
			}

			// Offene-Tabs-Badge (nur relevant wenn Feature aktiv, Button dann im DOM vorhanden).
			this.refreshTabsBadge();
			$('#lbite-pos-location').on('change', () => {
				this.refreshTabsBadge();
			});
		},

		/**
		 * Standort-Farbe auf POS-Dropdown anwenden
		 */
		applyLocationColor: function(locationId) {
			const $select = $('#lbite-pos-location');
			const colors = (lbitePos.locationColors) || {};
			const color = locationId ? colors[locationId] : null;
			if (color) {
				$select.css({
					'border-color': color,
					'border-width': '2px',
					'box-shadow': '0 0 0 1px ' + color
				});
			} else {
				$select.css({
					'border-color': '',
					'border-width': '',
					'box-shadow': ''
				});
			}
		},

		/**
		 * Produkt-Bereich bei fehlendem Standort blockieren/freigeben
		 */
		updateNoLocationState: function(noLocation) {
			const $products = $('.lbite-pos-products');
			if (noLocation) {
				$products.addClass('lbite-pos-no-location');
			} else {
				$products.removeClass('lbite-pos-no-location');
			}
		},

		updateTableSelectorVisibility: function() {
			const $container = $('#lbite-pos-table-selector-container');
			if ( ! $container.length ) return;
			const isDineIn    = $('input[name="lbite_pos_vat_type"]:checked').val() === 'dine_in';
			const hasLocation = !!$('#lbite-pos-location').val();
			if ( isDineIn && hasLocation ) {
				$container.show();
			} else {
				$container.hide();
				$('#lbite-pos-table').val('').trigger('change');
			}
		},

		/**
		 * VAT-Typ-Indikator im Warenkorb aktualisieren
		 */
		updateVatIndicator: function() {
			const $indicator = $('#lbite-pos-vat-indicator');
			if ($indicator.length === 0) return;
			const $checked = $('input[name="lbite_pos_vat_type"]:checked');
			if ($checked.length === 0) {
				$indicator.hide();
				return;
			}
			const val = $checked.val();
			const label = val === 'dine_in'
				? (lbitePos.strings.dineIn || 'Dine-in')
				: (lbitePos.strings.takeaway || 'Takeaway');
			$indicator.text(label).attr('data-type', val).show();
		},

		/**
		 * Eingebettete Produktdaten verwenden (sofort verfügbar)
		 */
		usePreloadedData: function(data) {
			this.dataLoaded = true;
			this.allProducts = data.products || [];
			this.allCategories = data.categories || [];

			// Details-Cache vorab befüllen
			if (data.details) {
				this.productDetailsCache = data.details;
			}

			// Initiale Standort-Filterung und Darstellung
			const initialLocation = $('#lbite-pos-location').val();
			this.filterByLocation(initialLocation);
		},

		/**
		 * Produkte nach gewähltem Standort filtern und Ansicht aktualisieren.
		 *
		 * Produkte ohne Ausschluss (leeres excluded_location_ids-Array) sind überall verfügbar.
		 */
		filterByLocation: function(locationId) {
			locationId = locationId ? parseInt(locationId, 10) : 0;

			if (!locationId) {
				this.filteredProducts = this.allProducts;
			} else {
				this.filteredProducts = this.allProducts.filter(function(product) {
					if (!product.excluded_location_ids || product.excluded_location_ids.length === 0) {
						return true;
					}
					return product.excluded_location_ids.indexOf(locationId) === -1;
				});
			}

			this.buildCategoryCache();
			this.renderProducts(this.productsCache[this.currentCategory] || []);
		},

		/**
		 * Kategorie-Cache aus den gefilterten Daten aufbauen
		 */
		buildCategoryCache: function() {
			const products = this.filteredProducts;

			this.productsCache = {};
			this.productsCache['all'] = products;

			// Pro Kategorie gruppieren
			products.forEach(product => {
				if (product.categories && product.categories.length > 0) {
					product.categories.forEach(catId => {
						if (!this.productsCache[catId]) {
							this.productsCache[catId] = [];
						}
						this.productsCache[catId].push(product);
					});
				}
			});
		},

		/**
		 * Events binden
		 */
		bindEvents: function() {
			// Kategorie-Wechsel
			$(document).on('click', '.lbite-category-btn', function() {
				$('.lbite-category-btn').removeClass('active');
				$(this).addClass('active');
				POS.currentCategory = $(this).data('category');
				POS.loadProducts();
			});

			// Warenkorb leeren
			$('#lbite-pos-clear').on('click', () => {
				if (confirm(lbitePos.strings.cartClearConfirm)) {
					this.clearCart();
				}
			});

			// Checkout
			$('#lbite-pos-checkout').on('click', () => {
				this.checkout();
			});

			// Produkt entfernen
			$(document).on('click', '.lbite-cart-item-remove', function() {
				const cartIndex = $(this).data('cart-index');
				POS.removeFromCart(cartIndex);
			});

			// Menge ändern
			$(document).on('click', '.lbite-cart-qty-minus', function() {
				const cartIndex = $(this).data('cart-index');
				POS.updateQuantity(cartIndex, -1);
			});

			$(document).on('click', '.lbite-cart-qty-plus', function() {
				const cartIndex = $(this).data('cart-index');
				POS.updateQuantity(cartIndex, 1);
			});

			// Zahlungs-Modal schliessen
			$(document).on('click', '#lbite-payment-modal-cancel, #lbite-payment-modal-overlay', function() {
				POS.closePaymentModal();
			});

			// Gutschein-Popup öffnen/schliessen
			$(document).on('click', '#lbite-pos-coupon-btn', () => {
				this.openCouponPopup();
			});
			$(document).on('click', '#lbite-pos-coupon-popup-close, #lbite-pos-coupon-popup-overlay', () => {
				this.closeCouponPopup();
			});

			// Split-Payment-Panel ein-/ausblenden
			$(document).on('click', '#lbite-split-payment-toggle', function() {
				POS.toggleSplitPanel();
			});

			// Split-Betrag geändert
			$(document).on('input', '.lbite-split-amount', function() {
				POS.updateSplitSummary();
			});

			// Restbetrag in ein Split-Feld eintragen
			$(document).on('click', '.lbite-split-rest', function() {
				const $target = $(`.lbite-split-amount[data-method="${$(this).data('method')}"]`);
				const total = parseFloat($('#lbite-payment-modal').data('total')) || 0;
				let others = 0;
				$('.lbite-split-amount').not($target).each(function() {
					others += parseFloat($(this).val()) || 0;
				});
				const rest = Math.max(0, Math.round((total - others) * 100) / 100);
				$target.val(rest.toFixed(2));
				POS.updateSplitSummary();
			});

			// Zahlung bestätigen: regulärer Bestellfluss ODER Tab-Abschluss.
			$(document).on('click', '#lbite-payment-modal-confirm', function() {
				const mode = $('#lbite-payment-modal').data('mode') || 'order';
				const splitPayments = POS.getSplitPayments();
				const paymentMethod = splitPayments ? 'split' : ($('input[name="lbite-payment-method"]:checked').val() || 'cash');

				if (mode === 'close_tab') {
					const orderId = $('#lbite-payment-modal').data('tab-order-id');
					POS.closePaymentModal();
					POS.closeTab(orderId, paymentMethod, splitPayments);
					return;
				}

				const locationId = $('#lbite-pos-location').val();
				const tableId = $('#lbite-pos-table').val() || 0;
				const customerName = $('#lbite-pos-customer-name').val().trim();
				const vatType = $('input[name="lbite_pos_vat_type"]:checked').val() || '';
				POS.closePaymentModal();
				POS.createOrder(locationId, 'now', '', customerName, paymentMethod, tableId, vatType, splitPayments);
			});

			// Tab eröffnen (statt regulärer Bestellung).
			$(document).on('click', '#lbite-payment-modal-open-tab', function() {
				POS.closePaymentModal();
				POS.openTab();
			});

			// Tabs-Panel öffnen/schliessen.
			$(document).on('click', '#lbite-pos-tabs-btn', () => {
				this.loadOpenTabs();
			});
			$(document).on('click', '#lbite-pos-tabs-panel-close, #lbite-pos-tabs-panel-overlay', () => {
				$('#lbite-pos-tabs-panel').fadeOut(200);
			});

			// Aktiven Tab-Modus verlassen.
			$(document).on('click', '#lbite-pos-active-tab-clear', () => {
				this.clearActiveTab();
			});

			// Tabs-Panel: Aktionen pro Tab (delegiert, da dynamisch gerendert).
			$(document).on('click', '.lbite-tab-add-items', function() {
				const orderId = $(this).data('order-id');
				const tableName = $(this).closest('.lbite-tab-card').data('table-name');
				POS.setActiveTab(orderId, tableName);
				$('#lbite-pos-tabs-panel').fadeOut(200);
			});
			$(document).on('click', '.lbite-tab-pay-close', function() {
				const orderId = $(this).data('order-id');
				POS.startCloseTab(orderId);
			});
			$(document).on('click', '.lbite-tab-cancel', function() {
				const orderId = $(this).data('order-id');
				if (confirm(lbitePos.strings.confirmCancelTab || 'Cancel this tab?')) {
					POS.cancelTab(orderId);
				}
			});
		},

		/**
		 * Lade-Overlay anzeigen
		 */
		showLoading: function(message = 'Laden...') {
			let $overlay = $('#lbite-pos-loading');
			if ($overlay.length === 0) {
				$overlay = $('<div id="lbite-pos-loading"></div>');
				$overlay.append('<div class="lbite-pos-spinner"></div>');
				$overlay.append($('<p id="lbite-pos-loading-text"></p>').text(message));
				$('body').append($overlay);
			} else {
				$('#lbite-pos-loading-text').text(message);
			}
			$overlay.fadeIn(100);
		},

		/**
		 * Lade-Overlay ausblenden
		 */
		hideLoading: function() {
			$('#lbite-pos-loading').fadeOut(100);
		},

		/**
		 * Produkte laden (mit Client-Cache)
		 */
		loadProducts: function() {
			const cacheKey = this.currentCategory;

			// Aus Cache laden wenn vorhanden
			if (this.productsCache[cacheKey]) {
				this.renderProducts(this.productsCache[cacheKey]);
				return;
			}

			// Verhindern von doppelten Anfragen
			if (this.isLoadingProducts) {
				return;
			}
			this.isLoadingProducts = true;

			const $loading = $('<div class="lbite-pos-loading-container"></div>');
			$loading.append('<div class="lbite-pos-inline-spinner"></div>');
			$loading.append($('<p></p>').text(lbitePos.strings.loadingProducts));
			$('#lbite-product-grid').html($loading);

			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_get_products',
					nonce: lbitePos.nonce,
					category_id: this.currentCategory === 'all' ? 0 : this.currentCategory,
					location_id: $('#lbite-pos-location').val() || 0
				},
				success: (response) => {
					if (response.success && response.data.products) {
						// Im Cache speichern
						this.productsCache[cacheKey] = response.data.products;
						this.renderProducts(response.data.products);
					}
				},
				error: () => {
					const $error = $('<p class="lbite-pos-error-message"></p>');
					$error.text((lbitePos.strings.loadProductsError || 'Error loading products') + '. ');
					$error.append($('<button class="button">' + (lbitePos.strings.tryAgain || 'Try again') + '</button>').on('click', () => this.clearCacheAndReload()));
					$('#lbite-product-grid').html($error);
				},
				complete: () => {
					this.isLoadingProducts = false;
				}
			});
		},

		/**
		 * Cache leeren und neu laden (erzwingt AJAX-Reload)
		 */
		clearCacheAndReload: function() {
			this.productsCache = {};
			this.productDetailsCache = {};
			this.allProducts = [];
			this.dataLoaded = false;

			// Seite neu laden um aktuelle Daten zu erhalten
			window.location.reload();
		},

		/**
		 * Produkte rendern
		 */
		renderProducts: function(products) {
			const $grid = $('#lbite-product-grid');
			$grid.empty();

			products.forEach(product => {
				const hasConfig = product.has_variations || product.has_options;
				const isOos = product.stock_status === 'outofstock';

				const $item = $('<div class="lbite-pos-product-item"></div>')
					.attr('data-product-id', product.id);

				if (isOos) {
					$item.addClass('lbite-out-of-stock');
				}

				// Ausserhalb des Verfügbarkeits-Zeitplans (F40): nur markieren,
				// nicht sperren – das Personal darf bewusst abweichen.
				if (product.off_schedule) {
					$item.addClass('lbite-off-schedule');
					$item.attr('title', lbitePos.strings.offSchedule || 'Outside its scheduled time');
					$item.append($('<span class="lbite-off-schedule-badge"></span>')
						.text(lbitePos.strings.offScheduleShort || 'Off-menu'));
				}

				// Lagerbestand-Toggle (oben rechts)
				const $toggleLabel = $('<label class="lbite-stock-toggle"></label>')
					.on('click', (e) => e.stopPropagation());
				const $toggleInput = $('<input type="checkbox" class="lbite-stock-toggle-input">')
					.prop('checked', !isOos)
					.on('change', (e) => {
						e.stopPropagation();
						this.toggleProductStock(product.id, $item, $toggleInput);
					});
				$toggleLabel.append($toggleInput);
				$toggleLabel.append($('<span class="lbite-stock-toggle-slider"></span>'));
				$item.append($toggleLabel);

				if (product.image) {
					$item.append($('<img>')
						.attr('src', product.image)
						.attr('alt', product.name));
				}

				$item.append($('<div class="lbite-pos-product-name"></div>').text(product.name));

				// Preisspanne bei variablen Produkten
				const minPrice = parseFloat(product.price) || 0;
				const maxPrice = parseFloat(product.max_price || product.price) || 0;
				const priceText = (maxPrice > minPrice)
					? this.formatPrice(minPrice) + ' – ' + this.formatPrice(maxPrice)
					: this.formatPrice(minPrice);
				$item.append($('<div class="lbite-pos-product-price"></div>').text(priceText));

				$item.on('click', () => {
					if (hasConfig) {
						this.openProductModal(product.id);
					} else {
						this.addToCart(product);
					}
				});

				$grid.append($item);
			});
		},

		/**
		 * Modal-Events binden
		 */
		bindModalEvents: function() {
			// Modal schließen
			$('#lbite-modal-close, #lbite-modal-cancel, .lbite-modal-close').on('click', () => {
				this.closeProductModal();
			});

			// Overlay klicken
			$('.lbite-modal-overlay').on('click', () => {
				this.closeProductModal();
			});

			// Produkt hinzufügen
			$('#lbite-modal-add').on('click', () => {
				this.addConfiguredProductToCart();
			});

			// Nicht-verfügbar-Dialog: "Nur heute"
			$('#lbite-unavailable-today').on('click', () => {
				const $dialog = $('#lbite-unavailable-dialog');
				const productId = $dialog.data('product-id');
				const $item = $dialog.data('$item');
				const $input = $dialog.data('$input');
				$dialog.fadeOut(150);
				const endOfDay = new Date();
				endOfDay.setHours(23, 59, 59, 999);
				this.setProductStock(productId, $item, $input, 'outofstock', endOfDay.toISOString());
			});

			// Nicht-verfügbar-Dialog: "Bis auf Weiteres"
			$('#lbite-unavailable-indefinite').on('click', () => {
				const $dialog = $('#lbite-unavailable-dialog');
				const productId = $dialog.data('product-id');
				const $item = $dialog.data('$item');
				const $input = $dialog.data('$input');
				$dialog.fadeOut(150);
				this.setProductStock(productId, $item, $input, 'outofstock', '');
			});

			// Nicht-verfügbar-Dialog: Abbrechen
			$('#lbite-unavailable-cancel').on('click', () => {
				const $dialog = $('#lbite-unavailable-dialog');
				const $input = $dialog.data('$input');
				if ($input) {
					$input.prop('checked', true);
				}
				$dialog.fadeOut(150);
			});
		},

		/**
		 * Produkt-Modal öffnen (mit Cache)
		 */
		openProductModal: function(productId) {
			$('#lbite-product-modal').fadeIn(200);

			// Aus Cache laden wenn vorhanden (JSON oder vorheriger AJAX)
			const cachedDetails = this.productDetailsCache[productId];
			if (cachedDetails) {
				this.currentProduct = cachedDetails;
				this.renderProductModal(cachedDetails);
				return;
			}

			$('#lbite-modal-product-name').text('Laden...');
			const $loading = $('<div class="lbite-pos-modal-loading"></div>');
			$loading.append('<div class="lbite-pos-modal-spinner"></div>');
			$loading.append($('<p></p>').text(lbitePos.strings.loadingProductDetails));
			$('#lbite-modal-body').html($loading);

			// Produkt-Details via AJAX laden (Fallback)
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_get_product_details',
					nonce: lbitePos.nonce,
					product_id: productId
				},
				success: (response) => {
					if (response.success) {
						// Im Cache speichern
						this.productDetailsCache[productId] = response.data;
						this.currentProduct = response.data;
						this.renderProductModal(response.data);
					} else {
						window.lbiteNotify && window.lbiteNotify.error(lbitePos.strings.errorLoadingDetails);
						this.closeProductModal();
					}
				},
				error: () => {
					window.lbiteNotify && window.lbiteNotify.error(lbitePos.strings.errorLoadingDetails);
					this.closeProductModal();
				}
			});
		},

		/**
		 * Produkt-Modal rendern
		 */
	renderProductModal: function(productData) {
		$('#lbite-modal-product-name').text(productData.name);

		const $body = $('#lbite-modal-body');
		$body.empty();
		
		let choiceCounter = 0;

		// Varianten rendern
		if (productData.variations && productData.variations.length > 0) {
			const $group = $('<div class="lbite-option-group lbite-variants-group"></div>');
			const $variantLabel = $('<div class="lbite-option-group-label"></div>').text(lbitePos.strings.selectVariant + ' ');
			$variantLabel.append($('<span style="color: red;" aria-hidden="true">*</span>'));
			$group.append($variantLabel);
			
			// Standard-Variante gemäss WooCommerce «Default Form Values» ermitteln (Fallback: erste Variante).
			const defaultAttributes = productData.default_attributes || {};
			let defaultIndex = productData.variations.findIndex(function(variation) {
				return Object.keys(defaultAttributes).every(function(attrName) {
					const defaultValue = defaultAttributes[attrName];
					if (!defaultValue) {
						return true; // Leer = «Any», keine Einschränkung.
					}
					const variationValue = variation.attributes['attribute_' + attrName];
					return variationValue && variationValue.toLowerCase() === defaultValue.toLowerCase();
				});
			});
			if (defaultIndex === -1) {
				defaultIndex = 0;
			}

			productData.variations.forEach((variation, index) => {
				const inputId = 'modal_choice_' + (choiceCounter++);
				const $label = $('<label class="lbite-option-choice"></label>').attr('for', inputId);

				const $radio = $('<input type="radio" name="variation">')
					.attr('id', inputId)
					.val(variation.id)
					.attr('data-price', variation.price);

				if (index === defaultIndex) {
					$radio.prop('checked', true);
				}

				$label.append($radio);
				$label.append($('<span class="lbite-option-choice-label"></span>').text(variation.name || 'Variante ' + (index + 1)));
				$label.append($('<span class="lbite-option-choice-price"></span>').text(this.formatPrice(variation.price)));
				
				$group.append($label);
			});
			$body.append($group);
		}

		// Optionen rendern (alle als eine «Add-ons»-Gruppe)
		if (productData.options && productData.options.length > 0) {
			const $group = $('<div class="lbite-option-group lbite-addons-group"></div>');
			$group.append($('<div class="lbite-option-group-label"></div>').text(lbitePos.strings.addons || 'Add-ons'));

			productData.options.forEach(option => {
				const inputId = 'modal_choice_' + (choiceCounter++);
				const price   = option.choices && option.choices[0] ? parseFloat(option.choices[0].price) : 0;

				const $label = $('<label class="lbite-option-choice"></label>').attr('for', inputId);
				const $input = $('<input type="checkbox">')
					.attr('id', inputId)
					.attr('name', 'option_' + option.id)
					.val(option.name)
					.attr('data-price', price)
					.attr('data-option-id', option.id);

				$label.append($input);
				$label.append($('<span class="lbite-option-choice-label"></span>').text(option.name));

				if (price > 0) {
					$label.append($('<span class="lbite-option-choice-price"></span>').text('+' + this.formatPrice(price)));
				}

				$group.append($label);
			});
			$body.append($group);
		}

		if ($body.is(':empty')) {
			$body.append($('<p></p>').text('Keine Konfiguration erforderlich.'));
		}
	},

		/**
		 * Modal schließen
		 */
		closeProductModal: function() {
			$('#lbite-product-modal').fadeOut(200);
			this.currentProduct = null;
		},

		/**
		 * Konfiguriertes Produkt zum Warenkorb hinzufügen
		 */
		addConfiguredProductToCart: function() {
			if (!this.currentProduct) {
				return;
			}

			let productId = this.currentProduct.id;
			let productName = this.currentProduct.name;
			let productPrice = parseFloat(this.currentProduct.variations && this.currentProduct.variations.length > 0
				? this.currentProduct.variations[0].price
				: this.currentProduct.price || 0);

			// Ausgewählte Variante
			const selectedVariation = $('input[name="variation"]:checked');
			if (selectedVariation.length > 0) {
				productId = parseInt(selectedVariation.val());
				productPrice = parseFloat(selectedVariation.data('price'));
			}

			// Meta-String und Option-IDs sammeln
			let meta = [];
			let optionIds = [];

			// Ausgewählte Optionen
			$('#lbite-modal-body input:checked').each(function() {
				if ($(this).attr('name') !== 'variation') {
					const label = $(this).val();
					const price = parseFloat($(this).data('price') || 0);
					const optId  = parseInt($(this).data('option-id') || 0, 10);
					meta.push(label);
					productPrice += price;
					if (optId) optionIds.push(optId);
				}
			});

			// Zum Warenkorb hinzufügen
			const metaString = meta.length > 0 ? meta.join(', ') : '';
			const existingItem = this.cart.find(item => item.id === productId && item.meta === metaString);

			if (existingItem) {
				existingItem.quantity++;
			} else {
				this.cart.push({
					id: productId,
					name: productName,
					price: productPrice,
					quantity: 1,
					meta: metaString,
					option_ids: optionIds,
					note: ''
				});
			}

			this.updateCartDisplay();
			this.saveCart();
			this.closeProductModal();
		},

		/**
		 * Zum Warenkorb hinzufügen
		 */
		addToCart: function(product) {
			const existingItem = this.cart.find(item => item.id === product.id);

			if (existingItem) {
				existingItem.quantity++;
			} else {
				this.cart.push({
					id: product.id,
					name: product.name,
					price: parseFloat(product.price),
					quantity: 1,
					note: ''
				});
			}

			this.updateCartDisplay();
			this.saveCart();
		},

		/**
		 * Aus Warenkorb entfernen
		 */
		removeFromCart: function(cartIndex) {
			this.cart.splice(cartIndex, 1);
			this.updateCartDisplay();
			this.saveCart();
		},

		/**
		 * Menge aktualisieren
		 */
		updateQuantity: function(cartIndex, delta) {
			const item = this.cart[cartIndex];
			if (!item) return;

			item.quantity += delta;

			if (item.quantity <= 0) {
				this.removeFromCart(cartIndex);
			} else {
				this.updateCartDisplay();
				this.saveCart();
			}
		},

		/**
		 * Warenkorb-Anzeige aktualisieren
		 */
		updateCartDisplay: function() {
			const $cartItems = $('#lbite-pos-cart-items');
			$cartItems.empty();

			if (this.cart.length === 0) {
				$cartItems.append($('<p style="text-align: center; color: #999;"></p>').text('Warenkorb ist leer'));
				$('#lbite-pos-subtotal, #lbite-pos-total').text(this.formatPrice(0));
				return;
			}

			this.cart.forEach((item, index) => {
				const itemTotal = item.price * item.quantity;
				const $item = $('<div class="lbite-pos-cart-item"></div>');

				const $nameDiv = $('<div class="lbite-pos-cart-item-name"></div>').text(item.name);
				if (item.meta) {
					$nameDiv.append($('<div class="lbite-pos-cart-meta"></div>').text(item.meta));
				}
				$item.append($nameDiv);

				if (lbitePos.enableItemNotes) {
					const $noteInput = $('<input type="text" class="lbite-pos-item-note">')
						.attr('placeholder', lbitePos.strings.itemNotePlaceholder || 'Note...')
						.val(item.note || '')
						.attr('data-cart-index', index);
					$noteInput.on('input', (e) => {
						const idx = $(e.target).data('cart-index');
						if (this.cart[idx] !== undefined) {
							this.cart[idx].note = $(e.target).val();
							this.saveCart();
						}
					});
					$item.append($noteInput);
				}

				const $qtyDiv = $('<div class="lbite-pos-cart-item-qty"></div>');
				$qtyDiv.append($('<button class="lbite-cart-qty-minus">−</button>').attr('data-cart-index', index));
				$qtyDiv.append($('<span></span>').text(item.quantity));
				$qtyDiv.append($('<button class="lbite-cart-qty-plus">+</button>').attr('data-cart-index', index));
				$item.append($qtyDiv);

				$item.append($('<div class="lbite-pos-cart-item-price"></div>').text(this.formatPrice(itemTotal)));
				$item.append($('<span class="lbite-cart-item-remove dashicons dashicons-trash"></span>').attr('data-cart-index', index));

				$cartItems.append($item);
			});

			// Gesamt berechnen
			const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
			const discount = this.calculateDiscount(subtotal);
			const total    = this.applyRounding(subtotal - discount);

			$('#lbite-pos-subtotal').text(this.formatPrice(subtotal));

			if (discount > 0) {
				$('#lbite-pos-discount').text('− ' + this.formatPrice(discount));
				$('#lbite-pos-discount-line').show();
			} else {
				$('#lbite-pos-discount-line').hide();
			}

			$('#lbite-pos-total').text(this.formatPrice(total));
		},

		/**
		 * Warenkorb leeren
		 */
		clearCart: function() {
			this.cart = [];
			this.coupons = [];
			this.updateCartDisplay();
			this.renderAppliedCoupons();
			this.saveCart();
			$('#lbite-pos-customer-name').val('');
			$('#lbite-pos-table').val('').trigger('change');
		},

		/**
		 * Checkout
		 */
		checkout: function() {
			// Doppelklick-Schutz.
			if (this.isProcessingOrder) {
				return;
			}

			if (this.cart.length === 0) {
				window.lbiteNotify.error(lbitePos.strings.cartEmpty);
				return;
			}

			// Standort aus Dropdown holen
			const locationId = $('#lbite-pos-location').val();
			if (!locationId) {
				window.lbiteNotify.error(lbitePos.strings.selectLocation);
				$('#lbite-pos-location').focus();
				return;
			}

			// Aktiver Tab-Modus: Positionen direkt nachbuchen statt Zahlungs-Modal zu öffnen.
			if (this.activeTab) {
				this.addToTab();
				return;
			}

			// Zahlungs-Modal öffnen
			this.openPaymentModal();
		},

		/**
		 * Zahlungs-Modal öffnen und befüllen
		 */
		openPaymentModal: function(mode = 'order', tabData = null) {
			const $modal = $('#lbite-payment-modal');
			$modal.data('mode', mode);
			$modal.data('tab-order-id', tabData ? tabData.order_id : null);

			const $items = $('#lbite-payment-modal-items');
			$items.empty();

			let total;

			if (mode === 'close_tab' && tabData) {
				total = parseFloat(tabData.total_raw) || 0;
				tabData.items.forEach((item) => {
					const $row = $('<div class="lbite-payment-modal-item"></div>');
					$row.append($('<span class="lbite-payment-modal-item-name"></span>').text(item.name));
					$row.append($('<span class="lbite-payment-modal-item-qty"></span>').text(`× ${item.quantity}`));
					$row.append($('<span class="lbite-payment-modal-item-price"></span>').text(''));
					$items.append($row);
				});
			} else {
				const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
				const discount = this.calculateDiscount(subtotal);
				total = this.applyRounding(subtotal - discount);

				this.cart.forEach((item) => {
					const $row = $('<div class="lbite-payment-modal-item"></div>');
					const $name = $('<span class="lbite-payment-modal-item-name"></span>').text(item.name);
					if (item.meta) {
						$name.append($('<small></small>').text(` (${item.meta})`));
					}
					$row.append($name);
					$row.append($('<span class="lbite-payment-modal-item-qty"></span>').text(`× ${item.quantity}`));
					$row.append($('<span class="lbite-payment-modal-item-price"></span>').text(this.formatPrice(item.price * item.quantity)));
					$items.append($row);
				});

				// Gutscheine mit Rabattbetrag anzeigen
				if (this.coupons.length > 0) {
					const $couponRow = $('<div class="lbite-payment-modal-item lbite-payment-modal-coupon-row"></div>');
					$couponRow.append($('<span class="lbite-payment-modal-item-name"></span>').text(
						(lbitePos.strings.coupon || 'Coupon') + ': ' + this.coupons.map(c => c.code).join(', ')
					));
					$couponRow.append($('<span></span>'));
					$couponRow.append($('<span class="lbite-payment-modal-item-price" style="color:#27ae60;"></span>').text(
						discount > 0 ? '− ' + this.formatPrice(discount) : '✓'
					));
					$items.append($couponRow);
				}
			}

			$('#lbite-payment-modal-total').text(this.formatPrice(total));
			$modal.data('total', total);

			// Zahlungsart zurücksetzen (ersten verfügbaren aktivieren)
			$modal.find('input[name="lbite-payment-method"]').prop('checked', false);
			$modal.find('input[name="lbite-payment-method"]:first').prop('checked', true);

			// Split-Payment-Panel zurücksetzen
			$('.lbite-split-amount').val('');
			$('#lbite-split-payment-panel').hide();
			$modal.removeClass('lbite-split-active');
			$('#lbite-payment-modal-confirm').prop('disabled', false);
			this.updateSplitSummary();

			// «Open tab»-Button nur im regulären Bestellmodus anzeigen.
			$('#lbite-payment-modal-open-tab').toggle(mode === 'order');

			if (! this.paymentConfirmDefaultText) {
				this.paymentConfirmDefaultText = $('#lbite-payment-modal-confirm').text();
			}
			$('#lbite-payment-modal-confirm').text(
				mode === 'close_tab' ? (lbitePos.strings.closeTab || 'Close tab') : this.paymentConfirmDefaultText
			);

			$modal.fadeIn(200);
		},

		/**
		 * Zahlungs-Modal schliessen
		 */
		closePaymentModal: function() {
			$('#lbite-payment-modal').fadeOut(200);
		},

		/**
		 * Split-Payment-Panel ein-/ausblenden
		 */
		toggleSplitPanel: function() {
			const $panel = $('#lbite-split-payment-panel');
			const active = $panel.is(':visible');
			$panel.slideToggle(150);
			$('#lbite-payment-modal').toggleClass('lbite-split-active', ! active);
			if (! active) {
				this.updateSplitSummary();
			} else {
				$('#lbite-payment-modal-confirm').prop('disabled', false);
			}
		},

		/**
		 * Summenzeile des Split-Panels aktualisieren und Confirm-Button sperren/freigeben
		 */
		updateSplitSummary: function() {
			const total = parseFloat($('#lbite-payment-modal').data('total')) || 0;
			let assigned = 0;
			$('.lbite-split-amount').each(function() {
				assigned += parseFloat($(this).val()) || 0;
			});
			assigned = Math.round(assigned * 100) / 100;

			const label = (lbitePos.strings.splitAssigned || 'Assigned') + ': ' + this.formatPrice(assigned) +
				' / ' + (lbitePos.strings.splitTotal || 'Total') + ': ' + this.formatPrice(total);
			$('#lbite-split-summary-text').text(label);

			const matches = Math.abs(total - assigned) <= 0.001;
			$('#lbite-split-summary').toggleClass('lbite-split-mismatch', ! matches);

			if ($('#lbite-payment-modal').hasClass('lbite-split-active')) {
				$('#lbite-payment-modal-confirm').prop('disabled', ! matches);
			}
		},

		/**
		 * Liest die eingetragenen Split-Beträge aus. Liefert null, wenn Split inaktiv oder ungültig.
		 */
		getSplitPayments: function() {
			if (! $('#lbite-payment-modal').hasClass('lbite-split-active')) {
				return null;
			}
			const total = parseFloat($('#lbite-payment-modal').data('total')) || 0;
			const payments = [];
			let assigned = 0;
			$('.lbite-split-amount').each(function() {
				const amount = Math.round((parseFloat($(this).val()) || 0) * 100) / 100;
				if (amount > 0) {
					payments.push({ method: $(this).data('method'), amount: amount });
					assigned += amount;
				}
			});
			assigned = Math.round(assigned * 100) / 100;
			if (payments.length === 0 || Math.abs(total - assigned) > 0.001) {
				return null;
			}
			return payments;
		},

		/**
		 * Offene Tabs für den aktuellen Standort laden und im Panel anzeigen.
		 */
		loadOpenTabs: function() {
			const locationId = $('#lbite-pos-location').val();
			if (! locationId) {
				window.lbiteNotify.error(lbitePos.strings.selectLocation);
				return;
			}
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_get_open_tabs',
					nonce: lbitePos.nonce,
					location_id: locationId
				},
				success: (response) => {
					if (response.success) {
						this.renderTabsPanel(response.data.tabs || []);
						$('#lbite-pos-tabs-panel').fadeIn(200);
					} else {
						window.lbiteNotify.error(response.data.message || lbitePos.strings.loadOrdersError);
					}
				},
				error: () => {
					window.lbiteNotify.error(lbitePos.strings.loadOrdersError || 'Error');
				}
			});
		},

		/**
		 * Badge-Zähler in der Topbar aktualisieren, ohne das Panel zu öffnen.
		 */
		refreshTabsBadge: function() {
			const locationId = $('#lbite-pos-location').val();
			if (! locationId || ! $('#lbite-pos-tabs-btn').length) {
				return;
			}
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_get_open_tabs',
					nonce: lbitePos.nonce,
					location_id: locationId
				},
				success: (response) => {
					if (response.success) {
						const count = (response.data.tabs || []).length;
						$('#lbite-pos-tabs-count').text(count).toggle(count > 0);
					}
				}
			});
		},

		/**
		 * Liste offener Tabs im Panel rendern.
		 */
		renderTabsPanel: function(tabs) {
			$('#lbite-pos-tabs-count').text(tabs.length).toggle(tabs.length > 0);

			const $list = $('#lbite-pos-tabs-list');
			$list.empty();

			if (tabs.length === 0) {
				$list.append($('<p class="lbite-tabs-empty"></p>').text(lbitePos.strings.noOpenTabs || 'No open tabs'));
				return;
			}

			tabs.forEach((tab) => {
				const $card = $('<div class="lbite-tab-card"></div>')
					.attr('data-order-id', tab.order_id)
					.attr('data-table-name', tab.table_name || '');

				const $header = $('<div class="lbite-tab-card-header"></div>');
				$header.append($('<strong></strong>').text(tab.table_name || (lbitePos.strings.tab || 'Tab')));
				$header.append($('<span class="lbite-tab-card-time"></span>').text(tab.created));
				$card.append($header);

				const roundLabel = (lbitePos.strings.round || 'Round') + ' ' + tab.round_count;
				$card.append($('<div class="lbite-tab-card-body"></div>').text(`${tab.item_count} × — ${roundLabel}`));
				$card.append($('<div class="lbite-tab-card-total"></div>').text(tab.total));

				const $actions = $('<div class="lbite-tab-card-actions"></div>');
				$actions.append(
					$('<button type="button" class="button lbite-tab-add-items"></button>')
						.attr('data-order-id', tab.order_id)
						.text(lbitePos.strings.addItems || 'Add items')
				);
				$actions.append(
					$('<button type="button" class="button button-primary lbite-tab-pay-close"></button>')
						.attr('data-order-id', tab.order_id)
						.text(lbitePos.strings.payClose || 'Pay / Close')
				);
				$actions.append(
					$('<button type="button" class="button-link lbite-tab-cancel"></button>')
						.attr('data-order-id', tab.order_id)
						.text(lbitePos.strings.cancelOrder || 'Cancel')
				);
				$card.append($actions);

				$list.append($card);
			});
		},

		/**
		 * POS in den Tab-Nachbuchungsmodus versetzen (Warenkorb wird an den Tab, nicht als
		 * neue Bestellung übermittelt).
		 */
		setActiveTab: function(orderId, tableName) {
			this.activeTab = { orderId: orderId, tableName: tableName };
			this.clearCart();
			$('#lbite-pos-active-tab-label').text((lbitePos.strings.addingToTab || 'Adding to tab:') + ' ' + (tableName || ''));
			$('#lbite-pos-active-tab-banner').show();
		},

		/**
		 * Tab-Nachbuchungsmodus verlassen, ohne den Warenkorb zu übertragen.
		 */
		clearActiveTab: function() {
			this.activeTab = null;
			$('#lbite-pos-active-tab-banner').hide();
			this.clearCart();
		},

		/**
		 * Warenkorb als neuen Tab an einem Tisch eröffnen.
		 */
		openTab: function() {
			if (this.cart.length === 0) {
				window.lbiteNotify.error(lbitePos.strings.cartEmpty);
				return;
			}
			const locationId = $('#lbite-pos-location').val();
			const tableId = $('#lbite-pos-table').val() || 0;
			if (! locationId) {
				window.lbiteNotify.error(lbitePos.strings.selectLocation);
				return;
			}
			if (! tableId) {
				window.lbiteNotify.error(lbitePos.strings.selectTable || 'Please select a table');
				return;
			}
			const customerName = $('#lbite-pos-customer-name').val().trim();

			this.showLoading(lbitePos.strings.creatingOrder || 'Creating order...');
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_open_tab',
					nonce: lbitePos.nonce,
					cart_items: JSON.stringify(this.cart),
					location_id: locationId,
					table_id: tableId,
					customer_name: customerName
				},
				success: (response) => {
					if (response.success) {
						window.lbiteNotify.success(lbitePos.strings.tabOpened || 'Tab opened');
						this.clearCart();
						this.refreshTabsBadge();
					} else {
						window.lbiteNotify.error(response.data.message || lbitePos.strings.orderError);
					}
				},
				error: () => {
					window.lbiteNotify.error(lbitePos.strings.orderError);
				},
				complete: () => {
					this.hideLoading();
				}
			});
		},

		/**
		 * Warenkorb-Positionen an den aktiven Tab nachbuchen (Runde+1).
		 */
		addToTab: function() {
			if (! this.activeTab || this.cart.length === 0) {
				return;
			}
			const locationId = $('#lbite-pos-location').val();

			this.showLoading(lbitePos.strings.creatingOrder || 'Creating order...');
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_add_to_tab',
					nonce: lbitePos.nonce,
					order_id: this.activeTab.orderId,
					cart_items: JSON.stringify(this.cart),
					location_id: locationId
				},
				success: (response) => {
					if (response.success) {
						window.lbiteNotify.success(lbitePos.strings.itemsAdded || 'Items added');
						this.clearActiveTab();
						this.refreshTabsBadge();
					} else {
						window.lbiteNotify.error(response.data.message || lbitePos.strings.orderError);
					}
				},
				error: () => {
					window.lbiteNotify.error(lbitePos.strings.orderError);
				},
				complete: () => {
					this.hideLoading();
				}
			});
		},

		/**
		 * Zahlungs-Modal im Tab-Abschluss-Modus öffnen (Positionen des Tabs statt Warenkorb).
		 */
		startCloseTab: function(orderId) {
			const locationId = $('#lbite-pos-location').val();
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_get_open_tabs',
					nonce: lbitePos.nonce,
					location_id: locationId
				},
				success: (response) => {
					if (! response.success) {
						window.lbiteNotify.error(response.data.message || lbitePos.strings.loadOrdersError);
						return;
					}
					const tab = (response.data.tabs || []).find((t) => String(t.order_id) === String(orderId));
					if (! tab) {
						window.lbiteNotify.error(lbitePos.strings.orderError);
						return;
					}
					$('#lbite-pos-tabs-panel').fadeOut(200);
					this.openPaymentModal('close_tab', tab);
				}
			});
		},

		/**
		 * Tab abschliessen und bezahlen.
		 */
		closeTab: function(orderId, paymentMethod, splitPayments) {
			const locationId = $('#lbite-pos-location').val();
			this.showLoading(lbitePos.strings.creatingOrder || 'Creating order...');
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_close_tab',
					nonce: lbitePos.nonce,
					order_id: orderId,
					location_id: locationId,
					payment_method: paymentMethod || 'cash',
					split_payments: splitPayments ? JSON.stringify(splitPayments) : ''
				},
				success: (response) => {
					if (response.success) {
						window.lbiteNotify.success(lbitePos.strings.tabClosed || 'Tab closed');
						this.refreshTabsBadge();
					} else {
						window.lbiteNotify.error(response.data.message || lbitePos.strings.orderError);
					}
				},
				error: () => {
					window.lbiteNotify.error(lbitePos.strings.orderError);
				},
				complete: () => {
					this.hideLoading();
				}
			});
		},

		/**
		 * Offenen Tab stornieren.
		 */
		cancelTab: function(orderId) {
			const locationId = $('#lbite-pos-location').val();
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_cancel_tab',
					nonce: lbitePos.nonce,
					order_id: orderId,
					location_id: locationId
				},
				success: (response) => {
					if (response.success) {
						window.lbiteNotify.success(lbitePos.strings.tabCancelled || 'Tab cancelled');
						this.loadOpenTabs();
					} else {
						window.lbiteNotify.error(response.data.message || lbitePos.strings.orderError);
					}
				},
				error: () => {
					window.lbiteNotify.error(lbitePos.strings.orderError);
				}
			});
		},

		/**
		 * Bestellung erstellen
		 */
		createOrder: function(locationId, orderType, pickupTime, customerName, paymentMethod, tableId = 0, vatType = '', splitPayments = null) {
			// Doppelklick-Schutz.
			this.isProcessingOrder = true;

			// Loading-Overlay anzeigen
			this.showLoading(lbitePos.strings.creatingOrder || 'Creating order...');
			$('#lbite-pos-checkout').prop('disabled', true);

			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_create_order',
					nonce: lbitePos.nonce,
					cart_items: JSON.stringify(this.cart),
					coupon_codes: JSON.stringify(this.coupons.map(c => c.code)),
					location_id: locationId,
					table_id: tableId,
					order_type: orderType,
					pickup_time: pickupTime,
					customer_name: customerName,
					payment_method: paymentMethod || 'cash',
					vat_order_type: vatType,
					split_payments: splitPayments ? JSON.stringify(splitPayments) : ''
				},
				success: (response) => {
					if (response.success) {
						window.lbiteNotify && window.lbiteNotify.success((lbitePos.strings.orderCreatedPrefix || 'Order #') + response.data.order_number + (lbitePos.strings.orderCreatedSuffix || ' created') + ' (' + response.data.total + ')');
						this.clearCart();
					} else {
						window.lbiteNotify && window.lbiteNotify.error(lbitePos.strings.orderError + ': ' + (response.data.message || ''));
					}
				},
				error: (xhr, status, error) => {
					window.lbiteNotify && window.lbiteNotify.error(lbitePos.strings.orderError);
				},
				complete: () => {
					this.isProcessingOrder = false;
					this.hideLoading();
					$('#lbite-pos-checkout').prop('disabled', false);
				}
			});
		},

		/**
		 * Preis formatieren
		 */
		formatPrice: function(price) {
			return lbitePos.currency + parseFloat(price).toFixed(2).replace('.', ',');
		},

		/**
		 * Warenkorb speichern (LocalStorage)
		 */
		cartKey: function() {
			return 'lbite_pos_cart_' + (lbitePos.userId || '0');
		},

		saveCart: function() {
			localStorage.setItem(this.cartKey(), JSON.stringify(this.cart));
		},

		/**
		 * Gespeicherten Warenkorb laden
		 */
		loadSavedCart: function() {
			const saved = localStorage.getItem(this.cartKey());
			if (saved) {
				try {
					this.cart = JSON.parse(saved);
					this.updateCartDisplay();
				} catch (e) {
					console.error('Error loading cart', e);
				}
			}
		},

		/**
		 * Vollbild-Events binden
		 */
		bindFullscreenEvents: function() {
			$('#lbite-pos-fullscreen').on('click', () => {
				this.toggleFullscreen();
			});

			// Vendor-präfixierte Events für alle Browser
			['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(evt => {
				document.addEventListener(evt, () => this.updateFullscreenButton());
			});

			// Android: Vollbild nach Systemunterbrechung (Notification, App-Wechsel) wiederherstellen
			document.addEventListener('visibilitychange', () => {
				if (document.visibilityState === 'visible' && this.fullscreenDesired && !this.isFullscreenActive()) {
					this.requestRealFullscreen();
				}
			});
		},

		/**
		 * Vollbild umschalten
		 */
		toggleFullscreen: function() {
			if (this.isFullscreenActive()) {
				this.fullscreenDesired = false;
				this.exitRealFullscreen();
			} else {
				this.fullscreenDesired = true;
				this.requestRealFullscreen();
			}
		},

		/**
		 * Prüft ob echtes oder Pseudo-Vollbild aktiv ist
		 */
		isFullscreenActive: function() {
			return !!(
				document.fullscreenElement ||
				document.webkitFullscreenElement ||
				document.mozFullScreenElement ||
				document.msFullscreenElement
			);
		},

		/**
		 * Echtes Vollbild anfordern (alle Vendor-Varianten, Fallback auf Pseudo-Vollbild)
		 */
		requestRealFullscreen: function() {
			const el = document.documentElement;
			if (el.requestFullscreen) {
				el.requestFullscreen().catch(() => this.activatePseudoFullscreen());
			} else if (el.webkitRequestFullscreen) {
				// Safari/iPad: Promise wird nicht unterstützt – Timeout-Fallback
				el.webkitRequestFullscreen();
				setTimeout(() => {
					if (!this.isFullscreenActive()) {
						this.activatePseudoFullscreen();
					}
				}, 300);
			} else if (el.mozRequestFullScreen) {
				el.mozRequestFullScreen();
			} else if (el.msRequestFullscreen) {
				el.msRequestFullscreen();
			} else {
				// Kein API-Support (iPad Safari) → CSS-Vollbild
				this.activatePseudoFullscreen();
			}
		},

		/**
		 * Vollbild beenden (alle Vendor-Varianten)
		 */
		exitRealFullscreen: function() {
			if (document.exitFullscreen) {
				document.exitFullscreen();
			} else if (document.webkitExitFullscreen) {
				document.webkitExitFullscreen();
			} else if (document.mozCancelFullScreen) {
				document.mozCancelFullScreen();
			} else if (document.msExitFullscreen) {
				document.msExitFullscreen();
			}
			this.deactivatePseudoFullscreen();
		},

		/**
		 * CSS-Vollbild aktivieren (ohne Fullscreen API)
		 */
		activatePseudoFullscreen: function() {
			$('body').addClass('lbite-fullscreen-active');
			this.updateFullscreenButton();
		},

		/**
		 * CSS-Vollbild deaktivieren
		 */
		deactivatePseudoFullscreen: function() {
			$('body').removeClass('lbite-fullscreen-active');
			this.updateFullscreenButton();
		},

		/**
		 * Wake Lock anfordern
		 */
		requestWakeLock: function() {
			if (!('wakeLock' in navigator)) {
				return;
			}
			navigator.wakeLock.request('screen').then((lock) => {
				this.wakeLock = lock;
				this.wakeLock.addEventListener('release', () => {
					this.wakeLock = null;
					// Sofort neu anfordern falls Checkbox noch aktiv und Seite sichtbar
					if ($('#lbite-pos-wake-lock').is(':checked') && document.visibilityState === 'visible') {
						this.requestWakeLock();
					}
				});
			}).catch(() => {});
		},

		/**
		 * Wake Lock freigeben
		 */
		releaseWakeLock: function() {
			if (this.wakeLock) {
				this.wakeLock.release().then(() => {
					this.wakeLock = null;
				});
			}
		},

		/**
		 * Gutschein-Popup öffnen und Gutscheine laden
		 */
		openCouponPopup: function() {
			$('#lbite-pos-coupon-popup').fadeIn(200);
			const $list = $('#lbite-pos-coupon-list');
			$list.html($('<p style="color:#999; text-align:center; padding:20px;"></p>').text(
				lbitePos.strings.loadingCoupons || 'Loading coupons...'
			));

			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_get_coupons',
					nonce: lbitePos.nonce
				},
				success: (response) => {
					$list.empty();
					if (!response.success || !response.data || !response.data.coupons || !response.data.coupons.length) {
						$list.append($('<p style="color:#999; text-align:center; padding:20px;"></p>').text(
							lbitePos.strings.noCoupons || 'No active coupons available'
						));
						return;
					}
					response.data.coupons.forEach(coupon => {
						const alreadyAdded = this.coupons.findIndex(c => c.code === coupon.code) !== -1;
						const $row = $('<div class="lbite-coupon-row"></div>');
						const $info = $('<div class="lbite-coupon-info"></div>');
						$info.append($('<strong></strong>').text(coupon.code));
						if (coupon.description) {
							$info.append($('<span style="color:#777;"></span>').text(' – ' + coupon.description));
						}
						const discountText = coupon.discount_type === 'percent'
							? coupon.amount + '%'
							: this.formatPrice(parseFloat(coupon.amount));
						$info.append($('<em style="color:#0073aa;margin-left:6px;"></em>').text('(' + discountText + ')'));
						$row.append($info);
						const $btn = $('<button type="button" class="button"></button>')
							.text(alreadyAdded ? '✓' : '+')
							.prop('disabled', alreadyAdded);
						if (!alreadyAdded) {
							$btn.on('click', () => {
								this.addCoupon(coupon.code, coupon.discount_type, coupon.amount);
								this.closeCouponPopup();
							});
						}
						$row.append($btn);
						$list.append($row);
					});
				}
			});
		},

		/**
		 * Gutschein-Popup schliessen
		 */
		closeCouponPopup: function() {
			$('#lbite-pos-coupon-popup').fadeOut(200);
		},

		/**
		 * Gutschein hinzufügen
		 */
		addCoupon: function(code, discountType, amount) {
			if (this.coupons.findIndex(c => c.code === code) === -1) {
				this.coupons.push({code, discount_type: discountType || 'fixed_cart', amount: amount || 0});
				this.renderAppliedCoupons();
				this.updateCartDisplay();
				window.lbiteNotify && window.lbiteNotify.success(
					(lbitePos.strings.couponAdded || 'Coupon added') + ': ' + code
				);
			}
		},

		/**
		 * Gutschein entfernen
		 */
		removeCoupon: function(code) {
			this.coupons = this.coupons.filter(c => c.code !== code);
			this.renderAppliedCoupons();
			this.updateCartDisplay();
		},

		/**
		 * Gutschein-Tags rendern
		 */
		renderAppliedCoupons: function() {
			const $container = $('#lbite-pos-applied-coupons');
			$container.empty();
			this.coupons.forEach(coupon => {
				const $tag = $('<span class="lbite-coupon-tag"></span>');
				$tag.append($('<span></span>').text(coupon.code));
				$tag.append(
					$('<button type="button" class="lbite-coupon-remove" aria-label="Remove">&times;</button>')
						.on('click', () => this.removeCoupon(coupon.code))
				);
				$container.append($tag);
			});
		},

		/**
		 * Rabatt aller aktiven Gutscheine berechnen
		 */
		calculateDiscount: function(subtotal) {
			let discount = 0;
			this.coupons.forEach(coupon => {
				const amount = parseFloat(coupon.amount) || 0;
				if (coupon.discount_type === 'percent') {
					discount += subtotal * (amount / 100);
				} else {
					discount += amount;
				}
			});
			return Math.min(discount, subtotal);
		},

		/**
		 * Betrag auf 5-Rappen runden (nur wenn in Plugin-Einstellungen aktiviert)
		 */
		applyRounding: function(amount) {
			if (!lbitePos.enableRounding) {
				return amount;
			}
			return Math.round(amount * 20) / 20;
		},

		/**
		 * Lagerbestand eines Produkts umschalten
		 */
		toggleProductStock: function(productId, $item, $input) {
			const newStatus = $input.is(':checked') ? 'instock' : 'outofstock';

			if (newStatus === 'instock') {
				if (!window.confirm(lbitePos.strings.confirmInStock)) {
					$input.prop('checked', false);
					return;
				}
				this.setProductStock(productId, $item, $input, 'instock', '');
			} else {
				// Toggle optisch zurücksetzen bis Wahl getroffen
				$input.prop('checked', true);
				this.showUnavailableDialog(productId, $item, $input);
			}
		},

		/**
		 * Dialog "Nicht verfügbar" anzeigen
		 */
		showUnavailableDialog: function(productId, $item, $input) {
			const $dialog = $('#lbite-unavailable-dialog');
			$dialog.data('product-id', productId).data('$item', $item).data('$input', $input);
			$dialog.fadeIn(150);
		},

		/**
		 * Lagerbestand per AJAX setzen
		 */
		setProductStock: function(productId, $item, $input, newStatus, unavailableUntil) {
			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_toggle_stock',
					nonce: lbitePos.nonce,
					product_id: productId,
					stock_status: newStatus,
					unavailable_until: unavailableUntil || ''
				},
				success: (response) => {
					if (response.success) {
						$item.toggleClass('lbite-out-of-stock', newStatus === 'outofstock');
						const cached = this.allProducts.find(p => p.id === productId);
						if (cached) {
							cached.stock_status = newStatus;
							cached.unavailable_until = unavailableUntil || '';
						}
						if (newStatus === 'outofstock') {
							$input.prop('checked', false);
						} else {
							$input.prop('checked', true);
						}
					} else {
						$input.prop('checked', newStatus === 'instock' ? false : true);
					}
				},
				error: () => {
					$input.prop('checked', newStatus === 'instock' ? false : true);
				}
			});
		},

		/**
		 * Vollbild-Button aktualisieren
		 */
		updateFullscreenButton: function() {
			const $btn = $('#lbite-pos-fullscreen');
			const $icon = $btn.find('.dashicons');
			const isActive = this.isFullscreenActive() || $('body').hasClass('lbite-fullscreen-active');

			if (isActive) {
				$icon.removeClass('dashicons-editor-expand').addClass('dashicons-editor-contract');
				$btn.attr('title', lbitePos.strings.exitFullscreen || 'Vollbild beenden');
				$('body').addClass('lbite-fullscreen-active');
			} else {
				$icon.removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
				$btn.attr('title', lbitePos.strings.enterFullscreen || 'Vollbild');
				$('body').removeClass('lbite-fullscreen-active');
			}
		}
	};

	// Global verfügbar machen
	window.POS = POS;

	// Initialisieren
	$(document).ready(() => {
		if ($('.lbite-pos').length > 0) {
			POS.init();
		}
	});

})(jQuery);
