import {Controller} from '@hotwired/stimulus';
import {useDebounce} from "stimulus-use";
import * as mangaService from './../js/manga-service';
import * as toastService from './../js/toast-service';
import * as userService from './../js/user-service';

export default class extends Controller {
    static debounces = ['_debouncedUpdateNbChapter'];
    static targets = ['play', 'pause', 'archived', 'step'];
    static values = {'userConnected': Boolean, 'userConfig': String}
    maxEntry = 30;
    hasManga = false;

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
            localStorage.setItem('not-started', '');
        }

        // Initialize user data
        localStorage.setItem('user-connected', this.userConnectedValue);

    }

    connect()
    {
        // Set useDebounce for this controller
        useDebounce(this, { wait: 500 });
    }

    // Triggered when play target is connected to the DOM
    playTargetConnected(targetElement)
    {
        this._initDatasByStatusTrack(targetElement, 'play');
    }

    // Triggered when pause target is connected to the DOM
    pauseTargetConnected(targetElement)
    {
        this._initDatasByStatusTrack(targetElement, 'pause');
    }

    // Triggered when archived target is connected to the DOM
    archivedTargetConnected(targetElement)
    {
        this._initDatasByStatusTrack(targetElement, 'archived');
    }

    addToScantheque(event)
    {
        event.preventDefault();

        const mangaData = event.params.mangaData;
        const statusTrack = event.params.statusTrack;
        const userConnected = this.userConnectedValue;

        if (mangaData && !mangaService.searchMangaFromLocalstorage(mangaData.titleSlug) && !mangaService.checkMaxEntry(statusTrack, this.maxEntry)) {
            mangaService.addMangaToLocalStorage(mangaData, statusTrack);

            if (userConnected) {
                userService.persistScanthequeDatas(statusTrack, [mangaData.titleSlug = {...mangaData, 'statusTrack': statusTrack, 'nbChaptersTrack': 1}]);
            }

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

        // Request confirmation before deleting
        if (newStatusTrack === 'delete' && !confirm("Voulez-vous vraiment supprimer ce manga ?")) {
            // Cancel
            return;
        }

        const mangaToUpdate = mangaService.updateStatusMangaInLocalStorage(currentStatusTrack, newStatusTrack, titleSlug, mangaCardId);

        if (this.userConnectedValue) {
            if (newStatusTrack === 'delete') {
                userService.deleteMangaDatas(mangaToUpdate);
            } else {
                userService.updateMangaDatas(mangaToUpdate, true);
            }
        }
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

        const mangaToUpdate = mangaService.updateNbChapterMangaInLocalStorage(statusTrack, titleSlug, nbChaptersTrack);

        if (this.userConnectedValue) {
            userService.updateMangaDatas(mangaToUpdate);
        }
    }

    _showStepSection()
    {
        const scanthequeMangaSections = document.querySelectorAll('.scantheque-manga-section');

        if (this.hasManga <= 0 && this.stepTarget.classList.contains('hidden')) {
            this.stepTarget.classList.remove('hidden');

            [...scanthequeMangaSections].map(sectionElement => {
                if (!sectionElement.classList.contains('hidden')) {
                    sectionElement.classList.add('hidden');
                }
            });
        } else {
            this.stepTarget.classList.add('hidden');
            [...scanthequeMangaSections].map(sectionElement => {
                if (sectionElement.classList.contains('hidden')) {
                    sectionElement.classList.remove('hidden');
                }
            });
        }
    }

    _initDatasByStatusTrack(targetElement, statusTrack)
    {
        // User stimulus values
        const userConnected = this.userConnectedValue;
        const userConfigByStatusTrack = this.userConfigValue ? JSON.parse(this.userConfigValue) : [];

        // Get all mangas
        let mangas = Object.values(mangaService.getAllMangasFromLocalstorage(statusTrack));

        if (userConnected ) {
            // User connected but no data - Persist data in DB'
            if (!userConfigByStatusTrack[statusTrack] && mangas.length > 0) {
                // Persist mangas in DB
                userService.persistScanthequeDatas(statusTrack, mangas);
            }

            // User connected with data - Update local storage
            if (userConfigByStatusTrack[statusTrack]) {
                userService.addUserConfigToLocalStorage(userConfigByStatusTrack[statusTrack], statusTrack);
                mangas = Object.values(mangaService.getAllMangasFromLocalstorage(statusTrack));
            }
        }

        // Create MangaCard elements
        mangas.map(manga => {
            mangaService.addMangaCardElement(targetElement, manga);
        });

        // Add number of manga
        mangaService.setNbMangaInTitle(statusTrack, mangas.length);

        // Show step section if no mangas
        if (!this.hasManga && mangas.length > 0) {
            this.hasManga = true;
        }
        this._showStepSection();
    }
}
