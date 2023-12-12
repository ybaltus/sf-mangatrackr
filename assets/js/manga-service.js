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

    // Control elements
    const formElement = clone.querySelector('form');
    const inputQuantityElement = formElement.querySelector('#quantity-input');
    const btnElements = formElement.querySelectorAll('button');

    // Update the values in the clone according to your target object
    aElement.href = '/manga/' + manga.titleSlug;
    aElement.title = manga.title;
    imgElement.src = manga.urlImg;
    imgElement.alt = manga.title;
    h3Element.textContent = manga.title;

    inputQuantityElement.id = 'quantity-input-' + manga.titleSlug;
    inputQuantityElement.value = manga.nbChaptersTrack;
    btnElements[0].setAttribute('data-input-counter-decrement', inputQuantityElement.id);
    btnElements[1].setAttribute('data-input-counter-increment', inputQuantityElement.id);
    btnElements[0].id = 'minus-btn-' + manga.titleSlug;
    btnElements[1].id = 'plus-btn-' + manga.titleSlug;

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

export const explodeData = () =>
{

}

export const getAllMangasFromLocalstorage = (statusTrack) =>
{
    const entriesString = localStorage.getItem(statusTrack);
    return entriesString ? JSON.parse(entriesString) : {};
}

export const updateMangaInLocalStorage = () =>
{

}


const removeMangaInLocalStorage = () =>
{

}