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
        console.log('playTarget');
        console.log(target);
    }

    // Trigerred when play target is connected to the DOM
    pauseTargetConnected(target)
    {
        console.log('pauseTarget');
        console.log(target);
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

    _getAllMangas()
    {

    }

    _updateManga()
    {

    }


    _removeManga()
    {

    }
}
