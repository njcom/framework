/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
///<amd-module name="localhost/lib/base/base" />

export interface IDisposable {
    dispose(): void;
}

export function id(value: any): any {
    return value;
}

export function lname(name: string): string {
    name = name.replace('_', '-')
    name = name.replace(/[a-z][A-Z]/, function camelizeNextCh(match: string/*match, p1, p2, p3, offset, inputString*/) {
        return match[0] + '-' + match[1].toLowerCase();
    })
    name = name.replace(/[^-A-Za-z.0-9]/, '-')
    name = name.replace(/-+/, '-')
    return name;
}

export function isPromise(val: any): boolean {
    return val && typeof val.promise === 'function';
}

// found at Jasmine Testing framework, $.isDomNode
export function isDomNode(obj: any): boolean {
    return obj && obj.nodeType > 0;
}

export function isGenerator(fn: Function): boolean {
    return (<any>fn.constructor).name === 'GeneratorFunction';
}

export class Re {
    public static readonly email = /^[^@]+@[^@]+$/;
}
/*
// @TODO: Use global Application
export const uri = new Uri();*/

export function showUnknownError(message?: string): void {
    // @TODO
    alert("Unknown error, please contact support");
}

// https://stackoverflow.com/questions/1909441/how-to-delay-the-keyup-handler-until-the-user-stops-typing/19259625
// https://github.com/dennyferra/TypeWatch/blob/master/jquery.typewatch.js
export function delayedCallback(callback: Function, waitMs: number): (this: any, ...args: any[]) => void {
    let timer: number = 0;
    return function(this: any): void {
        const self = this;
        const args = arguments;
        clearTimeout(timer); // clear the previous timer and set a new one. It will work if this function is called within the time interval [0..waitMs] and therefor the new timer will be set instead of the previous one.
        timer = window.setTimeout(function () {
            callback.apply(self, args);
        }, waitMs);
    };
}
