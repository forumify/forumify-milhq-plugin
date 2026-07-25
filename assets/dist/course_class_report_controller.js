import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['students', 'instructors'];

  connect() {
    this.studentIndex = parseInt(this.studentsTarget.dataset.index, 10) || 0;
    this.instructorIndex = parseInt(this.instructorsTarget.dataset.index, 10) || 0;

    this.studentsTarget.addEventListener('change', this._onStudentChange.bind(this));
    this.instructorsTarget.addEventListener('change', this._onInstructorChange.bind(this));
    this._syncAllStudentAchievements();
    this._syncAllInstructorRoles();
  }

  addStudent() {
    this._appendRows(this.studentsTarget, this.studentIndex++);
    this._syncAllStudentAchievements();
  }

  addInstructor() {
    this._appendRows(this.instructorsTarget, this.instructorIndex++);
    this._syncAllInstructorRoles();
  }

  _appendRows(tbody, index) {
    const html = tbody.dataset.prototype.replace(/__name__/g, index);
    const temp = document.createElement('tbody');
    temp.innerHTML = html;
    while (temp.firstElementChild) {
      tbody.appendChild(temp.firstElementChild);
    }
  }

  _onInstructorChange(event) {
    if (event.target.matches('[data-role="present"]')) {
      this._syncInstructorRole(event.target);
    }
  }

  _syncAllInstructorRoles() {
    this.instructorsTarget
      .querySelectorAll('[data-role="present"]')
      .forEach((checkbox) => this._syncInstructorRole(checkbox));
  }

  _syncInstructorRole(checkbox) {
    const roleCell = checkbox.closest('tr')?.querySelector('[data-role-cell]');
    if (roleCell) {
      roleCell.classList.toggle('d-none', !checkbox.checked);
    }
  }

  _onStudentChange(event) {
    if (event.target.matches('[data-role="result"]')) {
      this._syncStudentAchievements(event.target);
    }
  }

  _syncAllStudentAchievements() {
    this.studentsTarget
      .querySelectorAll('[data-role="result"]')
      .forEach((select) => this._syncStudentAchievements(select));
  }

  _syncStudentAchievements(select) {
    const row = select.closest('tr');
    const passed = select.value === 'passed';
    row?.querySelectorAll('[data-achievement-cell]').forEach((cell) => {
      cell.classList.toggle('d-none', !passed);
    });
  }

  toggleDetail(event) {
    const detail = event.currentTarget.closest('tr').nextElementSibling;
    if (detail && detail.classList.contains('milhq-report-detail')) {
      detail.classList.toggle('d-none');
    }
  }

  removeStudent(event) {
    const row = event.currentTarget.closest('tr');
    const detail = row.nextElementSibling;
    if (detail && detail.classList.contains('milhq-report-detail')) {
      detail.remove();
    }
    row.remove();
  }

  removeInstructor(event) {
    event.currentTarget.closest('tr').remove();
  }
}
