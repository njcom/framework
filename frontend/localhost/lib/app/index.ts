/// <amd-module name="localhost/lib/app/index" />

import {App as BaseApp} from "../base/app";

export class App extends BaseApp {
    protected bindEventHandlers(): void {
        this.bindMainMenuHandlers();
    }

    private bindMainMenuHandlers(): void {
        const uriPath = window.location.pathname;
        $('#main-menu a').each(function () {
            const $a = $(this);
            let linkUri = $a.attr('href');
            if (!linkUri) {
                return;
            }
            if (linkUri.substr(0, 1) !== '/') {
                return;
            }
            let offset = linkUri.indexOf('?');
            if (offset >= 0) {
                linkUri = linkUri.substr(0, offset);
            }
            offset = linkUri.indexOf('#');
            if (offset >= 0) {
                linkUri = linkUri.substr(0, offset);
            }
            if (linkUri === uriPath) {
                $a.addClass('active')
                $a.closest('.dropdown').find('.nav-link:first').addClass('active');
            }
        });
    }
}

window.app = new App();
