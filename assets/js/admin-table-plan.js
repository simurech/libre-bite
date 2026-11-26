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
		editMode: false,

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
				// Ein Klick, der das Fenster selbst umbaut (z. B. «Bezahlen»), löst
				// den Knopf aus dem DOM – das ist kein Klick ausserhalb.
				if (!document.contains(e.target)) return;
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
				const saveBtn = document.getElementById('lbite-fp-save');
				if (saveBtn) saveBtn.disabled = true;
				document.getElementById('lbite-fp-status').textContent = '';
				this.stopStatusRefresh();
				if (this.currentLocationId) {
					this.loadTables(this.currentLocationId);
				}
			});

			const saveBtn = document.getElementById('lbite-fp-save');
			if (saveBtn) {
				saveBtn.addEventListener('click', () => this.savePositions());
			}
			document.getElementById('lbite-fp-refresh').addEventListener('click', () => this.loadStatuses());

			const editBtn = document.getElementById('lbite-fp-edit-toggle');
			if (editBtn) {
				editBtn.addEventListener('click', () => this.setEditMode(!this.editMode));
			}

			// Ungespeicherte Positionen nicht stillschweigend verlieren.
			window.addEventListener('beforeunload', (e) => {
				if (this.isDirty) {
					e.preventDefault();
					e.returnValue = lbiteFloorPlan.strings.unsaved;
				}
			});
		},

		/**
		 * Bearbeitungsmodus ein- oder ausschalten. Beim Verlassen werden offene
		 * Änderungen gespeichert, damit kein Zwischenstand verloren geht.
		 */
		setEditMode(on) {
			const editBtn = document.getElementById('lbite-fp-edit-toggle');
			const saveBtn = document.getElementById('lbite-fp-save');
			if (!editBtn) return;

			if (!on && this.isDirty) {
				this.savePositions();
			}

			this.editMode = on;
			this.hidePopup();
			this.canvas.classList.toggle('is-editing', on);
			editBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
			editBtn.classList.toggle('button-primary', on);
			editBtn.querySelector('.lbite-fp-edit-label').textContent = on ? lbiteFloorPlan.strings.doneEditing : lbiteFloorPlan.strings.editLayout;
			if (saveBtn) {
				saveBtn.hidden = !on;
			}
			document.getElementById('lbite-fp-status').textContent = on ? lbiteFloorPlan.strings.editHint : '';
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

					// Grundrissbild des Standorts als Hintergrund setzen. Ohne
					// hinterlegtes Bild bleibt die Fläche wie bisher leer.
					const planUrl = (response.data && response.data.floor_plan_url) || '';
					if (planUrl) {
						this.canvas.style.backgroundImage = 'url("' + planUrl.replace(/"/g, '%22') + '")';
						this.canvas.classList.add('has-floor-plan');
					} else {
						this.canvas.style.backgroundImage = '';
						this.canvas.classList.remove('has-floor-plan');
					}

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
				<div class="lbite-fp-table-controls">
					<button class="lbite-fp-ctrl lbite-fp-ctrl-shape" type="button" title="${lbiteFloorPlan.strings.toggleShape}" aria-label="${lbiteFloorPlan.strings.toggleShape}">&#9680;</button>
					<button class="lbite-fp-ctrl lbite-fp-ctrl-size" type="button" title="${lbiteFloorPlan.strings.cycleSize}" aria-label="${lbiteFloorPlan.strings.cycleSize}">&#8862;</button>
					<a class="lbite-fp-ctrl" href="${lbiteFloorPlan.editUrl.replace('%d', data.id)}" target="_blank" title="${lbiteFloorPlan.strings.editTable}" aria-label="${lbiteFloorPlan.strings.editTable}">&#9998;</a>
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
				if (!this.editMode || e.target.closest('.lbite-fp-ctrl')) return;
				this.startDrag(e, data.id, el);
				e.preventDefault();
			});
			el.addEventListener('touchstart', (e) => {
				if (!this.editMode || e.target.closest('.lbite-fp-ctrl')) return;
				this.startDrag(e.touches[0], data.id, el);
			}, { passive: true });

			el.addEventListener('click', (e) => {
				if (e.target.closest('.lbite-fp-ctrl')) return;
				if (this.lastDragMoved) {
					this.lastDragMoved = false;
					return;
				}
				// Im Bearbeitungsmodus geht es um die Anordnung, nicht um Bestellungen.
				if (this.editMode) return;
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

			this.markDirty();
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
			const saveBtn = document.getElementById('lbite-fp-save');
			if (saveBtn) saveBtn.disabled = false;
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
					const statuses = response.data.statuses || {};

					this.tables.forEach(t => {
						t.el.classList.remove(
							'lbite-fp-table--free',
							'lbite-fp-table--reserved',
							'lbite-fp-table--ordered',
							'lbite-fp-table--served'
						);
						const s = statuses[t.id] || null;
						t.orderData = s;
						t.el.classList.add('lbite-fp-table--' + (s ? s.state : 'free'));
						this.renderTimer(t);

						// Kellnerruf (Pro, F48): Tisch zusätzlich hervorheben, unabhängig
						// davon, ob am Tisch schon eine Bestellung offen ist.
						t.calls = (response.data.service_call_items || {})[t.id] || [];
						const calls = (response.data.service_calls || {})[t.id];
						t.el.classList.toggle('lbite-fp-table--call', !!calls);
						if (calls) {
							t.el.setAttribute('title', calls.join(', '));
						} else {
							t.el.removeAttribute('title');
						}
					});
				}
			});
		},

		/**
		 * Wartezeit auf dem Tisch: nur bei «bestellt», Stufen wie im Bestellboard.
		 */
		renderTimer(t) {
			let timer = t.el.querySelector('.lbite-fp-table-timer');
			const s = t.orderData;
			if (!s || 'ordered' !== s.state || !s.since_ts) {
				if (timer) timer.remove();
				return;
			}
			if (!timer) {
				timer = document.createElement('div');
				timer.className = 'lbite-fp-table-timer';
				t.el.querySelector('.lbite-fp-table-inner').appendChild(timer);
			}
			const minutes = Math.max(0, Math.floor((Date.now() / 1000 - s.since_ts) / 60));
			timer.textContent = lbiteFloorPlan.strings.minutes.replace('%d', minutes);
			timer.classList.toggle('is-warn', minutes >= s.warn_at && minutes < s.late_at);
			timer.classList.toggle('is-late', minutes >= s.late_at);
		},

		startStatusRefresh() {
			this.stopStatusRefresh();
			// Kein Server-Roundtrip für einen Tab, den ohnehin niemand ansieht
			// (Audit 26.09.2026, AP-21).
			this.statusInterval = setInterval(() => {
				if (document.hidden) { return; }
				this.loadStatuses();
			}, 30000);

			document.addEventListener('visibilitychange', () => {
				if (!document.hidden) {
					this.loadStatuses();
				}
			});
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

			const S = lbiteFloorPlan.strings;
			const tableName = t.el.querySelector('.lbite-fp-table-name').textContent;
			const popup = document.createElement('div');
			popup.id = 'lbite-fp-popup';
			popup.className = 'lbite-fp-popup';
			popup.setAttribute('role', 'dialog');
			popup.setAttribute('aria-label', tableName);

			let body = '';

			// Offene Kellnerrufe zuerst – sie sind der Grund, warum man hinsieht.
			(t.calls || []).forEach((call) => {
				body += `<div class="lbite-fp-popup-call">
					<span>&#128276; ${this.escHtml(call.label)}</span>
					<button type="button" class="button button-small" data-fp-action="resolve-call" data-call-id="${this.escHtml(call.id)}">${this.escHtml(S.callDone)} &#10003;</button>
				</div>`;
			});

			const s      = t.orderData || {};
			const orders = s.orders || [];
			const state  = s.state || 'free';

			// Zustand in Worten, damit die Farbe nicht die einzige Information ist.
			const stateLabels = { ordered: S.stateOrdered, served: S.stateServed, reserved: S.stateReserved };
			if (stateLabels[state]) {
				let extra = '';
				if ('ordered' === state && s.since_ts) {
					extra = ' · ' + S.minutes.replace('%d', Math.max(0, Math.floor((Date.now() / 1000 - s.since_ts) / 60)));
				}
				body += `<p class="lbite-fp-popup-state lbite-fp-popup-state--${state}">${this.escHtml(stateLabels[state] + extra)}</p>`;
			}

			if (!orders.length && 'reserved' !== state) {
				body += `<p class="lbite-fp-popup-free">${S.tableFree}</p>`;
			}

			orders.forEach((o) => {
				const items = (o.items || []).map(i =>
					`<li><span class="lbite-fp-popup-qty">${this.escHtml(i.quantity)}&times;</span> ${this.escHtml(i.name)}</li>`
				).join('');

				// Kellner ergänzen und rechnen ab – beides in der Kasse, dort gibt es
				// alle Zahlungsarten, Split und den Beleg. Den Küchenstand ändert
				// nur das Bestellboard.
				let actions = '';
				if (o.is_tab && lbiteFloorPlan.canPos) {
					const addUrl   = lbiteFloorPlan.posUrl + '&lbite_tab=' + encodeURIComponent(o.order_id) + '&lbite_tab_table=' + encodeURIComponent(tableName);
					const closeUrl = lbiteFloorPlan.posUrl + '&lbite_close_tab=' + encodeURIComponent(o.order_id);
					actions += `<a class="button button-small" href="${this.escHtml(addUrl)}">${S.addItems}</a>`;
					actions += `<a class="button button-primary button-small" href="${this.escHtml(closeUrl)}">${S.closeBill}</a>`;
					actions += `<button type="button" class="button button-small" data-fp-action="move-choose" data-order-id="${o.order_id}">${S.moveTable}</button>`;
					actions += `<button type="button" class="button-link lbite-fp-popup-walkout" data-fp-action="walkout" data-order-id="${o.order_id}">${S.walkout}</button>`;
				}

				const payLabel = o.is_paid ? S.paid : S.unpaid;
				body += `<section class="lbite-fp-popup-order" data-order-id="${o.order_id}">
					<div class="lbite-fp-popup-row">
						<a href="${lbiteFloorPlan.orderEditUrl.replace('%d', o.order_id)}" target="_blank" class="lbite-fp-popup-link">${o.is_tab ? this.escHtml(S.tab) + ' ' : ''}#${this.escHtml(String(o.order_number))}</a>
						<span class="lbite-fp-popup-meta">${this.escHtml(o.time)}${o.board_label ? ' · ' + this.escHtml(o.board_label) : ''}</span>
					</div>
					<ul class="lbite-fp-popup-items">${items}</ul>
					<div class="lbite-fp-popup-row lbite-fp-popup-total">
						<span class="lbite-fp-popup-label">${S.total} · ${this.escHtml(payLabel)}</span>
						<strong>${this.escHtml(o.total)}</strong>
					</div>
					${actions ? `<div class="lbite-fp-popup-actions">${actions}</div>` : ''}
				</section>`;
			});

			if (orders.some(o => !o.delivered)) {
				body += `<p class="lbite-fp-popup-hint">${S.kitchenHint}</p>`;
			}

			// Reservierungen von heute an diesem Tisch.
			const reservations = s.reservations || [];
			if (reservations.length) {
				body += `<section class="lbite-fp-popup-res"><p class="lbite-fp-popup-label">${S.reservations}</p>`;
				reservations.forEach((r) => {
					const flags = [];
					if ('pending' === r.status) flags.push(S.pendingReq);
					if (r.is_late) flags.push(S.late);

					let resActions = '';
					if (lbiteFloorPlan.canReserve) {
						if ('pending' === r.status) {
							resActions += `<button type="button" class="button button-primary button-small" data-fp-action="res-status" data-res-id="${r.id}" data-status="confirmed">${S.accept}</button>`;
							resActions += `<button type="button" class="button button-small" data-fp-action="res-status" data-res-id="${r.id}" data-status="cancelled" data-confirm="decline">${S.decline}</button>`;
						} else if ('confirmed' === r.status) {
							resActions += `<button type="button" class="button button-primary button-small" data-fp-action="res-status" data-res-id="${r.id}" data-status="completed">${S.seated}</button>`;
							resActions += `<button type="button" class="button button-small" data-fp-action="res-status" data-res-id="${r.id}" data-status="no_show" data-confirm="noshow">${S.noShow}</button>`;
						}
					}

					body += `<div class="lbite-fp-popup-res-item">
						<div class="lbite-fp-popup-row">
							<strong>${this.escHtml(r.time)} · ${this.escHtml(r.name)}</strong>
							<span class="lbite-fp-popup-meta">${this.escHtml(S.guests.replace('%d', r.guests))}${flags.length ? ' · ' + this.escHtml(flags.join(' · ')) : ''}</span>
						</div>
						${r.notes ? `<p class="lbite-fp-popup-note">${this.escHtml(r.notes)}</p>` : ''}
						${resActions ? `<div class="lbite-fp-popup-actions">${resActions}</div>` : ''}
					</div>`;
				});
				body += '</section>';
			}

			popup.innerHTML = `
				<div class="lbite-fp-popup-header">
					<strong class="lbite-fp-popup-title">${this.escHtml(tableName)}</strong>
					<button class="lbite-fp-popup-close" type="button" aria-label="${this.escHtml(S.back)}">&times;</button>
				</div>
				<div class="lbite-fp-popup-body">${body}</div>
				<p class="lbite-fp-popup-message" role="status"></p>
			`;

			document.body.appendChild(popup);
			popup.querySelector('.lbite-fp-popup-close').addEventListener('click', () => this.hidePopup());
			popup.addEventListener('click', (e) => this.onPopupAction(e, t, popup));

			this.positionPopup(popup, el);
		},

		positionPopup(popup, el) {
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

		/**
		 * Klicks auf Aktionen im Tischfenster.
		 */
		onPopupAction(e, t, popup) {
			const btn = e.target.closest('[data-fp-action]');
			if (!btn) return;

			const S       = lbiteFloorPlan.strings;
			const action  = btn.dataset.fpAction;
			const orderId = btn.dataset.orderId;

			if ('move-choose' === action) {
				this.showMoveChoice(btn.closest('.lbite-fp-popup-order'), orderId, t);
				return;
			}
			if ('move-back' === action) {
				this.showPopup(t.id, t.el);
				return;
			}
			if ('walkout' === action && !window.confirm(S.confirmWalkout)) {
				return;
			}
			if ('res-status' === action) {
				const confirmText = { decline: S.confirmDecline, noshow: S.confirmNoShow }[btn.dataset.confirm];
				if (confirmText && !window.confirm(confirmText)) {
					return;
				}
			}

			btn.disabled = true;
			let request;
			if ('move-confirm' === action) {
				const select = popup.querySelector('.lbite-fp-move-select');
				request = this.posRequest('lbite_pos_move_tab', { order_id: orderId, table_id: select ? select.value : 0 });
			} else if ('walkout' === action) {
				request = this.posRequest('lbite_pos_tab_walkout', { order_id: orderId });
			} else if ('res-status' === action) {
				request = this.ajaxRequest({
					action: 'lbite_update_reservation_status',
					nonce: lbiteFloorPlan.resNonce,
					reservation_id: btn.dataset.resId,
					status: btn.dataset.status
				});
			} else if ('resolve-call' === action) {
				request = this.restRequest('service-calls/' + encodeURIComponent(btn.dataset.callId) + '/resolve', {});
			} else {
				return;
			}

			request.then(() => {
				this.hidePopup();
				this.loadStatuses();
			}).catch((message) => {
				btn.disabled = false;
				const msg = popup.querySelector('.lbite-fp-popup-message');
				if (msg) msg.textContent = message || S.actionError;
			});
		},

		/**
		 * Tisch wechseln: Zieltisch wählen. Belegte Tische (offene Bestellung)
		 * stehen am Ende, der Server lehnt einen Tisch mit eigenem Tab ab.
		 */
		showMoveChoice(section, orderId, t) {
			const S = lbiteFloorPlan.strings;
			const options = this.tables
				.filter(other => other.id != t.id)
				.map(other => {
					const name  = other.el.querySelector('.lbite-fp-table-name').textContent;
					const state = other.orderData ? other.orderData.state : 'free';
					return { id: other.id, name, busy: 'ordered' === state || 'served' === state };
				})
				.sort((a, b) => (a.busy - b.busy) || a.name.localeCompare(b.name, undefined, { numeric: true }))
				.map(o => `<option value="${this.escHtml(o.id)}">${this.escHtml(o.name)}${o.busy ? ' (' + this.escHtml(S.stateOrdered) + ')' : ''}</option>`)
				.join('');

			section.querySelector('.lbite-fp-popup-actions').innerHTML = `
				<label class="lbite-fp-popup-label" for="lbite-fp-move-select">${S.chooseTable}</label>
				<select id="lbite-fp-move-select" class="lbite-fp-move-select">${options}</select>
				<div class="lbite-fp-pay-foot">
					<button type="button" class="button-link" data-fp-action="move-back">${S.back}</button>
					<button type="button" class="button button-primary button-small" data-fp-action="move-confirm" data-order-id="${orderId}">${S.moveTable}</button>
				</div>`;
		},

		posRequest(action, data) {
			return this.ajaxRequest(Object.assign({ action: action, nonce: lbiteFloorPlan.posNonce, location_id: this.currentLocationId }, data));
		},

		ajaxRequest(data) {
			return new Promise((resolve, reject) => {
				$.ajax({
					url: lbiteFloorPlan.ajaxUrl,
					type: 'POST',
					data: data,
					success: (r) => (r && r.success ? resolve(r.data) : reject(r && r.data && r.data.message)),
					error: () => reject()
				});
			});
		},

		restRequest(path, body) {
			return fetch(lbiteFloorPlan.restUrl + path, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': lbiteFloorPlan.restNonce },
				body: JSON.stringify(body)
			}).then((r) => r.json().then((json) => {
				if (!r.ok) {
					throw (json && json.message) || '';
				}
				return json;
			}));
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
