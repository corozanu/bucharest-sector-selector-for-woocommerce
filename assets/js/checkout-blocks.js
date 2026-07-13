(function () {
	'use strict';

	if (typeof bsswooBlocks === 'undefined') {
		return;
	}

	var config = bsswooBlocks;
	var fieldId = config.blocksFieldId;
	var contexts = ['billing'];

	if (config.shippingEnabled) {
		contexts.push('shipping');
	}

	var syncing = {
		billing: false,
		shipping: false,
	};

	var lastSnapshot = {
		billing: '',
		shipping: '',
	};

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
	 * @return {object|null}
	 */
	function getCartSelect() {
		if (!window.wp || !wp.data) {
			return null;
		}

		return wp.data.select('wc/store/cart');
	}

	/**
	 * @return {object|null}
	 */
	function getCartDispatch() {
		if (!window.wp || !wp.data) {
			return null;
		}

		return wp.data.dispatch('wc/store/cart');
	}

	/**
	 * Get sector value from a Blocks address object.
	 *
	 * @param {object} address Address object.
	 * @return {string}
	 */
	function getSectorFromAddress(address) {
		if (!address) {
			return '';
		}

		return (address[fieldId] || '').trim();
	}

	/**
	 * Toggle Blocks checkout UI classes for city visibility/editability.
	 *
	 * @param {string} context billing|shipping.
	 * @param {boolean} isBucharest Whether Bucharest is selected.
	 * @return {void}
	 */
	function applyUiState(context, isBucharest) {
		var root = document.querySelector('.wc-block-checkout, .wp-block-woocommerce-checkout');

		if (!root) {
			return;
		}

		var hideClass =
			'billing' === context
				? 'bsswoo-blocks-hide-billing-city'
				: 'bsswoo-blocks-hide-shipping-city';
		var readonlyClass =
			'billing' === context
				? 'bsswoo-blocks-readonly-billing-city'
				: 'bsswoo-blocks-readonly-shipping-city';
		var hideCity = !!config.hideCity[context];
		var readonlyCity = !!config.readonlyCity[context];

		root.classList.toggle(hideClass, isBucharest && hideCity);
		root.classList.toggle(readonlyClass, isBucharest && !hideCity && readonlyCity);
	}

	/**
	 * Sync city in the cart store from the selected sector.
	 *
	 * @param {string} context billing|shipping.
	 * @param {object} address Address object.
	 * @return {void}
	 */
	function syncCityFromSector(context, address) {
		var dispatch = getCartDispatch();
		var select = getCartSelect();

		if (!dispatch || !select || !address) {
			return;
		}

		var sector = getSectorFromAddress(address);

		if (!sector) {
			return;
		}

		if ((address.city || '').trim() === sector) {
			return;
		}

		var current =
			'billing' === context ? select.getBillingAddress() : select.getShippingAddress();
		var update = Object.assign({}, current, {
			city: sector,
		});

		update[fieldId] = sector;
		syncing[context] = true;

		if ('billing' === context) {
			dispatch.setBillingAddress(update);
		} else {
			dispatch.setShippingAddress(update);
		}

		window.setTimeout(function () {
			syncing[context] = false;
		}, 0);
	}

	/**
	 * Clear auto-managed sector city values when leaving Bucharest.
	 *
	 * @param {string} context billing|shipping.
	 * @param {object} address Address object.
	 * @return {void}
	 */
	function maybeClearSectorCity(context, address) {
		var dispatch = getCartDispatch();
		var select = getCartSelect();

		if (!dispatch || !select || !address) {
			return;
		}

		if (!isSectorCity(address.city)) {
			return;
		}

		var current =
			'billing' === context ? select.getBillingAddress() : select.getShippingAddress();
		var update = Object.assign({}, current, {
			city: '',
		});

		syncing[context] = true;

		if ('billing' === context) {
			dispatch.setBillingAddress(update);
		} else {
			dispatch.setShippingAddress(update);
		}

		window.setTimeout(function () {
			syncing[context] = false;
		}, 0);
	}

	/**
	 * Handle address changes for one context.
	 *
	 * @param {string} context billing|shipping.
	 * @param {object} address Address object.
	 * @return {void}
	 */
	function handleAddressChange(context, address) {
		if (syncing[context]) {
			return;
		}

		var snapshot = JSON.stringify(address || {});

		if (lastSnapshot[context] === snapshot) {
			return;
		}

		lastSnapshot[context] = snapshot;

		if (!address || !isBucharestState(address.state)) {
			applyUiState(context, false);
			maybeClearSectorCity(context, address);
			return;
		}

		applyUiState(context, true);
		syncCityFromSector(context, address);
	}

	/**
	 * Subscribe to cart store updates.
	 *
	 * @return {void}
	 */
	function initSubscribe() {
		if (!window.wp || !wp.data) {
			return;
		}

		wp.data.subscribe(function () {
			var select = getCartSelect();

			if (!select) {
				return;
			}

			handleAddressChange('billing', select.getBillingAddress());

			if (config.shippingEnabled) {
				handleAddressChange('shipping', select.getShippingAddress());
			}
		});
	}

	/**
	 * Wait until WooCommerce cart store is available.
	 *
	 * @return {void}
	 */
	function waitForStores() {
		var select = getCartSelect();

		if (select) {
			initSubscribe();

			contexts.forEach(function (context) {
				var address =
					'billing' === context
						? select.getBillingAddress()
						: select.getShippingAddress();
				handleAddressChange(context, address);
			});

			return;
		}

		window.setTimeout(waitForStores, 200);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', waitForStores);
	} else {
		waitForStores();
	}
})();
