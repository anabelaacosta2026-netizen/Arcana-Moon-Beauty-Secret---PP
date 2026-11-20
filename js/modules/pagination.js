/**
 * Pagination class - uses createElement, never innerHTML
 */
export class Pagination {
  constructor({ items, itemsPerPage, renderFn, containerSelector, onPageChange }) {
    this.items = items;
    this.itemsPerPage = itemsPerPage;
    this.renderFn = renderFn;
    this.container = document.querySelector(containerSelector);
    this.currentPage = 1;
    this.onPageChange = onPageChange;
  }

  get totalPages() {
    return Math.ceil(this.items.length / this.itemsPerPage);
  }

  updateItems(newItems) {
    this.items = newItems;
    this.currentPage = 1;
    this.render();
  }

  getCurrentItems() {
    const start = (this.currentPage - 1) * this.itemsPerPage;
    return this.items.slice(start, start + this.itemsPerPage);
  }

  goToPage(page) {
    if (page < 1 || page > this.totalPages) return;
    this.currentPage = page;
    this.render();
    if (this.onPageChange) this.onPageChange();
  }

  render() {
    this.renderFn(this.getCurrentItems());
    this.renderControls();
  }

  renderControls() {
    if (!this.container) return;
    // Remove existing pagination
    const existing = this.container.querySelector('.pagination');
    if (existing) existing.remove();

    if (this.totalPages <= 1) return;

    const nav = document.createElement('nav');
    nav.className = 'pagination';

    // Prev button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination__btn';
    prevBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>';
    prevBtn.disabled = this.currentPage === 1;
    prevBtn.addEventListener('click', () => this.goToPage(this.currentPage - 1));
    nav.appendChild(prevBtn);

    // Page numbers
    const maxVisible = 5;
    let startPage = Math.max(1, this.currentPage - 2);
    let endPage = Math.min(this.totalPages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1) startPage = Math.max(1, endPage - maxVisible + 1);

    if (startPage > 1) {
      nav.appendChild(this._createPageBtn(1));
      if (startPage > 2) {
        const ellipsis = document.createElement('span');
        ellipsis.className = 'pagination__ellipsis';
        ellipsis.textContent = '...';
        nav.appendChild(ellipsis);
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      nav.appendChild(this._createPageBtn(i));
    }

    if (endPage < this.totalPages) {
      if (endPage < this.totalPages - 1) {
        const ellipsis = document.createElement('span');
        ellipsis.className = 'pagination__ellipsis';
        ellipsis.textContent = '...';
        nav.appendChild(ellipsis);
      }
      nav.appendChild(this._createPageBtn(this.totalPages));
    }

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination__btn';
    nextBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';
    nextBtn.disabled = this.currentPage === this.totalPages;
    nextBtn.addEventListener('click', () => this.goToPage(this.currentPage + 1));
    nav.appendChild(nextBtn);

    this.container.appendChild(nav);
  }

  _createPageBtn(page) {
    const btn = document.createElement('button');
    btn.className = 'pagination__btn' + (page === this.currentPage ? ' pagination__btn--active' : '');
    btn.textContent = page;
    btn.addEventListener('click', () => this.goToPage(page));
    return btn;
  }
}
