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

export const exportScantheque = async() => {
    try {
        const response = await fetch('/user/export/scantheque',{
            method: "GET"
        });

        if (response.ok) {
            // Create a Blob
            const blob = await response.blob();

            // Convert your blob into a Blob URL (a special url that points to an object in the browser's memory)
            const blobUrl = URL.createObjectURL(blob);

            // Create a link element
            const link = document.createElement("a");

            // Set link's href to point to the Blob URL
            link.href = blobUrl;
            link.download = `mangasync-scantheque.csv`;

            // Append link to the body
            document.body.appendChild(link);

            // Dispatch click event on the link
            // This is necessary as link.click() does not work on the latest firefox
            link.dispatchEvent(
                new MouseEvent('click', {
                    bubbles: true,
                    cancelable: true,
                    view: window
                })
            );

            // Remove link from body
            document.body.removeChild(link);

            console.log('Success: File downloaded successfully.');
        } else {
            alert('Une erreur s\'est produite. L\'export n\'a pas pu être généré.');
            console.error('Error uploading CSV file');
        }

        return 'ok';
    } catch (error) {
        console.error("Error uploading CSV file :", error);
        return error;
    }
}


