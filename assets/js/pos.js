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
		 * Produkte ohne Standort-Zuweisung (leeres location_ids-Array) sind überall verfügbar.
		 */
		filterByLocation: function(locationId) {
			locationId = locationId ? parseInt(locationId, 10) : 0;

			if (!locationId) {
				this.filteredProducts = this.allProducts;
			} else {
				this.filteredProducts = this.allProducts.filter(function(product) {
					if (!product.location_ids || product.location_ids.length === 0) {
						return true;
					}
					return product.location_ids.indexOf(locationId) !== -1;
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

			// Zahlung bestätigen und Bestellung anlegen
			$(document).on('click', '#lbite-payment-modal-confirm', function() {
				const paymentMethod = $('input[name="lbite-payment-method"]:checked').val() || 'cash';
				const locationId = $('#lbite-pos-location').val();
				const tableId = $('#lbite-pos-table').val() || 0;
				const customerName = $('#lbite-pos-customer-name').val().trim();
				POS.closePaymentModal();
				POS.createOrder(locationId, 'now', '', customerName, paymentMethod, tableId);
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
			
			productData.variations.forEach((variation, index) => {
				const inputId = 'modal_choice_' + (choiceCounter++);
				const $label = $('<label class="lbite-option-choice"></label>').attr('for', inputId);
				
				const $radio = $('<input type="radio" name="variation">')
					.attr('id', inputId)
					.val(variation.id)
					.attr('data-price', variation.price);
				
				if (index === 0) {
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

			// Meta-String für Optionen sammeln
			let meta = [];

			// Ausgewählte Optionen
			$('#lbite-modal-body input:checked').each(function() {
				if ($(this).attr('name') !== 'variation') {
					const label = $(this).val();
					const price = parseFloat($(this).data('price') || 0);
					meta.push(label);
					productPrice += price;
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
					meta: metaString
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
					quantity: 1
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
			// Namensfeld auch leeren für nächste Bestellung
			$('#lbite-pos-customer-name').val('');
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

			// Zahlungs-Modal öffnen
			this.openPaymentModal();
		},

		/**
		 * Zahlungs-Modal öffnen und befüllen
		 */
		openPaymentModal: function() {
			const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
			const discount = this.calculateDiscount(subtotal);
			const total    = this.applyRounding(subtotal - discount);

			// Bestellpositionen rendern
			const $items = $('#lbite-payment-modal-items');
			$items.empty();

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

			$('#lbite-payment-modal-total').text(this.formatPrice(total));

			// Zahlungsart zurücksetzen (ersten verfügbaren aktivieren)
			$('#lbite-payment-modal input[name="lbite-payment-method"]').prop('checked', false);
			$('#lbite-payment-modal input[name="lbite-payment-method"]:first').prop('checked', true);

			$('#lbite-payment-modal').fadeIn(200);
		},

		/**
		 * Zahlungs-Modal schliessen
		 */
		closePaymentModal: function() {
			$('#lbite-payment-modal').fadeOut(200);
		},

		/**
		 * Bestellung erstellen
		 */
		createOrder: function(locationId, orderType, pickupTime, customerName, paymentMethod, tableId = 0) {
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
					payment_method: paymentMethod || 'cash'
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

			// Vollbild-Status überwachen
			document.addEventListener('fullscreenchange', () => {
				this.updateFullscreenButton();
			});
		},

		/**
		 * Vollbild umschalten
		 */
		toggleFullscreen: function() {
			if (!document.fullscreenElement) {
				document.documentElement.requestFullscreen().catch(err => {
					console.error('Vollbild-Fehler:', err);
				});
			} else {
				if (document.exitFullscreen) {
					document.exitFullscreen();
				}
			}
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
			const confirmMsg = newStatus === 'outofstock'
				? lbitePos.strings.confirmOutOfStock
				: lbitePos.strings.confirmInStock;

			if ( ! window.confirm( confirmMsg ) ) {
				$input.prop( 'checked', ! $input.is( ':checked' ) );
				return;
			}

			$.ajax({
				url: lbitePos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_pos_toggle_stock',
					nonce: lbitePos.nonce,
					product_id: productId,
					stock_status: newStatus
				},
				success: (response) => {
					if (response.success) {
						$item.toggleClass('lbite-out-of-stock', newStatus === 'outofstock');
						const cached = this.allProducts.find(p => p.id === productId);
						if (cached) {
							cached.stock_status = newStatus;
						}
					} else {
						$input.prop('checked', !$input.is(':checked'));
					}
				},
				error: () => {
					$input.prop('checked', !$input.is(':checked'));
				}
			});
		},

		/**
		 * Vollbild-Button aktualisieren
		 */
		updateFullscreenButton: function() {
			const $btn = $('#lbite-pos-fullscreen');
			const $icon = $btn.find('.dashicons');

			if (document.fullscreenElement) {
				$icon.removeClass('dashicons-editor-expand').addClass('dashicons-editor-contract');
				$btn.attr('title', 'Vollbild beenden');
				$('body').addClass('lbite-fullscreen-active');
			} else {
				$icon.removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
				$btn.attr('title', 'Vollbild');
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
