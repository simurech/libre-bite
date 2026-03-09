/**
 * Tischplan – Admin-Seite (freie Positionierung, Status-Anzeige)
 */
(function ($) {
	'use strict';

	const FloorPlan = {
		canvas: null,
		tables: [],
		isDirty: false,
		currentLocationId: null,
		dragState: null,
		lastDragMoved: false,
		statusInterval: null,
		popupTableId: null,

		init() {
			this.canvas = document.getElementById('lbite-fp-canvas');
			this.bindGlobalEvents();
			this.bindToolbarEvents();

			const savedLocation = document.getElementById('lbite-floor-plan-location').value;
			if (savedLocation) {
				this.currentLocationId = savedLocation;
				this.loadTables(savedLocation);
			}
		},

		bindGlobalEvents() {
			document.addEventListener('mousemove', (e) => this.onDrag(e));
			document.addEventListener('mouseup', () => this.stopDrag());
			document.addEventListener('touchmove', (e) => {
				if (this.dragState) {
					e.preventDefault();
					this.onDrag(e.touches[0]);
				}
			}, { passive: false });
			document.addEventListener('touchend', () => this.stopDrag());
			document.addEventListener('click', (e) => {
				if (!e.target.closest('#lbite-fp-popup') && !e.target.closest('.lbite-fp-table')) {
					this.hidePopup();
				}
			});
		},

		bindToolbarEvents() {
			document.getElementById('lbite-floor-plan-location').addEventListener('change', (e) => {
				this.currentLocationId = e.target.value;
				this.isDirty = false;
				this.clearCanvas();
				document.getElementById('lbite-fp-save').disabled = true;
				document.getElementById('lbite-fp-status').textContent = '';
				this.stopStatusRefresh();
				if (this.currentLocationId) {
					this.loadTables(this.currentLocationId);
				}
			});

			document.getElementById('lbite-fp-save').addEventListener('click', () => this.savePositions());
			document.getElementById('lbite-fp-refresh').addEventListener('click', () => this.loadStatuses());
		},

		clearCanvas() {
			this.canvas.innerHTML = '';
			this.tables = [];
			this.popupTableId = null;
			this.hidePopup();
			document.getElementById('lbite-fp-canvas-wrap').style.display = 'none';
			document.getElementById('lbite-fp-empty').style.display = 'none';
			document.getElementById('lbite-fp-legend').style.display = 'none';
		},

		loadTables(locationId) {
			document.getElementById('lbite-fp-empty').style.display = 'none';
			document.getElementById('lbite-fp-canvas-wrap').style.display = '';
			document.getElementById('lbite-fp-legend').style.display = 'none';
			this.canvas.innerHTML = '<div class="lbite-fp-loading">&#9202; ' + lbiteFloorPlan.strings.loading + '</div>';

			$.ajax({
				url: lbiteFloorPlan.ajaxUrl,
				type: 'POST',
				data: {
					action: 'lbite_get_floor_plan_tables',
					nonce: lbiteFloorPlan.nonce,
					location_id: locationId
				},
				success: (response) => {
					this.canvas.innerHTML = '';

					if (!response.success || !response.data.tables.length) {
						document.getElementById('lbite-fp-canvas-wrap').style.display = 'none';
						document.getElementById('lbite-fp-empty').style.display = '';
						return;
					}

					this.tables = [];
					const total = response.data.tables.length;
					response.data.tables.forEach((table, index) => {
						const t = this.createTable(table, index, total);
						this.tables.push(t);
						this.canvas.appendChild(t.el);
					});

					document.getElementById('lbite-fp-legend').style.display = '';
					this.loadStatuses();
					this.startStatusRefresh();
				},
				error: () => {
					this.canvas.innerHTML = '<p class="lbite-fp-error">' + lbiteFloorPlan.strings.loadError + '</p>';
				}
			});
		},

		createTable(data, index, total) {
			// Standard-Position im Raster berechnen, falls noch keine gespeichert
			const cols = Math.max(3, Math.ceil(Math.sqrt(total)));
			const rows = Math.ceil(total / cols);
			const col = index % cols;
			const row = Math.floor(index / cols);
			const defaultX = col * (80 / cols) + 5;
			const defaultY = row * (80 / rows) + 5;

			const x     = data.x !== null ? data.x : defaultX;
			const y     = data.y !== null ? data.y : defaultY;
			const shape = data.shape || 'rect';
			const size  = data.size || 'medium';

			const el = document.createElement('div');
			el.className = `lbite-fp-table lbite-fp-table--${shape} lbite-fp-table--${size}`;
			el.dataset.tableId = data.id;
			el.style.left = x + '%';
			el.style.top  = y + '%';

			const seatsHtml = data.seats
				? `<div class="lbite-fp-table-seats">${data.seats}P</div>`
				: '';

			el.innerHTML = `
				<div class="lbite-fp-table-inner">
					<div class="lbite-fp-table-name">${this.escHtml(data.title)}</div>
					${seatsHtml}
				</div>
				<div class="lbite-fp-table-controls" aria-hidden="true">
					<button class="lbite-fp-ctrl lbite-fp-ctrl-shape" type="button" title="${lbiteFloorPlan.strings.toggleShape}">&#9680;</button>
					<button class="lbite-fp-ctrl lbite-fp-ctrl-size" type="button" title="${lbiteFloorPlan.strings.cycleSize}">&#8862;</button>
					<a class="lbite-fp-ctrl" href="${lbiteFloorPlan.editUrl.replace('%d', data.id)}" target="_blank" title="${lbiteFloorPlan.strings.editTable}">&#9998;</a>
				</div>
			`;

			el.querySelector('.lbite-fp-ctrl-shape').addEventListener('click', (e) => {
				e.stopPropagation();
				this.toggleShape(data.id);
			});
			el.querySelector('.lbite-fp-ctrl-size').addEventListener('click', (e) => {
				e.stopPropagation();
				this.cycleSize(data.id);
			});

			el.addEventListener('mousedown', (e) => {
				if (e.target.closest('.lbite-fp-ctrl')) return;
				this.startDrag(e, data.id, el);
				e.preventDefault();
			});
			el.addEventListener('touchstart', (e) => {
				if (e.target.closest('.lbite-fp-ctrl')) return;
				this.startDrag(e.touches[0], data.id, el);
			}, { passive: true });

			el.addEventListener('click', (e) => {
				if (e.target.closest('.lbite-fp-ctrl')) return;
				if (this.lastDragMoved) {
					this.lastDragMoved = false;
					return;
				}
				this.togglePopup(data.id, el);
			});

			return { id: data.id, el, x, y, shape, size, orderData: null };
		},

		startDrag(e, tableId, el) {
			const tableRect = el.getBoundingClientRect();
			this.dragState = {
				tableId,
				el,
				offsetX: e.clientX - tableRect.left,
				offsetY: e.clientY - tableRect.top,
				startX:  e.clientX,
				startY:  e.clientY,
				moved:   false
			};
			el.classList.add('lbite-fp-table--dragging');
		},

		onDrag(e) {
			if (!this.dragState) return;
			const { el, offsetX, offsetY, startX, startY } = this.dragState;

			if (Math.abs(e.clientX - startX) < 4 && Math.abs(e.clientY - startY) < 4) return;
			this.dragState.moved = true;
			this.hidePopup();

			const canvasRect = this.canvas.getBoundingClientRect();
			let x = ((e.clientX - canvasRect.left - offsetX) / canvasRect.width)  * 100;
			let y = ((e.clientY - canvasRect.top  - offsetY) / canvasRect.height) * 100;

			const maxX = Math.max(0, ((canvasRect.width  - el.offsetWidth)  / canvasRect.width)  * 100);
			const maxY = Math.max(0, ((canvasRect.height - el.offsetHeight) / canvasRect.height) * 100);

			x = Math.max(0, Math.min(x, maxX));
			y = Math.max(0, Math.min(y, maxY));

			el.style.left = x + '%';
			el.style.top  = y + '%';

			const t = this.tables.find(t => t.id == this.dragState.tableId);
			if (t) { t.x = x; t.y = y; }

			this.isDirty = true;
			document.getElementById('lbite-fp-save').disabled = false;
		},

		stopDrag() {
			if (!this.dragState) return;
			this.lastDragMoved = this.dragState.moved;
			this.dragState.el.classList.remove('lbite-fp-table--dragging');
			this.dragState = null;
		},

		toggleShape(tableId) {
			const t = this.tables.find(t => t.id == tableId);
			if (!t) return;
			t.shape = t.shape === 'rect' ? 'circle' : 'rect';
			t.el.classList.toggle('lbite-fp-table--rect',   t.shape === 'rect');
			t.el.classList.toggle('lbite-fp-table--circle', t.shape === 'circle');
			this.markDirty();
		},

		cycleSize(tableId) {
			const t = this.tables.find(t => t.id == tableId);
			if (!t) return;
			const sizes = ['small', 'medium', 'large'];
			t.el.classList.remove('lbite-fp-table--small', 'lbite-fp-table--medium', 'lbite-fp-table--large');
			t.size = sizes[(sizes.indexOf(t.size) + 1) % sizes.length];
			t.el.classList.add(`lbite-fp-table--${t.size}`);
			this.markDirty();
		},

		markDirty() {
			this.isDirty = true;
			document.getElementById('lbite-fp-save').disabled = false;
		},

		savePositions() {
			if (!this.currentLocationId) return;

			const tablesData = this.tables.map(t => ({
				id:    t.id,
				x:     parseFloat(t.x.toFixed(4)),
				y:     parseFloat(t.y.toFixed(4)),
				shape: t.shape,
				size:  t.size
			}));

			const btn      = document.getElementById('lbite-fp-save');
			const statusEl = document.getElementById('lbite-fp-status');
			btn.disabled = true;
			statusEl.textContent = lbiteFloorPlan.strings.saving;

			$.ajax({
				url:  lbiteFloorPlan.ajaxUrl,
				type: 'POST',
				data: {
					action:      'lbite_save_floor_plan_positions',
					nonce:       lbiteFloorPlan.nonce,
					location_id: this.currentLocationId,
					tables:      tablesData
				},
				success: (response) => {
					if (response.success) {
						this.isDirty = false;
						statusEl.textContent = lbiteFloorPlan.strings.saved;
						setTimeout(() => { statusEl.textContent = ''; }, 2500);
					} else {
						statusEl.textContent = lbiteFloorPlan.strings.saveError;
						btn.disabled = false;
					}
				},
				error: () => {
					statusEl.textContent = lbiteFloorPlan.strings.saveError;
					btn.disabled = false;
				}
			});
		},

		loadStatuses() {
			if (!this.currentLocationId || !this.tables.length) return;

			$.ajax({
				url:  lbiteFloorPlan.ajaxUrl,
				type: 'POST',
				data: {
					action:      'lbite_get_table_statuses',
					nonce:       lbiteFloorPlan.nonce,
					location_id: this.currentLocationId
				},
				success: (response) => {
					if (!response.success) return;
					const statuses = response.data.statuses;

					this.tables.forEach(t => {
						t.el.classList.remove(
							'lbite-fp-table--free',
							'lbite-fp-table--occupied',
							'lbite-fp-table--preparing',
							'lbite-fp-table--ready'
						);
						const s = statuses[t.id];
						if (s) {
							t.orderData = s;
							t.el.classList.add(`lbite-fp-table--${s.display_status}`);
						} else {
							t.orderData = null;
							t.el.classList.add('lbite-fp-table--free');
						}
					});
				}
			});
		},

		startStatusRefresh() {
			this.stopStatusRefresh();
			this.statusInterval = setInterval(() => this.loadStatuses(), 30000);
		},

		stopStatusRefresh() {
			if (this.statusInterval) {
				clearInterval(this.statusInterval);
				this.statusInterval = null;
			}
		},

		togglePopup(tableId, el) {
			if (this.popupTableId === tableId) {
				this.hidePopup();
			} else {
				this.showPopup(tableId, el);
			}
		},

		showPopup(tableId, el) {
			this.hidePopup();
			this.popupTableId = tableId;

			const t = this.tables.find(t => t.id == tableId);
			if (!t) return;

			const tableName = t.el.querySelector('.lbite-fp-table-name').textContent;
			const popup = document.createElement('div');
			popup.id = 'lbite-fp-popup';
			popup.className = 'lbite-fp-popup';

			if (t.orderData) {
				const d = t.orderData;
				popup.innerHTML = `
					<div class="lbite-fp-popup-header">
						<strong class="lbite-fp-popup-title">${this.escHtml(tableName)}</strong>
						<button class="lbite-fp-popup-close" type="button">&times;</button>
					</div>
					<div class="lbite-fp-popup-body">
						<div class="lbite-fp-popup-row">
							<span class="lbite-fp-popup-label">${lbiteFloorPlan.strings.order}</span>
							<a href="${lbiteFloorPlan.orderEditUrl.replace('%d', d.order_id)}" target="_blank" class="lbite-fp-popup-link">#${this.escHtml(String(d.order_number))}</a>
						</div>
						<div class="lbite-fp-popup-row">
							<span class="lbite-fp-popup-label">${lbiteFloorPlan.strings.time}</span>
							<span>${this.escHtml(d.time)}</span>
						</div>
						<div class="lbite-fp-popup-row">
							<span class="lbite-fp-popup-label">${lbiteFloorPlan.strings.items}</span>
							<span>${d.items_count}</span>
						</div>
						<div class="lbite-fp-popup-row">
							<span class="lbite-fp-popup-label">${lbiteFloorPlan.strings.total}</span>
							<span>${this.escHtml(d.total)}</span>
						</div>
						<div class="lbite-fp-popup-row">
							<span class="lbite-fp-popup-label">${lbiteFloorPlan.strings.status}</span>
							<span class="lbite-fp-popup-status lbite-fp-popup-status--${d.display_status}">${this.escHtml(d.status_label)}</span>
						</div>
						<div class="lbite-fp-popup-actions">
							<a href="${lbiteFloorPlan.orderUrl}" class="button button-small">${lbiteFloorPlan.strings.viewOrder}</a>
						</div>
					</div>
				`;
			} else {
				popup.innerHTML = `
					<div class="lbite-fp-popup-header">
						<strong class="lbite-fp-popup-title">${this.escHtml(tableName)}</strong>
						<button class="lbite-fp-popup-close" type="button">&times;</button>
					</div>
					<div class="lbite-fp-popup-body">
						<p class="lbite-fp-popup-free">${lbiteFloorPlan.strings.tableFree}</p>
					</div>
				`;
			}

			document.body.appendChild(popup);
			popup.querySelector('.lbite-fp-popup-close').addEventListener('click', () => this.hidePopup());

			// Position berechnen (neben dem Tisch-Element)
			requestAnimationFrame(() => {
				const elRect   = el.getBoundingClientRect();
				const popupW   = popup.offsetWidth;
				const popupH   = popup.offsetHeight;
				let left = elRect.right + 10;
				let top  = elRect.top;

				if (left + popupW > window.innerWidth - 10) {
					left = Math.max(10, elRect.left - popupW - 10);
				}
				if (top + popupH > window.innerHeight - 10) {
					top = Math.max(10, window.innerHeight - popupH - 10);
				}

				popup.style.left = left + 'px';
				popup.style.top  = top + 'px';
				popup.classList.add('lbite-fp-popup--visible');
			});
		},

		hidePopup() {
			const existing = document.getElementById('lbite-fp-popup');
			if (existing) existing.remove();
			this.popupTableId = null;
		},

		escHtml(text) {
			const div = document.createElement('div');
			div.appendChild(document.createTextNode(String(text)));
			return div.innerHTML;
		}
	};

	document.addEventListener('DOMContentLoaded', () => {
		if (document.getElementById('lbite-fp-canvas')) {
			FloorPlan.init();
		}
	});

})(jQuery);
