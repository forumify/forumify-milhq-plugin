import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['options'];

  connect() {
    if (this.hasOptionsTarget) {
      this._initOptions();
    }
  }

  _initOptions() {
    this.optionIdx = this.optionsTarget.dataset.index;
    [...this.optionsTarget.firstElementChild.children].forEach(this._formatOption.bind(this));
  }

  addOption() {
    const prototype = this.optionsTarget.dataset.prototype;
    const row = document.createElement('div');
    row.innerHTML = prototype.replace(/__name__/g, this.optionIdx);

    this.optionsTarget.firstElementChild.append(row);
    this._formatOption(row);

    this.optionIdx++;
  }

  _formatOption(formRow) {
    const innerRow = formRow.firstElementChild;
    [...innerRow.children].forEach((c) => c.classList.add('w-100'));
    innerRow.prepend(this._createDeleteBtn(formRow));
    innerRow.classList.add('flex', 'items-center', 'gap-2');

    formRow.classList.remove('form-row');
    formRow.classList.add('mb-2');
    [...formRow.querySelectorAll('.form-row')].forEach((c) => c.classList.remove('form-row'));
  }

  _createDeleteBtn(formRow) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.classList.add('btn-link', 'btn-small', 'btn-icon');
    btn.innerHTML = '<i class="ph ph-x"></i>';

    btn.addEventListener('click', () => {
      formRow.parentElement.removeChild(formRow);
    });
    return btn;
  }
}
