///<amd-module name="localhost/lib/base/keyboard" />

/*
Usage:
    <script src="/node_modules/keymaster/keymaster.js"></script>
    <script src="/lib/github.com/tedkulp/keymaster-sequence/keymaster.sequence.js"></script>

    declareKey('k');
    declareKey('l');
    declareKey('-', 'up dir');
    declareKey(['g', 'g'], 'begin');
    declareKey('alt+shift+,', 'begin');
    declareKey('shift+g', 'end');
    declareKey('alt+shift+.', 'end');
    declareKey('/', 'search forward');
    declareKey('?', 'search backward');
*/
/*declare global {
    interface Keymaster {
        sequence(keys: string[], fn: any): void;
    }
}*/


export function bind(k: string | string[], handler: (key: string) => any) {
    /*
    let fn: any;
    if (typeof k === 'string') {
        fn = key;
    } else {
        fn = key.sequence;
    }
    fn(k, handler);
     */
}
export {}