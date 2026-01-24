import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  removeAssignment(event) {
    const toRemove = event.params.toRemove.toString();
    const input = this.element.querySelector('#user_secondaryAssignmentRecords');
    input.value = input.value.split(',').filter((v) => v !== toRemove).join(',');

    const elementToRemove = document.getElementById(`assignment-record-${toRemove}`);
    if (elementToRemove !== null) {
      elementToRemove.remove();
    }
  }
}
