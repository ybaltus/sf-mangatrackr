import {Controller} from '@hotwired/stimulus';
import {useDebounce} from "stimulus-use";
import * as mangaService from './../js/manga-service'

export default class extends Controller {
    static debounces = ['_debouncedUpdateNbChapter']
    static targets = ['play', 'pause', 'archived']
    maxEntry = 30;

    // In order to save the datas for the debounced events with useDebounce()
    currentTargetForDebounce = null;
    formFieldForDebounce = null;

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

    connect()
    {
        // Set useDebounce for this controller
        useDebounce(this, { wait: 500 });
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

    updateNbChapter(event)
    {
        // Save the datas for useDebounce()
        this.formFieldForDebounce = event.params.formField;
        this.currentTargetForDebounce = event.currentTarget;

        // Call the dedicated function for the debounce
        this._debouncedUpdateNbChapter();
    }

    _debouncedUpdateNbChapter()
    {
        let currentNbChapterInputElement = null;

        if (this.formFieldForDebounce === 'btn-minus') {
            currentNbChapterInputElement = this.currentTargetForDebounce.nextElementSibling;
        } else if (this.formFieldForDebounce === 'btn-plus') {
            currentNbChapterInputElement = this.currentTargetForDebounce.previousElementSibling;
        } else {
            currentNbChapterInputElement = this.currentTargetForDebounce;
        }

        const titleSlug = currentNbChapterInputElement.getAttribute('titleSlug');
        const statusTrack = currentNbChapterInputElement.getAttribute('statusTrack');
        const nbChaptersTrack = currentNbChapterInputElement.value;

        mangaService.updateMangaInLocalStorage(statusTrack, titleSlug, nbChaptersTrack);
    }
}
