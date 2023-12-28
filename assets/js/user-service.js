const _headers = new Headers({
    "Content-Type": "application/json",
});

export const persistScanthequeDatas = async(statusTrack, mangas) => {
    try {
        const reponse = await fetch('/scantheque/smut_datas/' + statusTrack, {
            method: "POST",
            headers: _headers,
            body: JSON.stringify({'mangas': mangas}),
        });
        const result = await reponse.json();
        // console.log("Réussite :", result);
    } catch (erreur) {
        console.error("Persist datas error :", erreur);
    }
}

export const deleteMangaDatas = async(mangaToUpdate) => {
    try {
        const reponse = await fetch('/scantheque/dmut_datas/' + mangaToUpdate['mut'], {
            method: "DELETE",
            headers: _headers,
        });
        const result = await reponse.json();
        // console.log("Réussite :", result);
    } catch (erreur) {
        console.error("Persist datas error :", erreur);
    }
}

export const updateMangaDatas = async(mangaToUpdate, isUpdateStatusTrack = false) => {
    try {
        const reponse = await fetch('/scantheque/umut_datas/' + mangaToUpdate['mut'], {
            method: "PUT",
            headers: _headers,
            body: JSON.stringify({...mangaToUpdate, 'isUpdateStatusTrack': isUpdateStatusTrack}),
        });
        const result = await reponse.json();
        // console.log("Réussite :", result);
    } catch (error) {
        console.error("Persist datas error :", error);
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

export const deleteUserAccount = async() => {
    try {
        const reponse = await fetch('/user/delete-account',{
            method: "DELETE",
            headers: _headers,
        });
        const result = await reponse.json();

        if (result[0] === 'success') {
            console.log('Success: ', result[0]);
            // Redirect to home page
            window.location.href = result[1];
        } else {
            alert('Une erreur s\'est produit. Votre compte n\'a pas pu être supprimé.')
            console.error("Delete account error :", result[0]);
        }
    } catch (error) {
        console.error("Delete account error :", error);
    }
}


