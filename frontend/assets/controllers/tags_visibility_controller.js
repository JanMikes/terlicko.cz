import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tag', 'showMore'];

    showAll() {
        // Show all hidden tags
        this.tagTargets.forEach((tag) => {
            tag.classList.remove('d-none');
        });

        // Hide the "show more" button
        if (this.hasShowMoreTarget) {
            this.showMoreTarget.classList.add('d-none');
        }
    }
}