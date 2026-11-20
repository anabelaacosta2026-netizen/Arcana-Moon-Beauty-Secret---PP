/**
 * DOM Utilities for Arcana
 */

/**
 * Query selector shortcut
 * @param {string} selector
 * @param {ParentNode} [parent=document]
 * @returns {Element|null}
 */
export function qs(selector, parent = document) {
  return parent.querySelector(selector);
}

/**
 * Query selector all shortcut
 * @param {string} selector
 * @param {ParentNode} [parent=document]
 * @returns {NodeListOf<Element>}
 */
export function qsa(selector, parent = document) {
  return parent.querySelectorAll(selector);
}

/**
 * Debounce function
 * @param {Function} fn
 * @param {number} delay
 * @returns {Function}
 */
export function debounce(fn, delay = 300) {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

/**
 * Format price as ARS currency
 * @param {number} price
 * @returns {string}
 */
export function formatPrice(price) {
  const num = typeof price === 'number' ? price : Number(price || 0);
  return '$' + (isNaN(num) ? 0 : num).toLocaleString('es-AR');
}

/**
 * Format ISO date to display string
 * @param {string} isoString
 * @returns {string}
 */
export function formatDate(isoString) {
  const d = new Date(isoString);
  return d.toLocaleDateString('es-AR');
}

/**
 * Format ISO datetime to display string
 * @param {string} isoString
 * @returns {string}
 */
export function formatDateTime(isoString) {
  const d = new Date(isoString);
  return d.toLocaleDateString('es-AR') + ' - ' + d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Create element with attributes
 * @param {string} tag
 * @param {Object} [attrs={}]
 * @param {string} [text='']
 * @returns {HTMLElement}
 */
export function createEl(tag, attrs = {}, text = '') {
  const el = document.createElement(tag);
  for (const [key, val] of Object.entries(attrs)) {
    if (key === 'class') el.className = val;
    else if (key === 'dataset') Object.assign(el.dataset, val);
    else el.setAttribute(key, val);
  }
  if (text) el.textContent = text;
  return el;
}
