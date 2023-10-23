interface RegExpConstructor {
    e(s: string): string;
}

interface JQueryStatic {
    resolvedPromise(...args: any[]): JQueryPromise<any>;

    rejectedPromise(...args: any[]): JQueryPromise<any>;
}

interface JQuery {
    once(this: JQuery, fn: (key: any, value: any) => any): JQuery;
}

interface String {
    titleize(): string;
    nl2Br(): string;
    replaceAll(search: string, replace: string): string;
    e(): string;
    trimL(chars: string): string;
    trimR(chars: string): string;
    trimLR(chars: string): string;
    ucFirst(): string;
}

interface Math {
    EPS: number;

    // Returns x from base^x ~> n, e.g.: logN(8, 2) ~> 3, because 2^3 ~> 8
    logN(n: number, base: number): number;

    roundFloat(val: number, precision: number): number;

    floatLessThanZero(val: number): boolean;

    floatGreaterThanZero(val: number): boolean;

    floatEqualZero(val: number): boolean;

    floatsEqual(a: number, b: number): boolean;
}


interface JQuery {
    once(this: JQuery, fn: (key: any, value: any) => any): JQuery;
}

interface Math {
    EPS: number;
    roundFloat(val: number, precision: number): number;
    floatLessThanZero(val: number): boolean;
    floatGreaterThanZero(val: number): boolean;
    floatEqualZero(val: number): boolean;
    floatsEqual(a: number, b: number): boolean;
}

interface String {
    encodeHtml(): string;
    titleize(): string;
    format(args: string[], filter?: (s: string) => string): string;
}

interface JSONResult<TOK = any, TErr = any> {
    ok: TOK;
    err: TErr;
}

// -------------------------------------------------------
// External libraries

interface ToastifyConf {
    // Message to be displayed in the toast. Default: "Hi there!"
    text: string
    // Provide a node to be mounted inside the toast. node takes higher precedence over text. Default: body
    node: HTMLElement
    // Duration for which the toast should be displayed, -1 for permanent toast. Default to 3000.
    duration: number
    // CSS Selector on which the toast should be added. Default: body
    selector: string
    // URL to which the browser should be navigated on click of the toast.
    destination: string
    // Decides whether the destination should be opened in a new window or not. Default: false
    newWindow: boolean
    // To show the close icon or not. Default: false
    close: boolean
    // To show the toast from top or bottom. Default: 'top'
    gravity: 'top' | 'bottom'
    // To show the toast on left or right. Default: 'right'
    position: 'left' | 'right'
    // CSS background value	Sets the background color of the toast
    backgroundColor: string
    // URL of the image/icon to be shown before text.
    avatar: string
    // Ability to provide custom class name for further customization
    className: string
    // To stop timer when hovered over the toast (Only if duration is set). Default: true
    stopOnFocus: boolean
    // Invoked when the toast is dismissed
    callback: (toast: HTMLElement) => any;
    // Invoked when the toast is clicked
    onClick: Function
}

interface Toastify {
//    new (conf: ToastifyConf): Toastify;
    (conf: Partial<ToastifyConf>): Toastify;
    showToast(): any;
}
declare const Toastify: Toastify;

type EntityId = string | number;
