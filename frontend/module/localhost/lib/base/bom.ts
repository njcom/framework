/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
///<amd-module name="localhost/lib/base/bom" />

// --------------------------------------------------------------------------
// Math

Math.EPS = 0.000001;

Math.roundFloat = function (val: number, precision: number = 2): number {
    const dd = Math.pow(10, precision);
    return Math.round(val * dd) / dd;
};
Math.floatLessThanZero = function (val: number): boolean {
    return val < -Math.EPS;
};
Math.floatGreaterThanZero = function (val: number): boolean {
    return val > Math.EPS;
};
Math.floatEqualZero = function (val: number): boolean {
    return Math.abs(val) <= Math.EPS;
};
Math.floatsEqual = function (a: number, b: number): boolean {
    return Math.floatEqualZero(a - b);
};

// E.g: logN(8, 2) ~> 3
Math.logN = function (n: number, base: number): number {
    return Math.log(n) / Math.log(base);
};

// --------------------------------------------------------------------------
// String

String.prototype.e = function (this: string): string {
    const entityMap = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        // tslint:disable-next-line:object-literal-sort-keys
        '"': '&quot;',
        "'": '&#39;'
    };
    return this.replace(/[&<>"']/g, function (s: string): string {
        return (<any>entityMap)[s];
    });
};

String.prototype.titleize = function (this: string): string {
    // @TODO
    return this.charAt(0).toUpperCase() + this.slice(1);
};

String.prototype.format = function (this: string, args: string[], filter?: (s: string) => string): string {
    let val = this;
    args.forEach((arg: string, index: number) => {
        val = val.replace('{' + index + '}', filter ? filter(arg) : arg);
    });
    return val;
}

String.prototype.nl2Br = function (): string {
    return this.replace(/\r?\n/g, '<br>');
};
String.prototype.replaceAll = function (search: string, replace: string): string {
    return this.split(search).join(replace);
};

String.prototype.ucFirst = function (this: string) {
    return this.charAt(0).toUpperCase() + this.slice(1);
};

// https://stackoverflow.com/a/41431646/13393715
String.prototype.trimR = function (this: string, chars?: string): string {
    if (chars === undefined) {
        return this.replace(new RegExp('\\s+$'), ''); // by default trim spaces
    }
    return this.replace(new RegExp("[" + RegExp.e(chars) + "]+$"), '');
};
String.prototype.trimL = function (this: string, chars?: string): string {
    if (chars === undefined) {
        return this.replace(new RegExp('^\\s+'), ''); // by default trim spaces
    }
    return this.replace(new RegExp("^[" + RegExp.e(chars) + "]+"), '');
};
String.prototype.trimLR = function (this: string, chars?: string): string {
    if (chars == undefined) {
        return this.trim();
    }
    return this.trimL(chars).trimR(chars);
}

// ----------------------------------------------------------------------------
// RegExp

// https://github.com/benjamingr/RegExp.escape/blob/master/polyfill.js
RegExp.e = function (s: string): string {
    return String(s).replace(/[\\^$*+?.()|[\]{}]/g, '\\$&');
};

// ----------------------------------------------------------------------------
// Object

export {}; // this file needs to be a module

declare global {
    interface ObjectConstructor {
        pick(o: any, keys: string[]): any;
    }
}

// https://github.com/you-dont-need/You-Dont-Need-Lodash-Underscore#_pick
Object.pick = function (object: any, keys: string[]): any {
    return keys.reduce((obj, key) => {
        if (object && object.hasOwnProperty(key)) {
            obj[key] = object[key];
        }
        return obj;
    }, <{[name: string]: any}>{});
}