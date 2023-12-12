import {Controller} from '@hotwired/stimulus';
import * as mangaService from './../js/manga-service'

export default class extends Controller {
    static targets = ['play', 'pause', 'archived']
    maxEntry = 30;

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

    // Triggered when play target is connected to the DOM
    playTargetConnected(target)
    {
        // Get all mangas
        const mangas = Object.values(mangaService.getAllMangasFromLocalstorage('play'));
        mangas.map(manga => {
            mangaService.addMangaCardElement(target, manga);
        })

        // Add number of manga
        mangaService.setNbMangaInTitle('play', mangas.length);
    }

    // Triggered when pause target is connected to the DOM
    pauseTargetConnected(target)
    {
        // Get all mangas
        const mangas = Object.values(mangaService.getAllMangasFromLocalstorage('pause'));
        mangas.map(manga => {
            mangaService.addMangaCardElement(target, manga);
        });

        // Add number of manga
        mangaService.setNbMangaInTitle('pause', mangas.length);
    }

    // Triggered when archived target is connected to the DOM
    archivedTargetConnected(target)
    {
        console.log('archivedTarget');
        console.log(target);
    }

    addToScantheque(event)
    {
        event.preventDefault();
        const mangaData = event.params.mangaData;
        const statusTrack = event.params.statusTrack;

        if (mangaData && !mangaService.getMangaFromLocalstorage(mangaData.titleSlug) && !mangaService.checkMaxEntry(statusTrack, this.maxEntry)) {
            mangaService.addMangaToLocalStorage(mangaData, statusTrack);
        }
    }
}
