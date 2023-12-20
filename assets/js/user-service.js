const _headers = new Headers({
    "Content-Type": "application/json",
});

export const persistScanthequeDatas = async(statusTrack, mangas) => {
    try {
        console.log('persistScanthequeDatas')

        const reponse = await fetch('/scantheque/smut_datas/' + statusTrack, {
            method: "POST",
            headers: _headers,
            body: JSON.stringify({'mangas': mangas}),
        });
        const resultat = await reponse.json();
        // console.log("Réussite :", resultat);
    } catch (erreur) {
        console.error("Persist datas error :", erreur);
    }
}

export const updateMangaDatas = async(mangaToUpdate, isUpdateStatusTrack = false) => {
    try {
        console.log('updateMangaDatas');

        const reponse = await fetch('/scantheque/umut_datas/' + mangaToUpdate['mut'], {
            method: "PUT",
            headers: _headers,
            body: JSON.stringify({...mangaToUpdate, 'isUpdateStatusTrack': isUpdateStatusTrack}),
        });
        const resultat = await reponse.json();
        // console.log("Réussite :", resultat);
    } catch (erreur) {
        console.error("Persist datas error :", erreur);
    }
}

export const addUserConfigToLocalStorage = (mangaDatas, statusTrack) =>
{
    switch (statusTrack) {
        case 'play':
        case 'pause':
        case 'archived':
            localStorage.removeItem(statusTrack);
            break;
        default:
            console.error('Unrecognized status.')
            return;
    }

    if (!mangaDatas || typeof mangaDatas !== 'object') {
        return {};
    }

    localStorage.setItem(statusTrack, JSON.stringify(mangaDatas));
}


