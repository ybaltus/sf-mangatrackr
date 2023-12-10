import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['play', 'pause', 'archived']
    limitEntry = 30;

    // First instantiated
    initialize()
    {
        super.initialize();

        // Initialize the status of tracker
        if (!localStorage.getItem('play')) {
            localStorage.setItem('play', '');
            localStorage.setItem('pause', '');
            localStorage.setItem('archived', '');
        }
    }

    // Trigerred when play target is connected to the DOM
    playTargetConnected(target)
    {
        // Get all mangas
        const mangas = Object.values(this._getAllMangas('play'));
        mangas.map(manga => {
            this._addMangaCardElement(target, manga);
        })

        // Add number of manga
        this._addNbMangaTitle('play', mangas.length);
    }

    // Trigerred when play target is connected to the DOM
    pauseTargetConnected(target)
    {
        // Get all mangas
        const mangas = Object.values(this._getAllMangas('pause'));
        mangas.map(manga => {
            this._addMangaCardElement(target, manga);
        })

        // Add number of manga
        this._addNbMangaTitle('pause', mangas.length);
    }

    // Trigerred when play target is connected to the DOM
    archivedTargetConnected(target)
    {
        console.log('parchivedTarget');
        console.log(target);
    }

    addToScantheque = (event) =>
    {
        event.preventDefault();
        const mangaData = event.params.mangaData;
        const statusTrack = event.params.statusTrack;

        if (mangaData && !this._getManga(mangaData.titleSlug) && !this._checkLimitEntry(statusTrack)) {
            this._addManga(mangaData, statusTrack);
        }
    }

    _addMangaCardElement(target, manga)
    {
        // Get the template element
        const template = document.getElementById('mangaCardTemplate');
        // Cloner the template
        const clone = document.importNode(template.content, true);

        // Update the values in the clone according to your target object
        const aElement = clone.querySelector('a');
        const imgElement = clone.querySelector('img');
        const h3Element = clone.querySelector('h3');

        aElement.href = '/manga/' + manga.titleSlug;
        aElement.title = manga.title;
        imgElement.src = manga.urlImg;
        imgElement.alt = manga.title;
        h3Element.textContent = manga.title;

        // Add clone to target
        target.appendChild(clone);
    }

    _addNbMangaTitle(statusTrack, nbManga)
    {
        // Get the title element
        const h2title = document.getElementById('title-' + statusTrack);
        const spanElement = h2title.querySelector('span');
        spanElement.textContent = nbManga;
    }

    _checkLimitEntry(statusTrack)
    {
        const entriesString = localStorage.getItem(statusTrack);
        const entries = entriesString ? JSON.parse(entriesString) : {} ;

        return Object.values(entries).length >= this.limitEntry;
    }

    _addManga(mangaData, statusTrack)
    {
        let mangaItem = {};
        switch (statusTrack) {
            case 'play':
            case 'pause':
            case 'archived':
                const entriesString = localStorage.getItem(statusTrack);
                mangaItem = entriesString ? JSON.parse(entriesString) : {};
                break;
            default:
                console.error('Unrecognized status.')
                return;
        }

        mangaItem[mangaData.titleSlug] = mangaData;
        localStorage.setItem(statusTrack, JSON.stringify(mangaItem));
    }

    _getManga(titleSlug, statusTrack = 'play')
    {
        const entriesString = localStorage.getItem(statusTrack);
        const entries = entriesString ? JSON.parse(entriesString) : null;

        if (!entries) {
            return;
        }

        return Object.values(entries).find(manga => manga.titleSlug === titleSlug);

    }

    _getAllMangas(statusTrack)
    {
        const entriesString = localStorage.getItem(statusTrack);
        return entriesString ? JSON.parse(entriesString) : {};
    }

    _updateManga()
    {

    }


    _removeManga()
    {

    }
}
