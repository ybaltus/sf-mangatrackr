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

    // Control elements
    const infoMangaElement = clone.querySelector('#info-manga');
    const formElement = clone.querySelector('form');
    const inputQuantityElement = formElement.querySelector('#quantity-input');
    const btnElements = formElement.querySelectorAll('button');

    // Dropdown menu
    const dropdownBtnElement = clone.querySelector('#dropdown-btn');
    const dropdownMenuElement = clone.querySelector('#dropdown-menu');
    const ulMenuElement = clone.querySelector('#dropdown-menu ul');

    // Update common values
    infoMangaElement.id = 'info-manga-' + manga.titleSlug;
    aElement.href = '/manga/' + manga.titleSlug;
    aElement.title = manga.title;
    imgElement.src = manga.urlImg;
    imgElement.alt = manga.title;
    h3Element.textContent = manga.title;

    // Update controls elements
    inputQuantityElement.id = 'quantity-input-' + manga.titleSlug;
    inputQuantityElement.value = manga.nbChaptersTrack;
    inputQuantityElement.setAttribute('titleSLug', manga.titleSlug);
    inputQuantityElement.setAttribute('statusTrack', statusTrack);
    btnElements[0].setAttribute('data-input-counter-decrement', inputQuantityElement.id);
    btnElements[1].setAttribute('data-input-counter-increment', inputQuantityElement.id);
    btnElements[0].id = 'minus-btn-' + manga.titleSlug;
    btnElements[1].id = 'plus-btn-' + manga.titleSlug;

    // Update dropdown menu
    dropdownBtnElement.setAttribute('info-manga','info-manga-' + manga.titleSlug);
    dropdownBtnElement.setAttribute('dropdown-menu','dropdown-menu-' + manga.titleSlug);
    dropdownBtnElement.id = 'dropdown-btn-' + manga.titleSlug;
    dropdownMenuElement.id = 'dropdown-menu-' + manga.titleSlug;


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

export const addMangaToLocalStorage = (mangaData, statusTrack) =>
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
    mangaItem[mangaData.titleSlug]['statusTrack'] = statusTrack;
    mangaItem[mangaData.titleSlug]['nbChaptersTrack'] = 1;
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

export const updateMangaInLocalStorage = (statusTrack, titleSlug, nbChaptersTrack) =>
{
    console.log(statusTrack, titleSlug, nbChaptersTrack);
    let mangasListStorage = getAllMangasFromLocalstorage(statusTrack);
    const mangaToUpdate = mangasListStorage[titleSlug];
    if (mangaToUpdate) {
        mangaToUpdate.nbChaptersTrack = nbChaptersTrack;
    }

    localStorage.setItem(statusTrack, JSON.stringify(mangasListStorage));
}


const removeMangaInLocalStorage = () =>
{

}