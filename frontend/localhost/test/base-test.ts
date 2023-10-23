import {isDomNode} from "../lib/base/base";

QUnit.module('base',  hooks => {
    hooks.beforeEach(() => {
    });

    hooks.afterEach(() => {
    });

    QUnit.test('isDomNode', assert => {
        assert.notOk(isDomNode(null));
        assert.notOk(isDomNode(false));
        assert.notOk(isDomNode(undefined));
        assert.notOk(isDomNode(0));
        assert.notOk(isDomNode(-1));
        assert.notOk(isDomNode(''));
        assert.notOk(isDomNode($()));

        assert.ok(isDomNode($('body')[0]));

        assert.notOk(isDomNode([]));
        assert.notOk(isDomNode({}));
        assert.notOk(isDomNode(123));
        assert.notOk(isDomNode("some"));
        assert.notOk(isDomNode({foo: 'bar'}));
    });
});