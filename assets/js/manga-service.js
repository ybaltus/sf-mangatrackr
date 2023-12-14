/*
================================================================
Publics functions
===============================================================
*/

export const addMangaCardElement = (target, manga) =>
{
    // Get the template element
    const template = document.getElementById('mangaCardTemplate');
    // Cloner the template
    const clone = document.importNode(template.content, true);

    // Common elements
    const aElement = clone.querySelector('a');
    const imgElement = clone.querySelector('img');
    const h3Element = clone.querySelector('h3');
    const statusTrack = target.getAttribute('data-scantheque-target');
    const infoMangaElement = clone.querySelector('#info-manga');

    // Dropdown menu
    const dropdownBtnElement = clone.querySelector('#dropdown-btn');
    const dropdownMenuElement = clone.querySelector('#dropdown-menu');
    const liMenuElements = clone.querySelectorAll('#dropdown-menu ul li');

    // Control elements
    const formElement = clone.querySelector('form');
    const inputQuantityElement = formElement.querySelector('#quantity-input');
    const btnElements = formElement.querySelectorAll('button');

    // Update common values
    clone.querySelector('div.grid').id = 'manga-card-' + manga.titleSlug;
    infoMangaElement.id = 'info-manga-' + manga.titleSlug;
    aElement.href = '/manga/' + manga.titleSlug;
    aElement.title = manga.title;
    imgElement.src = manga.urlImg;
    imgElement.alt = manga.title;
    h3Element.textContent = manga.title;

    // Update form/controls elements
    inputQuantityElement.id = 'quantity-input-' + manga.titleSlug;
    inputQuantityElement.value = manga.nbChaptersTrack;
    inputQuantityElement.setAttribute('title-sLug', manga.titleSlug);
    inputQuantityElement.setAttribute('status-track', statusTrack);
    btnElements[0].setAttribute('data-input-counter-decrement', inputQuantityElement.id);
    btnElements[1].setAttribute('data-input-counter-increment', inputQuantityElement.id);
    btnElements[0].id = 'minus-btn-' + manga.titleSlug;
    btnElements[1].id = 'plus-btn-' + manga.titleSlug;

    // Update dropdown elements menu
    dropdownBtnElement.setAttribute('info-manga','info-manga-' + manga.titleSlug);
    dropdownBtnElement.setAttribute('dropdown-menu','dropdown-menu-' + manga.titleSlug);
    dropdownBtnElement.id = 'dropdown-btn-' + manga.titleSlug;
    dropdownMenuElement.id = 'dropdown-menu-' + manga.titleSlug;
    [...liMenuElements].map(liElement => {
        liElement.setAttribute('manga-title-slug', manga.titleSlug);
        liElement.setAttribute('current-status-track', statusTrack);
        liElement.setAttribute('manga-card-id', 'manga-card-' + manga.titleSlug);

        if (liElement.getAttribute('status-track') !== statusTrack) {
            liElement.classList.remove('hidden');
        }
    });

    // Add clone to target
    target.appendChild(clone);
}

export const setNbMangaInTitle = (statusTrack, nbManga) =>
{
    // Get the title element
    const h2title = document.getElementById('title-' + statusTrack);
    const spanElement = h2title.querySelector('span');
    spanElement.textContent = nbManga;
}

export const checkMaxEntry = (statusTrack, maxEntry = 30) =>
{
    const entriesString = localStorage.getItem(statusTrack);
    const entries = entriesString ? JSON.parse(entriesString) : {} ;

    return Object.values(entries).length >= maxEntry;
}

export const addMangaToLocalStorage = (mangaData, statusTrack, isNew = true) =>
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
    if (isNew === true) {
        mangaItem[mangaData.titleSlug]['statusTrack'] = statusTrack;
        mangaItem[mangaData.titleSlug]['nbChaptersTrack'] = 1;
    }
    localStorage.setItem(statusTrack, JSON.stringify(mangaItem));
}

export const getMangaFromLocalstorage = (titleSlug, statusTrack = 'play') =>
{
    const entriesString = localStorage.getItem(statusTrack);
    const entries = entriesString ? JSON.parse(entriesString) : null;

    if (!entries) {
        return;
    }

    return Object.values(entries).find(manga => manga.titleSlug === titleSlug);

}

export const getAllMangasFromLocalstorage = (statusTrack) =>
{
    const entriesString = localStorage.getItem(statusTrack);
    return entriesString ? JSON.parse(entriesString) : {};
}

export const updateNbChapterMangaInLocalStorage = (statusTrack, titleSlug, nbChaptersTrack) =>
{
    let mangasListStorage = getAllMangasFromLocalstorage(statusTrack);
    const mangaToUpdate = mangasListStorage[titleSlug];
    if (mangaToUpdate) {
        mangaToUpdate.nbChaptersTrack = nbChaptersTrack;
    }

    localStorage.setItem(statusTrack, JSON.stringify(mangasListStorage));
}

export const updateStatusMangaInLocalStorage = (currentStatusTrack, newStatusTrack, titleSlug, mangaCardId) =>
{
    let currentStatusListStorage = getAllMangasFromLocalstorage(currentStatusTrack);

    // CurrentStatusTrack = delete
    if (newStatusTrack === 'delete') {
        removeMangaInLocalStorage(currentStatusTrack, titleSlug);
        _deleteMangaCardElement(mangaCardId);
        return;
    }

    // Clone and update the manga entry
    const mangaToUpdate = {...currentStatusListStorage[titleSlug]};
    if (mangaToUpdate) {
        mangaToUpdate.statusTrack = newStatusTrack;

        // Add in new localstorage
        addMangaToLocalStorage(mangaToUpdate, newStatusTrack, false);

        // Remove in current localstorage list
        removeMangaInLocalStorage(currentStatusTrack, titleSlug);

        // Move manga card element in new status section
        _moveMangaCardStatusSection(mangaCardId, currentStatusTrack, newStatusTrack);
    }
}

export const removeMangaInLocalStorage = (statusTrack, titleSlug) =>
{
    const mangasListLocalStorage =  getAllMangasFromLocalstorage(statusTrack);
    delete mangasListLocalStorage[titleSlug];
    localStorage.setItem(statusTrack, JSON.stringify(mangasListLocalStorage));
}

/*
================================================================
Privates functions
===============================================================
*/

const _moveMangaCardStatusSection = (mangaCardId, currentStatusTrack, newStatusTrack) =>
{
    // Get elements
    const currentSectionElement = document.querySelector("#scantheque-list-" + currentStatusTrack);
    const newSectionElement = document.querySelector("#scantheque-list-" + newStatusTrack);
    const mangaCardElement = document.querySelector('#' + mangaCardId);
    const hrCardElement = mangaCardElement.nextElementSibling;

    // Clone the manga card htmlElement
    const cloneMangaCard = mangaCardElement.cloneNode(true);

    // Clone the hr card htmlElement
    const cloneHrCard = hrCardElement.cloneNode(true);

    // Update the attributes for the menu
    _updateMenuAfterMove(cloneMangaCard, newStatusTrack);

    // Add the manga card in new section
    if (cloneMangaCard && newSectionElement) {
        newSectionElement.appendChild(cloneMangaCard);
        newSectionElement.appendChild(cloneHrCard);
    }

    // Remove the manga card in new section
    if (cloneMangaCard && currentSectionElement) {
        currentSectionElement.removeChild(mangaCardElement);
        hrCardElement.remove();
    }

    // Set the new length of mangas
    const nbMangasCurrentSection = _getNbMangas(currentStatusTrack);
    const nbMangasNewSection = _getNbMangas(newStatusTrack);
    if (nbMangasCurrentSection > 0) {
        setNbMangaInTitle(currentStatusTrack, nbMangasCurrentSection - 1);
    } else {
        setNbMangaInTitle(currentStatusTrack, nbMangasCurrentSection);
    }
    setNbMangaInTitle(newStatusTrack, nbMangasNewSection + 1);
}

const _deleteMangaCardElement = (mangaCardId) =>
{
    const mangaCardElement = document.querySelector('#' + mangaCardId);
    const hrCardElement = mangaCardElement.nextElementSibling;
    mangaCardElement.remove();
    hrCardElement.remove();
}
const _getNbMangas = (statusTrack) => {
    const entriesString = localStorage.getItem(statusTrack);
    return entriesString ? Object.values(JSON.parse(entriesString)).length : 0;
}

const _updateMenuAfterMove = (cloneMangaCard, newStatusTrack) =>
{
    const ulElement = cloneMangaCard.querySelector('ul');
    const liMenuElements = ulElement.querySelectorAll('ul li');
    const formElement = cloneMangaCard.querySelector('form');
    const inputQuantityElement = formElement.querySelector('input');

    // Reset the menu elements
    ulElement.parentNode.classList.add('hidden');
    ulElement.parentNode.parentNode.nextElementSibling.classList.remove('hidden');
    inputQuantityElement.setAttribute('status-track', newStatusTrack);

    // Update attributes of li elements
    [...liMenuElements].map(liElement => {
        liElement.setAttribute('current-status-track', newStatusTrack);

        if (liElement.getAttribute('status-track') !== newStatusTrack) {
            liElement.classList.remove('hidden');
        } else {
            liElement.classList.add('hidden');
        }
    });
}

