import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['instructors', 'students'];

  connect() {
    this.instructorIdx = this.instructorsTarget.dataset.index;
    this.studentIdx = this.studentsTarget.dataset.index;

    [...this.instructorsTarget.firstElementChild.children].forEach(this._addDeleteToExistingRow.bind(this));
    [...this.studentsTarget.firstElementChild.children].forEach(this._addDeleteToExistingRow.bind(this));
  }

  addInstructor() {
    const prototype = this.instructorsTarget.dataset.prototype;
    const row = document.createElement('div');
    row.classList.add('form-row', 'mt-8');
    row.innerHTML = prototype.replace(/__name__/g, this.instructorIdx);

    const deleteBtn = this._createDeleteBtn(row);
    row.prepend(deleteBtn);

    this.instructorsTarget.firstElementChild.append(row);

    document
      .getElementById(`class_result_instructors_${this.instructorIdx}_user`)
      .classList
      .remove('d-none')
    ;

    this.instructorIdx++;
  }

  addStudent() {
    const prototype = this.studentsTarget.dataset.prototype;
    const row = document.createElement('div');
    row.classList.add('form-row', 'mt-8');
    row.innerHTML = prototype.replace(/__name__/g, this.studentIdx);

    const deleteBtn = this._createDeleteBtn(row);
    row.prepend(deleteBtn);

    this.studentsTarget.firstElementChild.append(row);

    document
      .getElementById(`class_result_students_${this.studentIdx}_user`)
      .classList
      .remove('d-none')
    ;

    this.studentIdx++;
  }

  _addDeleteToExistingRow(formRow) {
    const btn = this._createDeleteBtn(formRow);
    formRow.querySelector('label.text-bold > span').append(btn);
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
