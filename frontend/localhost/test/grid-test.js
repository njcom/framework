define(["require", "exports", "localhost/lib/base/grid"], function (require, exports, grid_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    QUnit.module('grid', hooks => {
        let grid, $grid;
        hooks.beforeEach(() => {
            $grid = $('#grid');
            grid = new grid_1.Grid($grid);
        });
        hooks.afterEach(() => {
            grid.dispose();
        });
        QUnit.test('checkAllCheckboxes() and uncheckAllCheckboxes()', assert => {
            assert.ok(grid.isActionButtonsDisabled());
            const $checkedCheckboxes = () => $grid.find('.grid__chk:checked');
            const bindHandler = (handler) => $grid.find('.grid__chk:first').on('change', handler);
        });
    });
});
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZ3JpZC10ZXN0LmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiZ3JpZC10ZXN0LnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7OztJQUVBLEtBQUssQ0FBQyxNQUFNLENBQUMsTUFBTSxFQUFHLEtBQUssQ0FBQyxFQUFFO1FBQzFCLElBQUksSUFBVSxFQUNWLEtBQWEsQ0FBQztRQUVsQixLQUFLLENBQUMsVUFBVSxDQUFDLEdBQUcsRUFBRTtZQUNsQixLQUFLLEdBQUcsQ0FBQyxDQUFDLE9BQU8sQ0FBQyxDQUFDO1lBQ25CLElBQUksR0FBRyxJQUFJLFdBQUksQ0FBQyxLQUFLLENBQUMsQ0FBQztRQUMzQixDQUFDLENBQUMsQ0FBQztRQUVILEtBQUssQ0FBQyxTQUFTLENBQUMsR0FBRyxFQUFFO1lBRWpCLElBQUksQ0FBQyxPQUFPLEVBQUUsQ0FBQztRQUNuQixDQUFDLENBQUMsQ0FBQztRQUVILEtBQUssQ0FBQyxJQUFJLENBQUMsaURBQWlELEVBQUUsTUFBTSxDQUFDLEVBQUU7WUFDbkUsTUFBTSxDQUFDLEVBQUUsQ0FBQyxJQUFJLENBQUMsdUJBQXVCLEVBQUUsQ0FBQyxDQUFDO1lBRTFDLE1BQU0sa0JBQWtCLEdBQUcsR0FBRyxFQUFFLENBQUMsS0FBSyxDQUFDLElBQUksQ0FBQyxvQkFBb0IsQ0FBQyxDQUFDO1lBQ2xFLE1BQU0sV0FBVyxHQUFHLENBQUMsT0FBbUIsRUFBRSxFQUFFLENBQUMsS0FBSyxDQUFDLElBQUksQ0FBQyxrQkFBa0IsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxRQUFRLEVBQUUsT0FBTyxDQUFDLENBQUM7UUF3QnRHLENBQUMsQ0FBQyxDQUFDO0lBQ1AsQ0FBQyxDQUFDLENBQUMifQ==