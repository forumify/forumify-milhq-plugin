import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['loader', 'form'];

  static values = {
    fetchUnitUri: String,
  };

  connect() {
    const form = this.element.querySelector('form');
    const unitSelect = form.querySelector('#after_action_report_unit');
    unitSelect.addEventListener('change', (e) => this._handleUnitChange(e.target.value));

    if (unitSelect.value) {
      this._handleUnitChange(unitSelect.value);
    }

    form.addEventListener('submit', this._handleFormSubmit.bind(this));
  }

  _handleUnitChange(newUnitId) {
    this._startLoading();

    fetch(this.fetchUnitUriValue.replace('id', newUnitId))
      .then((res) => res.json())
      .then((users) => this._addAttendanceToForm(users))
      .then(this._setAttendance.bind(this))
      .finally(this._endLoading.bind(this));
  }

  _addAttendanceToForm(users) {
    const oldAttendanceForm = this.element.querySelector('#attendance-form');
    if (oldAttendanceForm) {
      oldAttendanceForm.remove();
    }

    const attendanceForm = this.element.querySelector('#attendance-table-prototype').firstElementChild.cloneNode(true);
    attendanceForm.id = 'attendance-form';

    const tbody = attendanceForm.querySelector('tbody');
    const rowPrototype = tbody.querySelector('tr');
    rowPrototype.remove();

    users.forEach((user) => {
      const tr = rowPrototype.cloneNode(true);
      [...tr.querySelectorAll('input[type="radio"]')].forEach((input) => {
        input.name = input.name.replace('__ID__', user.id);
        input.dataset.userId = user.id;

        if (
          (user.rsvp === true && input.value === 'present')
          || (user.rsvp === false && input.value === 'excused')
        ) {
          input.checked = true;
        }
      });

      const nameTd = tr.firstElementChild;

      if (user.rankImage) {
        const img = document.createElement('img');
        img.width = 24;
        img.height = 24;
        img.src = user.rankImage;
        img.classList.add('mr-2');
        nameTd.append(img);
      }
      nameTd.append(user.name);
      tbody.append(tr);
    });

    const attendanceInput = this.element.querySelector('#after_action_report_attendanceJson');
    this.element.querySelector('#after_action_report').insertBefore(
      attendanceForm,
      attendanceInput,
    );
    return attendanceForm;
  }

  _setAttendance(attendanceForm) {
    const attendanceInput = this.element.querySelector('#after_action_report_attendanceJson');
    if (!attendanceInput.value) {
      return;
    }

    const attendance = JSON.parse(attendanceInput.value);
    Object.keys(attendance).forEach((state) => {
      attendance[state].forEach((userId) => {
        const input = attendanceForm.querySelector(`input[name="attendance[${userId}]"][value="${state}"]`);
        if (input !== null) {
          input.checked = true;
        }
      });
    });
  }

  _handleFormSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const attendance = {};

    [...form.querySelectorAll('#attendance-form input')]
      .filter((input) => input.checked)
      .forEach((input) => {
        if (attendance[input.value] === undefined) {
          attendance[input.value] = [];
        }
        attendance[input.value].push(input.dataset.userId);
      });

    const attendanceInput = this.element.querySelector('#after_action_report_attendanceJson');
    attendanceInput.value = JSON.stringify(attendance);

    form.submit();
  }

  _startLoading() {
    this.loaderTarget.classList.remove('d-none');
    this.formTarget.classList.add('d-none');
  }

  _endLoading() {
    this.loaderTarget.classList.add('d-none');
    this.formTarget.classList.remove('d-none');
  }
}
