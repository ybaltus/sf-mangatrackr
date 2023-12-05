import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['searchDb', 'searchApi']
    hiddenSearch(event)
    {
        const inputVal = event.currentTarget.checked;

        if (inputVal === false) {
            this.searchApiTarget.classList.add('hidden');
            this.searchDbTarget.classList.remove('hidden');
        } else {
            this.searchDbTarget.classList.add('hidden');
            this.searchApiTarget.classList.remove('hidden');
        }
    }

    hiddenGallery(event)
    {
        const inputVal = event.currentTarget.value;

        let galleryTarget = document.getElementById('mangas-gallery');

        if (inputVal !== '') {
            if (!galleryTarget.classList.contains('hidden')) {
                galleryTarget.classList.add('hidden');
            }
        } else {
            if (galleryTarget.classList.contains('hidden')) {
                galleryTarget.classList.remove('hidden');
            }
        }
    }
}
