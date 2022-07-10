define(["require", "exports", "localhost/lib/base/grid"], function (require, exports, grid_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    QUnit.module('grid', hooks => {
        let grid, $grid;
        function mkGrid() {
            const $grid = $('#grid'), grid = new grid_1.Grid($grid);
            return [$grid, grid];
        }
        function findCheckedCheckboxes($grid) {
            return $grid.find(':checked');
        }
        function checkAllCheckbox() {
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
            [$grid, grid] = mkGrid();
            assert.true(findCheckedCheckboxes($grid).length == 4);
        });
        QUnit.test('checkAllCheckboxes() and uncheckAllCheckboxes()', assert => {
            assert.ok(grid.isActionButtonDisabled());
            const $checkedCheckboxes = () => $grid.find('.grid__chk:checked');
            const bindHandler = (handler) => $grid.find('.grid__chk:first').on('change', handler);
        });
    });
});
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZ3JpZC10ZXN0LmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiZ3JpZC10ZXN0LnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7OztJQUVBLEtBQUssQ0FBQyxNQUFNLENBQUMsTUFBTSxFQUFHLEtBQUssQ0FBQyxFQUFFO1FBQzFCLElBQUksSUFBVSxFQUNWLEtBQWEsQ0FBQztRQUVsQixTQUFTLE1BQU07WUFDWCxNQUFNLEtBQUssR0FBRyxDQUFDLENBQUMsT0FBTyxDQUFDLEVBQ3BCLElBQUksR0FBRyxJQUFJLFdBQUksQ0FBQyxLQUFLLENBQUMsQ0FBQztZQUMzQixPQUFPLENBQUMsS0FBSyxFQUFFLElBQUksQ0FBQyxDQUFDO1FBQ3pCLENBQUM7UUFFRCxTQUFTLHFCQUFxQixDQUFDLEtBQWE7WUFDeEMsT0FBTyxLQUFLLENBQUMsSUFBSSxDQUFDLFVBQVUsQ0FBQyxDQUFDO1FBQ2xDLENBQUM7UUFFRCxTQUFTLGdCQUFnQjtZQUNyQixPQUFPLENBQUMsQ0FBQyxnQkFBZ0IsQ0FBQyxDQUFDO1FBQy9CLENBQUM7UUFFRCxLQUFLLENBQUMsVUFBVSxDQUFDLEdBQUcsRUFBRTtZQUNsQixDQUFDLEtBQUssRUFBRSxJQUFJLENBQUMsR0FBRyxNQUFNLEVBQUUsQ0FBQztRQUM3QixDQUFDLENBQUMsQ0FBQztRQUVILEtBQUssQ0FBQyxTQUFTLENBQUMsR0FBRyxFQUFFO1lBQ2pCLElBQUksQ0FBQyxPQUFPLEVBQUUsQ0FBQztZQUNmLEtBQUssQ0FBQyxJQUFJLENBQUMsWUFBWSxDQUFDLENBQUMsSUFBSSxDQUFDLFNBQVMsRUFBRSxLQUFLLENBQUMsQ0FBQztRQUNwRCxDQUFDLENBQUMsQ0FBQztRQUVILEtBQUssQ0FBQyxJQUFJLENBQUMsbUVBQW1FLEVBQUUsTUFBTSxDQUFDLEVBQUU7WUFDckYsTUFBTSxTQUFTLEdBQUcsZ0JBQWdCLEVBQUUsQ0FBQztZQUNyQyxNQUFNLENBQUMsSUFBSSxDQUFDLFNBQVMsQ0FBQyxNQUFNLElBQUksQ0FBQyxDQUFDLENBQUM7WUFFbkMsU0FBUyxDQUFDLElBQUksQ0FBQyxTQUFTLEVBQUUsSUFBSSxDQUFDLENBQUM7WUFFaEMsTUFBTSxDQUFDLElBQUksQ0FBQyxxQkFBcUIsQ0FBQyxLQUFLLENBQUMsQ0FBQyxNQUFNLElBQUksQ0FBQyxDQUFDLENBQUM7WUFFdEQsQ0FBQyxLQUFLLEVBQUUsSUFBSSxDQUFDLEdBQUcsTUFBTSxFQUFFLENBQUM7WUFDekIsTUFBTSxDQUFDLElBQUksQ0FBQyxxQkFBcUIsQ0FBQyxLQUFLLENBQUMsQ0FBQyxNQUFNLElBQUksQ0FBQyxDQUFDLENBQUM7UUFDMUQsQ0FBQyxDQUFDLENBQUM7UUFFSCxLQUFLLENBQUMsSUFBSSxDQUFDLGlEQUFpRCxFQUFFLE1BQU0sQ0FBQyxFQUFFO1lBQ25FLE1BQU0sQ0FBQyxFQUFFLENBQUMsSUFBSSxDQUFDLHNCQUFzQixFQUFFLENBQUMsQ0FBQztZQUV6QyxNQUFNLGtCQUFrQixHQUFHLEdBQUcsRUFBRSxDQUFDLEtBQUssQ0FBQyxJQUFJLENBQUMsb0JBQW9CLENBQUMsQ0FBQztZQUNsRSxNQUFNLFdBQVcsR0FBRyxDQUFDLE9BQW1CLEVBQUUsRUFBRSxDQUFDLEtBQUssQ0FBQyxJQUFJLENBQUMsa0JBQWtCLENBQUMsQ0FBQyxFQUFFLENBQUMsUUFBUSxFQUFFLE9BQU8sQ0FBQyxDQUFDO1FBdUJ0RyxDQUFDLENBQUMsQ0FBQztJQUNQLENBQUMsQ0FBQyxDQUFDIn0=