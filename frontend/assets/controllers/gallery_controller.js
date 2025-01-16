import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item', 'button'];

    showMore() {
        this.itemTargets.forEach((item) => {
            item.classList.remove('d-none');
        });

        this.buttonTarget.classList.add('d-none');
    }
}
