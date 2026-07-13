(function ($) {
	'use strict';

	if (typeof bsswooCheckout === 'undefined') {
		return;
	}

	var config = bsswooCheckout;
	var contexts = ['billing'];

	if (config.shippingEnabled) {
		contexts.push('shipping');
	}

	/**
	 * Normalize a state value for comparison.
	 *
	 * @param {string} value State value.
	 * @return {string}
	 */
	function normalizeState(value) {
		if (!value) {
			return '';
		}

		return value
			.toString()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.toUpperCase()
			.trim()
			.replace(/\s+/g, ' ');
	}

	/**
	 * Determine whether a state value represents Bucharest.
	 *
	 * @param {string} value State value.
	 * @return {boolean}
	 */
	function isBucharestState(value) {
		var normalized = normalizeState(value);

		if (normalized === 'B' || normalized === 'BUCURESTI') {
			return true;
		}

		if (Array.isArray(config.bucharestStates)) {
			return config.bucharestStates.some(function (candidate) {
				return normalizeState(candidate) === normalized || candidate === value;
			});
		}

		return false;
	}

	/**
	 * Check whether a city value is an auto-managed sector.
	 *
	 * @param {string} value City value.
	 * @return {boolean}
	 */
	function isSectorCity(value) {
		return config.sectorValues.indexOf((value || '').trim()) !== -1;
	}

	/**
	 * Get field elements for a context.
	 *
	 * @param {string} context billing|shipping.
	 * @return {{state: JQuery, sector: JQuery, city: JQuery, sectorRow: JQuery, cityRow: JQuery}}
	 */
	function getFields(context) {
		var state = $('#' + context + '_state');
		var sector = $('#' + context + '_sector_bucuresti');
		var city = $('#' + context + '_city');

		return {
			state: state,
			sector: sector,
			city: city,
			sectorRow: sector.closest('.form-row'),
			cityRow: city.closest('.form-row')
		};
	}

	/**
	 * Apply city field visibility and editability for a context.
	 *
	 * @param {string} context billing|shipping.
	 * @param {boolean} isBucharest Whether Bucharest is selected.
	 * @return {void}
	 */
	function applyCityFieldState(context, isBucharest) {
		var fields = getFields(context);
		var hideCity = !!config.hideCity[context];
		var readonlyCity = !!config.readonlyCity[context];

		if (!fields.city.length) {
			return;
		}

		fields.cityRow.removeClass('bsswoo-city-hidden bsswoo-city-readonly');

		if (!isBucharest) {
			fields.city.prop('readonly', false).prop('disabled', false);
			return;
		}

		if (hideCity) {
			fields.cityRow.addClass('bsswoo-city-hidden');
			return;
		}

		if (readonlyCity) {
			fields.city.prop('readonly', true);
			fields.cityRow.addClass('bsswoo-city-readonly');
		} else {
			fields.city.prop('readonly', false);
		}
	}

	/**
	 * Sync sector dropdown from city when city already contains a sector value.
	 *
	 * @param {string} context billing|shipping.
	 * @return {void}
	 */
	function syncSectorFromCity(context) {
		var fields = getFields(context);
		var cityValue = (fields.city.val() || '').trim();

		if (isSectorCity(cityValue) && !fields.sector.val()) {
			fields.sector.val(cityValue);
		}
	}

	/**
	 * Update UI and values for one address context.
	 *
	 * @param {string} context billing|shipping.
	 * @return {void}
	 */
	function updateContext(context) {
		var fields = getFields(context);

		if (!fields.sector.length) {
			return;
		}

		var stateValue = fields.state.val();
		var isBucharest = isBucharestState(stateValue);

		if (isBucharest) {
			fields.sectorRow.show().removeClass('bsswoo-sector-hidden');
			syncSectorFromCity(context);

			var sectorValue = fields.sector.val();

			if (sectorValue) {
				fields.city.val(sectorValue).trigger('change');
			}

			applyCityFieldState(context, true);
			return;
		}

		fields.sectorRow.hide().addClass('bsswoo-sector-hidden');
		fields.sector.val('');

		var cityValue = (fields.city.val() || '').trim();

		if (isSectorCity(cityValue)) {
			fields.city.val('').trigger('change');
		}

		applyCityFieldState(context, false);
	}

	/**
	 * Update all configured contexts.
	 *
	 * @return {void}
	 */
	function updateAllContexts() {
		contexts.forEach(function (context) {
			updateContext(context);
		});
	}

	/**
	 * Bind event handlers.
	 *
	 * @return {void}
	 */
	function bindEvents() {
		contexts.forEach(function (context) {
			var fields = getFields(context);

			$(document.body).on('change', '#' + context + '_state', function () {
				updateContext(context);
			});

			$(document.body).on('change', '#' + context + '_sector_bucuresti', function () {
				var sectorValue = fields.sector.val();

				if (sectorValue) {
					fields.city.val(sectorValue).trigger('change');
				}
			});
		});

		$(document.body).on('updated_checkout', function () {
			updateAllContexts();
		});
	}

	$(function () {
		bindEvents();
		updateAllContexts();
	});
})(jQuery);
