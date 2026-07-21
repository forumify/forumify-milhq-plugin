import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    const useTiersInput = this.element.querySelector('#award_useTiers');
    if (useTiersInput === null) {
      return;
    }

    useTiersInput.addEventListener('change', ({ target }) => this._showTierForm(target));
    this._showTierForm(useTiersInput);
  }

  _showTierForm({ checked }) {
    const section = this.element.querySelector('#award-tiers');
    if (checked) {
      section.classList.remove('d-none');
    } else {
      section.classList.add('d-none');
    }
  }
}
