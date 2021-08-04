import {Grid} from "../lib/base/grid";

QUnit.module('grid',  hooks => {
    let grid: Grid,
        $grid: JQuery;

    hooks.beforeEach(() => {
        $grid = $('#grid');
        grid = new Grid($grid);
    });

    hooks.afterEach(() => {
        //router.destroy();
        grid.dispose();
    });

    QUnit.test('checkAllCheckboxes() and uncheckAllCheckboxes()', assert => {
        assert.ok(grid.isActionButtonsDisabled());

        const $checkedCheckboxes = () => $grid.find('.grid__chk:checked');
        const bindHandler = (handler: () => void) => $grid.find('.grid__chk:first').on('change', handler);
/*
        assert.strictEqual($checkedCheckboxes().length, 0);
        assert.strictEqual(grid.checkedCheckboxes().length, 0);

        let onChange1Triggered = false;
        bindHandler(() => onChange1Triggered = true);

        grid.checkAllCheckboxes();

        assert.ok(onChange1Triggered);
        assert.strictEqual($checkedCheckboxes().length, 5);
        assert.strictEqual(grid.checkedCheckboxes().length, 5);

        assert.notOk(grid.isActionButtonsDisabled());

        let onChange2Triggered = false;
        bindHandler(() => onChange2Triggered = true);

        grid.uncheckAllCheckboxes();

        assert.ok(onChange2Triggered);
*/
    });
});