/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */

///<amd-module name="localhost/lib/base/widget" />

import {EventManager} from "./event-manager";
import {ResultResponse} from "./http";
import {IDisposable} from "./base";

export type WidgetConf = {
    el?: JQuery | string;
}

export abstract class Widget<TConf extends WidgetConf = WidgetConf> extends EventManager implements IDisposable {
    protected el!: JQuery;

    protected conf: TConf;

    public constructor(conf: TConf | JQuery) {
        super();
        this.conf = this.normalizeConf(conf);
        this.init();
        this.bindHandlers();
    }

    public dispose(): void {
        this.unbindHandlers();
    }

    protected init(): void {
        if (this.conf && this.conf.el) {
            this.el = $(<string>this.conf.el);
        }
    }

    protected bindHandlers(): void {
    }

    protected unbindHandlers(): void {
    }

    protected normalizeConf(conf: TConf | JQuery): TConf {
        if (<any>conf instanceof jQuery) {
            return <TConf>{el: <JQuery>conf};
        }
        return <TConf>conf;
    }
}
/*
 class ProgressBar extends Widget {

 }

 class Menu extends Widget {

 }

 class Window extends Widget {}

 class ModalWindow extends Window {

 }
 */

export function okToast(text: string): void {
    Toastify({
        text: text,
        backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
        className: "info",
    }).showToast();
}

export function errorToast(text: string | undefined = undefined): void {
    Toastify({
        text: text || 'Error.',
        backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
        className: "info",
    }).showToast();
}

export function showResponseErr(response: ResultResponse) {
    if (response.err && typeof response.err == 'string') {
        errorToast(response.err);
    } else {
        errorToast();
    }
}

