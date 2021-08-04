import {Uri} from "./uri";

class Http {
    public get(uri: Uri | string) {

    }

    public delete(uri: Uri | string) {

    }

    public head(uri: Uri | string) {

    }

    public options(uri: Uri | string) {

    }

    public patch(uri: Uri | string) {

    }

    public post(uri: Uri | string) {

    }

    public put(uri: Uri | string) {

    }
}

export enum RestAction {
    Delete = 'delete'
}

export type ResultResponse<TOk = unknown, TErr = unknown> = {ok: TOk, err: never} | {ok: never, err: TErr};

export function isResponseError(response: ResultResponse) {
    return !response.ok;
}

export function redirectToSelf(): void {
    //redirectTo(window.location.href);
    window.location.reload();
}

export function redirectToHome(): void {
    // @TODO:
    // redirectTo(uri.prependBasePath('/'));
    redirectTo('/');
}

export function redirectTo(uri: string, storePageInHistory = true): void {
    if (storePageInHistory) {
        window.location.href = uri;
    } else {
        window.location.replace(uri);
    }
}

// queryArgs() based on https://github.com/unshiftio/querystringify/blob/master/index.js
export function queryArgs(): JQuery.PlainObject {
    const decode = (input: string): string => decodeURIComponent(input.replace(/\+/g, ' '));

    const parser = /([^=?&]+)=?([^&]*)/g;
    let queryArgs: JQuery.PlainObject = {},
        part;

    while (part = parser.exec(window.location.search)) {
        let key = decode(part[1]),
            value = decode(part[2]);

        // Prevent overriding of existing properties. This ensures that build-in
        // methods like `toString` or __proto__ are not overriden by malicious
        // querystrings.
        if (key in queryArgs) {
            continue;
        }
        queryArgs[key] = value;
    }

    return queryArgs;
}