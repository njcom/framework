/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
///<amd-module name="localhost/lib/base/jquery-ext" />

(() => {
    let uniqId: number = 0;
    $.fn.once = function (this: JQuery, fn: (key: any, value: any) => any): JQuery {
        let cssClass: string = String(uniqId++) + '-processed';
        return this.not('.' + cssClass)
            .addClass(cssClass)
            .each(fn);
    };
})();

$.resolvedPromise = function (value?: any, ...args: any[]): JQueryPromise<any> {
    return $.Deferred().resolve(value, ...args).promise();
};

$.rejectedPromise = function (value?: any, ...args: any[]): JQueryPromise<any> {
    return $.Deferred().reject(value, ...args).promise();
};
declare global {
    interface JQuery {
        uniqId: () => JQuery;
    }
}

export const __dummy = null;

// Taken from https://github.com/jquery/jquery-ui/blob/master/ui/unique-id.js
$.fn.extend({
    uniqId: (function() {
        var uuid = 0;
        return function (this: JQuery) {
            return this.each(function() {
                if (!this.id) {
                    this.id = "ui-id-" + ( ++uuid );
                }
            });
        };
    })(),

    removeUniqId: function (this: JQuery) {
        return this.each(function() {
            if (/^ui-id-\d+$/.test(this.id)) {
                $(this).removeAttr("id");
            }
        });
    }
});
