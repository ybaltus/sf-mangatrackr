export const handleToastMessage = (message = '', duration= 2000) =>
{
    const toastMessageElement = document.querySelector('.toast-message');
    console.log('toastMessageElement', toastMessageElement)
    if (message !== '') {
        toastMessageElement.querySelector('div.toast-message-content').textContent = message;
    }

    if (toastMessageElement.classList.contains('hidden')) {
        toastMessageElement.classList.remove('hidden')
    }

    setTimeout(() => {
        toastMessageElement.classList.add('hidden');
    }, duration);
}