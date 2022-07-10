/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */

///<amd-module name="localhost/lib/base/grid" />

/*import {showResponseErr, Widget} from "./widget";
import {isResponseError} from "./http";
import * as widget from "./widget";
import * as $ from "jquery";
import {RestAction} from "./http";

type RowAndEntityIdResult = [JQuery, EntityId];*/

import {Widget} from "./widget";

export class Grid extends Widget {
    public checkAllCheckboxes(): void {
        this.checkboxes().prop('checked', true).trigger('change');
    }

    public uncheckAllCheckboxes(): void {
        this.checkboxes().prop('checked', false).trigger('change');
    }

    public checkedCheckboxes(): JQuery {
        return this.checkboxes(':checked');
    }

    public checkboxes(selector?: string): JQuery {
        return this.el.find('.grid__chk' + (selector || ''));
    }

    public isActionButtonDisabled(): boolean {
        const actionButtons = this.actionButtons();
        if (!actionButtons.length) {
            throw new Error("Empty action buttons");
        }
        return actionButtons.filter(':not(.disabled)').length === 0;
    }

    /*private enableActionButtons() {
        this.el.find('.action-btn').removeAttr('disabled').removeClass('disabled');
    }

    private disableActionButtons() {
        this.el.find('.action-btn').prop('disabled', true).addClass('disabled')
    }*/
    public actionButtons(): JQuery {
        return this.el.find('.grid__action-btn');
    }

    protected init(): void {
        super.init();
        this.initCheckboxes();
        this.initActionButtons();
    }

    protected bindHandlers(): void {
        super.bindHandlers();
        /*
        this.el.find('.grid__chk').on('change', function () {
            console.log(this);
        });
        */
    }

    protected unbindHandlers() {
        super.unbindHandlers();

    }

    protected initCheckboxes(): void {
        const selectAllCheckbox = this.selectAllCheckbox();
        const checkboxes = this.checkboxes();
        if (selectAllCheckbox.is(':checked') || (checkboxes.length && !checkboxes.not(selectAllCheckbox).not(':checked').length)) {
            this.checkAllCheckboxes();
        }
    }

    protected initActionButtons(): void {
        //     const $checked = this.el.find('.select-one:checked');
        //     if ($checked.length) {
        //         this.enableActionButtons();
        //     } else {
        //     }
        //         this.disableActionButtons();
    }

    protected selectAllCheckbox(): JQuery {
        return this.el.find('.grid__chk-all');
    }
}


/*
type EntityType = string

type GridConf = {
    entityType: EntityType
    deleteUri: string
    editUri: string
} & WidgetConf;

export type CrudConf = {
    entityType: EntityType
};

export function bindMainView(app: App, conf: CrudConf) {
    const createForm = app.context.createForm = new CreateForm({el: $('#create' + conf['entityType'].ucFirst() + 'Form')});
    const grid = app.context.grid = new Grid({
        el: $('#' + conf['entityType'] + 'Grid'),
        entityType: conf['entityType'],
        deleteUri: window.location.pathname + '/delete',
        editUri: window.location.pathname + '/$id/edit',
    });
    createForm.setGrid(grid);
}

class Grid<TConf extends GridConf = GridConf> extends Widget<TConf> {
    public addRow(data: any): void {
        const rowTplClone: HTMLTemplateElement = <HTMLTemplateElement>$('#' + this.conf.entityType + 'GridRowTpl')[0].cloneNode(true);
        const re = new RegExp('(' + Object.keys(data).map(key => '\\$' + key).join('|') + ')', 'g')
        rowTplClone.innerHTML = rowTplClone.innerHTML.replace(re, function (match: string) {
            const key = match.substr(1);
            return String(data[key]).e();
        });
        this.el.find('tr:last').after(rowTplClone.content);
        this.sort();
    }

    public sort(): void {
        // https://gist.github.com/bxt/914568
        const $tr = this.el.find('tbody tr').detach();
        Array.prototype.sort.call($tr, function(row1, row2) {
            const name1 = $(row1).find('.name').text();
            const name2 = $(row2).find('.name').text();
            console.log(name1, name2);
            return name1.localeCompare(name2);
        });
        $('table').append($tr);
    }



}
 */