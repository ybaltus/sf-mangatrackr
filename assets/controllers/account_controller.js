import {Controller} from '@hotwired/stimulus';
import * as userService from "./../js/user-service";

export default class extends Controller {
    static targets = ['counterDelete', 'btnDelete','btnCancelDelete', 'loadingExportScantheque'];
    deleteTimer = false;

    connect()
    {
        super.connect();
        this.deleteTimer = false;
    }

    deleteUserAccount(event )
    {
        event.preventDefault();

        if (this.counterDeleteTarget.classList.contains('hidden')) {
            this.counterDeleteTarget.classList.remove('hidden');
            this.btnDeleteTarget.classList.add('hidden');
            this.btnCancelDeleteTarget.classList.remove('hidden');
            const messageDeletionIn = event.params.deletionIn;
            const messageSeconds = event.params.seconds;

            let i = 10;

            this.deleteTimer = setInterval(() => {
                this.counterDeleteTarget.innerText = messageDeletionIn + ' ' + i + ' ' + messageSeconds;

                if (i === 0) {
                    clearInterval(this.deleteTimer);
                    this.counterDeleteTarget.classList.remove('hidden');
                    userService.deleteUserAccount();
                }

                i--;
            }, 1000);
        }
    }

    cancelDeleteUserAccount(event)
    {
        event.preventDefault();
        clearInterval(this.deleteTimer);
        this.counterDeleteTarget.innerText = '';
        this.counterDeleteTarget.classList.add('hidden');
        this.btnCancelDeleteTarget.classList.add('hidden');
        this.btnDeleteTarget.classList.remove('hidden');
    }

    exportScantheque(event)
    {
        event.preventDefault();

        if (this.loadingExportScanthequeTarget.classList.contains('hidden')) {
            this.loadingExportScanthequeTarget.classList.remove('hidden');

            userService.exportScantheque().then(result => {
                this.loadingExportScanthequeTarget.classList.add('hidden');
            });
        } else {
            this.loadingExportScanthequeTarget.classList.add('hidden');
        }
    }
}
