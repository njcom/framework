/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
///<amd-module name="localhost/lib/base/error" />

export class Exception extends Error {
    //public stack: any;

    constructor(public message: string) {
        super(message);
        this.name = 'Exception';
        this.message = message;
        // this.stack = (<any>new Error()).stack;
    }

    public toString() {
        return this.name + ': ' + this.message;
    }
}

export class NotImplementedException extends Exception {
}

export class UnexpectedValueException extends Exception {
}
