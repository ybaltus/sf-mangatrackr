export const handleToastMessage = (message = '', duration = 2000) =>
{
    const toastMessageElement = document.querySelector('.toast-message');
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