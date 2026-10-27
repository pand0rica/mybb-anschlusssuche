/**
 * Anschluss-Suchmaschine – connection list rows (add row, username suggestions via the core
 * endpoint xmlhttp.php?action=get_users) and confirm dialogs for delete forms.
 */
(function () {
	'use strict';

	var listCounter = 0;

	function bburl() {
		return (window.rootpath || '.') + '/xmlhttp.php';
	}

	function addRow(container) {
		var rows = container.querySelectorAll('.anschlusssuche-links-row');
		if (!rows.length) {
			return;
		}
		var clone = rows[rows.length - 1].cloneNode(true);
		clone.querySelectorAll('input').forEach(function (input) {
			input.value = '';
		});
		container.insertBefore(clone, container.querySelector('.anschlusssuche-links-add'));
		bindRow(clone);
	}

	function bindRow(row) {
		var nameInput = row.querySelector('.anschlusssuche-links-name');
		var uidInput = row.querySelector('.anschlusssuche-links-uid');
		if (!nameInput || !uidInput || nameInput.getAttribute('data-bound')) {
			return;
		}
		nameInput.setAttribute('data-bound', '1');

		var listId = 'anschlusssuche-users-' + (++listCounter);
		var datalist = document.createElement('datalist');
		datalist.id = listId;
		row.appendChild(datalist);
		nameInput.setAttribute('list', listId);

		var known = {};
		var timer = null;

		nameInput.addEventListener('input', function () {
			var value = nameInput.value;
			uidInput.value = known.hasOwnProperty(value) ? known[value] : '';
			if (timer) {
				clearTimeout(timer);
			}
			if (value.length < 2) {
				return;
			}
			timer = setTimeout(function () {
				var url = bburl() + '?action=get_users&query=' + encodeURIComponent(value);
				fetch(url, { credentials: 'same-origin' })
					.then(function (r) { return r.ok ? r.json() : []; })
					.then(function (users) {
						datalist.innerHTML = '';
						(users || []).forEach(function (user) {
							if (!user || !user.text) {
								return;
							}
							known[user.text] = user.uid;
							var opt = document.createElement('option');
							opt.value = user.text;
							datalist.appendChild(opt);
						});
						if (known.hasOwnProperty(nameInput.value)) {
							uidInput.value = known[nameInput.value];
						}
					})
					.catch(function () { /* suggestions are optional */ });
			}, 250);
		});
	}

	function init() {
		document.querySelectorAll('[data-anschlusssuche-links]').forEach(function (container) {
			container.querySelectorAll('.anschlusssuche-links-row').forEach(bindRow);
			var add = container.querySelector('.anschlusssuche-links-add');
			if (add) {
				add.addEventListener('click', function () {
					addRow(container);
				});
			}
		});
		document.querySelectorAll('form[data-anschlusssuche-confirm]').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				if (!window.confirm(form.getAttribute('data-anschlusssuche-confirm'))) {
					e.preventDefault();
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
