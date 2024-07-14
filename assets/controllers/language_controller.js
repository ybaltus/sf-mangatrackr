import {Controller} from '@hotwired/stimulus';
import {useDebounce} from "stimulus-use";

export default class extends Controller {
    static debounces = ['changeLanguage'];
    static values = {pathFr: String, pathEn: String, currentLang: String};

    connect()
    {
        // Set useDebounce for this controller
        useDebounce(this, { wait: 200 });
    }

    changeLanguage(event)
    {
        const targetLang = event.target.value;
        const currentLang = event.params.currentLang;
        if (
            typeof targetLang === 'string'
            && targetLang.length === 2
            && typeof currentLang === 'string'
            && currentLang.length === 2
            && currentLang !== targetLang
        ) {
            if (
                (targetLang === 'fr' || targetLang === 'en')
                && (currentLang === 'fr' || currentLang === 'en')
            ) {
                if (targetLang === 'fr') {
                    localStorage.setItem('lang', 'fr');
                    window.location.href = this.pathFrValue;
                } else {
                    localStorage.setItem('lang', 'en');
                    window.location.href = this.pathEnValue;
                }
            }
        }

    }
}