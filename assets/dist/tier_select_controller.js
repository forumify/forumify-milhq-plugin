import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    this.parentSelect = this.element.querySelector('[data-tier-parent-select]');
    this.tierSelect = this.element.querySelector('[data-tier-select]');
    this.tierRow = this.element.querySelector('[data-tier-row]');

    if (!this.parentSelect || !this.tierSelect) {
      return;
    }

    const options = [...this.tierSelect.options];
    this.placeholder = options.find((option) => option.value === '');
    this.tierOptions = options.filter((option) => option.value !== '');

    this.parentSelect.addEventListener('change', this._filterTiers.bind(this));
    this._filterTiers();
  }

  _filterTiers() {
    const selectedOption = this.parentSelect.options[this.parentSelect.selectedIndex];
    const autoAdvance = selectedOption?.dataset.autoAdvance === '1';

    const parentId = this.parentSelect.value;
    const tiers = this.tierOptions.filter((option) => option.dataset.parent === parentId);

    this.tierSelect.replaceChildren(...(this.placeholder ? [this.placeholder, ...tiers] : tiers));
    this.tierSelect.value = '';
    this.tierSelect.required = !autoAdvance && tiers.length > 0;

    if (this.tierRow) {
      this.tierRow.classList.toggle('d-none', autoAdvance || tiers.length === 0);
    }
  }
}
