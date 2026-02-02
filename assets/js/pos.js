/**
 * POS/Kassensystem JavaScript
 */

(function($) {
	'use strict';

	const POS = {
		cart: [],
		currentCategory: 'all',
		currentProduct: null,
		isProcessingOrder: false,
		productsCache: {},
		productDetailsCache: {},
		isLoadingProducts: false,
		allProducts: [],
		allCategories: [],
		dataLoaded: false,

		/**
		 * Initialisierung
		 */
		init: function() {
			this.bindEvents();
			this.bindModalEvents();
			this.loadSavedCart();
			this.bindFullscreenEvents();

			// Eingebettete Daten verwenden (kein HTTP-Request nötig)
			if (lbPos.preloadData && lbPos.preloadData.products) {
				this.usePreloadedData(lbPos.preloadData);
			} else {
				// Fallback auf AJAX
				this.loadProducts();
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

			// Produkte-Cache pro Kategorie aufbauen
			this.buildCategoryCache();

			// Produkte sofort anzeigen
			this.renderProducts(this.productsCache['all'] || []);
		},

		/**
		 * Kategorie-Cache aus den geladenen Daten aufbauen
		 */
		buildCategoryCache: function() {
			if (!this.allProducts.length) return;

			// Alle Produkte (category: all)
			this.productsCache['all'] = this.allProducts;

			// Pro Kategorie gruppieren
			this.allProducts.forEach(product => {
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
			$(document).on('click', '.lb-category-btn', function() {
				$('.lb-category-btn').removeClass('active');
				$(this).addClass('active');
				POS.currentCategory = $(this).data('category');
				POS.loadProducts();
			});

			// Warenkorb leeren
			$('#lb-pos-clear').on('click', () => {
				if (confirm('Warenkorb wirklich leeren?')) {
					this.clearCart();
				}
			});

			// Checkout
			$('#lb-pos-checkout').on('click', () => {
				this.checkout();
			});

			// Produkt entfernen
			$(document).on('click', '.lb-cart-item-remove', function() {
				const cartIndex = $(this).data('cart-index');
				POS.removeFromCart(cartIndex);
			});

			// Menge ändern
			$(document).on('click', '.lb-cart-qty-minus', function() {
				const cartIndex = $(this).data('cart-index');
				POS.updateQuantity(cartIndex, -1);
			});

			$(document).on('click', '.lb-cart-qty-plus', function() {
				const cartIndex = $(this).data('cart-index');
				POS.updateQuantity(cartIndex, 1);
			});
		},

		/**
		 * Lade-Overlay anzeigen
		 */
		showLoading: function(message = 'Laden...') {
			if ($('#lb-pos-loading').length === 0) {
				$('body').append(`
					<div id="lb-pos-loading" style="
						position: fixed;
						top: 0;
						left: 0;
						right: 0;
						bottom: 0;
						background: rgba(255,255,255,0.9);
						display: flex;
						flex-direction: column;
						align-items: center;
						justify-content: center;
						z-index: 99999;
					">
						<div style="
							width: 50px;
							height: 50px;
							border: 4px solid #e0e0e0;
							border-top: 4px solid #0073aa;
							border-radius: 50%;
							animation: lb-pos-spin 0.8s linear infinite;
						"></div>
						<p id="lb-pos-loading-text" style="margin-top: 15px; font-size: 16px; color: #333; font-weight: 500;">${message}</p>
					</div>
					<style>
						@keyframes lb-pos-spin {
							0% { transform: rotate(0deg); }
							100% { transform: rotate(360deg); }
						}
					</style>
				`);
			} else {
				$('#lb-pos-loading-text').text(message);
			}
			$('#lb-pos-loading').fadeIn(100);
		},

		/**
		 * Lade-Overlay ausblenden
		 */
		hideLoading: function() {
			$('#lb-pos-loading').fadeOut(100);
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

			$('#lb-product-grid').html(`
				<div style="text-align: center; padding: 40px;">
					<div style="
						width: 40px;
						height: 40px;
						border: 3px solid #e0e0e0;
						border-top: 3px solid #0073aa;
						border-radius: 50%;
						animation: lb-pos-spin 0.8s linear infinite;
						margin: 0 auto 15px;
					"></div>
					<p style="color: #666;">Produkte werden geladen...</p>
				</div>
			`);

			$.ajax({
				url: lbPos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lb_pos_get_products',
					nonce: lbPos.nonce,
					category_id: this.currentCategory === 'all' ? 0 : this.currentCategory
				},
				success: (response) => {
					if (response.success && response.data.products) {
						// Im Cache speichern
						this.productsCache[cacheKey] = response.data.products;
						this.renderProducts(response.data.products);
					}
				},
				error: () => {
					$('#lb-product-grid').html('<p style="text-align: center; padding: 20px; color: red;">Fehler beim Laden der Produkte. <button onclick="POS.clearCacheAndReload()" class="button">Erneut versuchen</button></p>');
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
			const $grid = $('#lb-product-grid');
			$grid.empty();

			products.forEach(product => {
				const imageHtml = product.image
					? `<img src="${product.image}" alt="${product.name}" style="max-width: 100%; height: auto; margin-bottom: 5px;">`
					: '';

				const hasConfig = product.has_variations || product.has_options;
				const configClass = hasConfig ? 'lb-product-has-config' : '';

				const $item = $(`
					<div class="lb-pos-product-item ${configClass}" data-product-id="${product.id}" data-has-config="${hasConfig}">
						${imageHtml}
						<div class="lb-pos-product-name">${product.name}</div>
						<div class="lb-pos-product-price">${this.formatPrice(product.price)}</div>
					</div>
				`);

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
			$('#lb-modal-close, #lb-modal-cancel, .lb-modal-close').on('click', () => {
				this.closeProductModal();
			});

			// Overlay klicken
			$('.lb-modal-overlay').on('click', () => {
				this.closeProductModal();
			});

			// Produkt hinzufügen
			$('#lb-modal-add').on('click', () => {
				this.addConfiguredProductToCart();
			});
		},

		/**
		 * Produkt-Modal öffnen (mit Cache)
		 */
		openProductModal: function(productId) {
			$('#lb-product-modal').fadeIn(200);

			// Aus Cache laden wenn vorhanden (JSON oder vorheriger AJAX)
			const cachedDetails = this.productDetailsCache[productId];
			if (cachedDetails) {
				this.currentProduct = cachedDetails;
				this.renderProductModal(cachedDetails);
				return;
			}

			$('#lb-modal-product-name').text('Laden...');
			$('#lb-modal-body').html(`
				<div style="text-align: center; padding: 30px;">
					<div style="
						width: 30px;
						height: 30px;
						border: 3px solid #e0e0e0;
						border-top: 3px solid #0073aa;
						border-radius: 50%;
						animation: lb-pos-spin 0.8s linear infinite;
						margin: 0 auto 10px;
					"></div>
					<p style="color: #666; margin: 0;">Produktdetails werden geladen...</p>
				</div>
			`);

			// Produkt-Details via AJAX laden (Fallback)
			$.ajax({
				url: lbPos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lb_pos_get_product_details',
					nonce: lbPos.nonce,
					product_id: productId
				},
				success: (response) => {
					if (response.success) {
						// Im Cache speichern
						this.productDetailsCache[productId] = response.data;
						this.currentProduct = response.data;
						this.renderProductModal(response.data);
					} else {
						window.lbNotify && window.lbNotify.error('Fehler beim Laden der Produktdetails');
						this.closeProductModal();
					}
				},
				error: () => {
					window.lbNotify && window.lbNotify.error('Fehler beim Laden der Produktdetails');
					this.closeProductModal();
				}
			});
		},

		/**
		 * Produkt-Modal rendern
		 */
	renderProductModal: function(productData) {
		$('#lb-modal-product-name').text(productData.name);

		let html = '';
		let choiceCounter = 0;

		// Varianten rendern
		if (productData.variations && productData.variations.length > 0) {
			html += '<div class="lb-option-group">';
			html += '<div class="lb-option-group-label">Variante wählen: <span style="color: red;">*</span></div>';
			productData.variations.forEach((variation, index) => {
				const variationName = variation.name || 'Variante ' + (index + 1);
				const inputId = 'modal_choice_' + (choiceCounter++);
				html += `
					<label class="lb-option-choice" for="${inputId}">
						<input type="radio" id="${inputId}" name="variation" value="${variation.id}" ${index === 0 ? 'checked' : ''} data-price="${variation.price}">
						<span class="lb-option-choice-label">${variationName}</span>
						<span class="lb-option-choice-price">${this.formatPrice(variation.price)}</span>
					</label>
				`;
			});
			html += '</div>';
		}

		// Optionen rendern
		if (productData.options && productData.options.length > 0) {
			productData.options.forEach(option => {
				html += '<div class="lb-option-group">';
				html += `<div class="lb-option-group-label">${option.name}${option.required ? ' <span style="color: red;">*</span>' : ''}</div>`;

				option.choices.forEach((choice, choiceIndex) => {
					const inputType = option.type === 'checkbox' ? 'checkbox' : 'radio';
					const inputName = option.type === 'checkbox' ? `option_${option.id}[]` : `option_${option.id}`;
					const inputId = 'modal_choice_' + (choiceCounter++);

					html += `
						<label class="lb-option-choice" for="${inputId}">
							<input type="${inputType}" id="${inputId}" name="${inputName}" value="${choice.label}" data-price="${choice.price}" data-option-id="${option.id}">
							<span class="lb-option-choice-label">${choice.label}</span>
							${choice.price > 0 ? `<span class="lb-option-choice-price">+${this.formatPrice(choice.price)}</span>` : ''}
						</label>
					`;
				});
				html += '</div>';
			});
		}

		if (!html) {
			html = '<p>Keine Konfiguration erforderlich.</p>';
		}

		$('#lb-modal-body').html(html);
	},

		/**
		 * Modal schließen
		 */
		closeProductModal: function() {
			$('#lb-product-modal').fadeOut(200);
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
			$('#lb-modal-body input:checked').each(function() {
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
			const $cartItems = $('#lb-pos-cart-items');
			$cartItems.empty();

			if (this.cart.length === 0) {
				$cartItems.html('<p style="text-align: center; color: #999;">Warenkorb ist leer</p>');
				$('#lb-pos-subtotal, #lb-pos-total').text(this.formatPrice(0));
				return;
			}

			this.cart.forEach((item, index) => {
				const itemTotal = item.price * item.quantity;
				const metaHtml = item.meta ? `<div style="font-size: 0.9em; color: #666; margin-top: 3px;">${item.meta}</div>` : '';

				const $item = $(`
					<div class="lb-pos-cart-item">
						<div class="lb-pos-cart-item-name">
							${item.name}
							${metaHtml}
						</div>
						<div class="lb-pos-cart-item-qty">
							<button class="lb-cart-qty-minus" data-cart-index="${index}">−</button>
							<span>${item.quantity}</span>
							<button class="lb-cart-qty-plus" data-cart-index="${index}">+</button>
						</div>
						<div class="lb-pos-cart-item-price">${this.formatPrice(itemTotal)}</div>
						<span class="lb-cart-item-remove dashicons dashicons-trash" data-cart-index="${index}"></span>
					</div>
				`);

				$cartItems.append($item);
			});

			// Gesamt berechnen
			const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
			$('#lb-pos-subtotal, #lb-pos-total').text(this.formatPrice(subtotal));
		},

		/**
		 * Warenkorb leeren
		 */
		clearCart: function() {
			this.cart = [];
			this.updateCartDisplay();
			this.saveCart();
			// Namensfeld auch leeren für nächste Bestellung
			$('#lb-pos-customer-name').val('');
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
				window.lbNotify.error(lbPos.strings.cartEmpty);
				return;
			}

			// Standort aus Dropdown holen
			const locationId = $('#lb-pos-location').val();
			if (!locationId) {
				window.lbNotify.error(lbPos.strings.selectLocation);
				$('#lb-pos-location').focus();
				return;
			}

			// Kassen-Bestellungen sind immer für sofort
			const orderType = 'now';
			const pickupTime = '';

			// Kunden-Name aus dem Eingabefeld in der Warenkorb-Box holen
			const customerName = $('#lb-pos-customer-name').val().trim();

			// Direkt Bestellung erstellen (kein Modal mehr)
			this.createOrder(locationId, orderType, pickupTime, customerName);
		},

		/**
		 * Bestellung erstellen
		 */
		createOrder: function(locationId, orderType, pickupTime, customerName) {
			// Doppelklick-Schutz.
			this.isProcessingOrder = true;

			// Loading-Overlay anzeigen
			this.showLoading('Bestellung wird erstellt...');
			$('#lb-pos-checkout').prop('disabled', true);

			$.ajax({
				url: lbPos.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lb_pos_create_order',
					nonce: lbPos.nonce,
					cart_items: JSON.stringify(this.cart),
					location_id: locationId,
					order_type: orderType,
					pickup_time: pickupTime,
					customer_name: customerName
				},
				success: (response) => {
					if (response.success) {
						window.lbNotify && window.lbNotify.success(`Bestellung #${response.data.order_number} erstellt (${response.data.total})`);
						this.clearCart();
					} else {
						window.lbNotify && window.lbNotify.error(lbPos.strings.orderError + ': ' + (response.data.message || ''));
					}
				},
				error: (xhr, status, error) => {
					window.lbNotify && window.lbNotify.error(lbPos.strings.orderError);
				},
				complete: () => {
					this.isProcessingOrder = false;
					this.hideLoading();
					$('#lb-pos-checkout').prop('disabled', false);
				}
			});
		},

		/**
		 * Preis formatieren
		 */
		formatPrice: function(price) {
			return lbPos.currency + parseFloat(price).toFixed(2).replace('.', ',');
		},

		/**
		 * Warenkorb speichern (LocalStorage)
		 */
		saveCart: function() {
			localStorage.setItem('lb_pos_cart', JSON.stringify(this.cart));
		},

		/**
		 * Gespeicherten Warenkorb laden
		 */
		loadSavedCart: function() {
			const saved = localStorage.getItem('lb_pos_cart');
			if (saved) {
				try {
					this.cart = JSON.parse(saved);
					this.updateCartDisplay();
				} catch (e) {
					console.error('Fehler beim Laden des Warenkorbs', e);
				}
			}
		},

		/**
		 * Vollbild-Events binden
		 */
		bindFullscreenEvents: function() {
			$('#lb-pos-fullscreen').on('click', () => {
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
		 * Vollbild-Button aktualisieren
		 */
		updateFullscreenButton: function() {
			const $btn = $('#lb-pos-fullscreen');
			const $icon = $btn.find('.dashicons');

			if (document.fullscreenElement) {
				$icon.removeClass('dashicons-editor-expand').addClass('dashicons-editor-contract');
				$btn.attr('title', 'Vollbild beenden');
				$('body').addClass('lb-fullscreen-active');
			} else {
				$icon.removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
				$btn.attr('title', 'Vollbild');
				$('body').removeClass('lb-fullscreen-active');
			}
		}
	};

	// Global verfügbar machen
	window.POS = POS;

	// Initialisieren
	$(document).ready(() => {
		if ($('.lb-pos').length > 0) {
			POS.init();
		}
	});

})(jQuery);
