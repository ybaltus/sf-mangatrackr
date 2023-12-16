import {Controller} from '@hotwired/stimulus';
import {useDebounce} from "stimulus-use";
import * as mangaService from './../js/manga-service';
import * as toastService from './../js/toast-service';

export default class extends Controller {
    static debounces = ['_debouncedUpdateNbChapter']
    static targets = ['play', 'pause', 'archived', 'step']
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

        // Show if no mangas
        this._showStepSection(mangas.length);
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

        // Show if no mangas
        this._showStepSection(mangas.length);
    }

    // Triggered when archived target is connected to the DOM
    archivedTargetConnected(target)
    {
        // Get all mangas
        const mangas = Object.values(mangaService.getAllMangasFromLocalstorage('archived'));
        mangas.map(manga => {
            mangaService.addMangaCardElement(target, manga);
        });

        // Add number of manga
        mangaService.setNbMangaInTitle('archived', mangas.length);

        // Show if no mangas
        this._showStepSection(mangas.length);
    }

    addToScantheque(event)
    {
        event.preventDefault();

        const mangaData = event.params.mangaData;
        const statusTrack = event.params.statusTrack;

        if (mangaData && !mangaService.getMangaFromLocalstorage(mangaData.titleSlug) && !mangaService.checkMaxEntry(statusTrack, this.maxEntry)) {
            mangaService.addMangaToLocalStorage(mangaData, statusTrack);
            toastService.handleToastMessage('Ajouté à la scanthèque !');
        } else {
            toastService.handleToastMessage('Existe déjà dans la scanthèque.');
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

    showMenuStatus(event)
    {
        const currentElement = event.currentTarget;
        const menuElementId = currentElement.getAttribute('dropdown-menu');
        const infoElementId = currentElement.getAttribute('info-manga');
        const menuElement = document.querySelector('#' + menuElementId);
        const infoDivElement = document.querySelector('#' + infoElementId);

        if (menuElement.classList.contains('hidden')) {
            menuElement.classList.remove('hidden');
            infoDivElement.classList.add('hidden');
        } else {
            infoDivElement.classList.remove('hidden');
            menuElement.classList.add('hidden');
        }
    }

    setStatusManga(event)
    {
        const currentElement = event.currentTarget;
        const currentStatusTrack = currentElement.getAttribute('current-status-track');
        const newStatusTrack = currentElement.getAttribute('status-track');
        const titleSlug = currentElement.getAttribute('manga-title-slug');
        const mangaCardId = currentElement.getAttribute('manga-card-id');

        mangaService.updateStatusMangaInLocalStorage(currentStatusTrack, newStatusTrack, titleSlug, mangaCardId);
    }

    /*
================================================================
Privates functions
===============================================================
*/

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

        const titleSlug = currentNbChapterInputElement.getAttribute('title-slug');
        const statusTrack = currentNbChapterInputElement.getAttribute('status-track');
        const nbChaptersTrack = currentNbChapterInputElement.value;

        mangaService.updateNbChapterMangaInLocalStorage(statusTrack, titleSlug, nbChaptersTrack);
    }

    _showStepSection(length)
    {
        if (length <= 0 && this.stepTarget.classList.contains('hidden')) {
            this.stepTarget.classList.remove('hidden')
        } else {
            this.stepTarget.classList.add('hidden');
        }
    }
}
