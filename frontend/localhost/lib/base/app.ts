/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
///<amd-module name="localhost/lib/base/app" />

import {PageMessenger} from "./message";

type TAppContext = Record<string, any>;

export class App {
    public context: TAppContext = {};

    public constructor() {
        this.context.pageMessenger = new PageMessenger({el: $('#page-messages')});
        this.bindEventHandlers();
    }

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

declare global {
    interface Window {
        app: App;
    }
}