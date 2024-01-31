import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    submitSearch(event)
    {
        event.preventDefault();
        this.hiddenGallery(false, true);
    }

    hiddenGallery(event = false, isSubmit = false)
    {
        const inputVal =  event ? event.currentTarget.value : document.getElementById('searchTerm');
        let galleryTarget = document.getElementById('mangas-gallery');
        let listHtmxTarget = document.getElementById('manga-list-htmx');

        if (inputVal !== '') {
            if (isSubmit && !galleryTarget.classList.contains('hidden')) {
                galleryTarget.classList.add('hidden');
            }
            if (listHtmxTarget.classList.contains('hidden')) {
                listHtmxTarget.classList.remove('hidden');
            }
        } else {
            if (!listHtmxTarget.classList.contains('hidden')) {
                listHtmxTarget.classList.add('hidden');
            }
            if (galleryTarget.classList.contains('hidden')) {
                galleryTarget.classList.remove('hidden');
            }
        }
    }
}
