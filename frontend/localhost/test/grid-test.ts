import {Grid} from "../lib/base/grid";

QUnit.module('grid',  hooks => {
    let grid: Grid,
        $grid: JQuery;

    function mkGrid(): [JQuery, Grid] {
        const $grid = $('#grid'),
            grid = new Grid($grid);
        return [$grid, grid];
    }

    function findCheckedCheckboxes($grid: JQuery): JQuery {
        return $grid.find(':checked');
    }

    function checkAllCheckbox(): JQuery {
        return $('.grid__chk-all');
    }

    hooks.beforeEach(() => {
        [$grid, grid] = mkGrid();
    });

    hooks.afterEach(() => {
        grid.dispose();
        $grid.find('.grid__chk').prop('checked', false);
    });

    QUnit.test('Checkboxes are checked if check all checkbox is initially checked', assert => {
        const $checkAll = checkAllCheckbox();
        assert.true($checkAll.length == 1);

        $checkAll.prop('checked', true);

        assert.true(findCheckedCheckboxes($grid).length == 1);

        [$grid, grid] = mkGrid(); // this should initialize checkboxes
        assert.true(findCheckedCheckboxes($grid).length == 4);
    });

    QUnit.test('checkAllCheckboxes() and uncheckAllCheckboxes()', assert => {
        assert.ok(grid.isActionButtonDisabled());

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