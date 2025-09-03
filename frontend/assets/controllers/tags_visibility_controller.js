import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tag', 'showMore'];
    static values = { 
        initialCount: { type: Number, default: 10 }
    };

    connect() {
        this.hideExtraTags();
    }

    hideExtraTags() {
        this.tagTargets.forEach((tag, index) => {
            if (index >= this.initialCountValue) {
                tag.classList.add('d-none');
            }
        });

        // Show button only if there are more tags than initial count
        if (this.tagTargets.length > this.initialCountValue) {
            if (this.hasShowMoreTarget) {
                this.showMoreTarget.classList.remove('d-none');
            }
        } else {
            // Hide button if not enough tags
            if (this.hasShowMoreTarget) {
                this.showMoreTarget.classList.add('d-none');
            }
        }
    }

    showAll() {
        this.tagTargets.forEach((tag) => {
            tag.classList.remove('d-none');
        });

        if (this.hasShowMoreTarget) {
            this.showMoreTarget.classList.add('d-none');
        }
    }
}