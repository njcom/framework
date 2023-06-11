/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
///<amd-module name="localhost/lib/base/form" />

import {ErrorMessage, renderMessage} from "./message";
import {Widget, WidgetConf} from "./widget";
import {redirectTo} from "./http";

type ResponseErrorMessage = Pick<ErrorMessage, "text" | "args">;
export type ResponseError = ResponseErrorMessage[] | {[elName: string]: ResponseErrorMessage[]};

export class RequiredElValidator implements ElValidator {
    public static readonly EmptyValueMessage = 'This field is required';

    public validate($el: JQuery): string[] {
        if (Form.isRequiredEl($el)) {
            if (Form.elValue($el).trim().length < 1) {
                return [RequiredElValidator.EmptyValueMessage];
            }
        }
        return [];
    }
}

interface ElValidator {
    validate($el: JQuery): string[];
}

export function defaultValidators(): ElValidator[] {
    return [
        new RequiredElValidator()
    ];
}

export function validateEl($el: JQuery, validators?: ElValidator[]): string[] {
    if (!validators) {
        validators = defaultValidators();
    }
    let errors: string[] = [];
    validators.forEach(function (validator: ElValidator) {
        errors = errors.concat(validator.validate($el));
    });
    return errors;
}

export function formData($form: JQuery): JQuerySerializeArrayElement[] {
    // @TODO: see the serializeArray() method: $('form').serializeArray()?
    const data: JQuerySerializeArrayElement[] = [];
    els($form).each((index, node) => {
        const name = node.getAttribute('name');
        if (!name) {
            return;
        }
        data.push({
            name,
            value: Form.elValue($(node))
        });
    });
    return data;
}

export function forEachEl($form: JQuery, fn: ($el: JQuery, index?: number) => void | false) {
    return els($form).each(function (index: number, el: HTMLElement) {
        if (false === fn($(el), index)) {
            return false;
        }
        return undefined;
    });
}

export function els($form: JQuery): JQuery {
    return $((<any> $form[0]).elements);
}

export enum FieldType {
    Button = 'button',
    Checkbox = 'checkbox',
    File = 'file',
    Hidden = 'hidden',
    Image = 'image',
    Password = 'password',
    Radio = "radio",
    Reset = 'reset',
    Select = 'select',
    Submit = 'submit',
    Textarea = 'textarea',
    Textfield = 'text'
}

export const elChangeEvents = 'keyup blur change paste cut';

export class Form<FormConf extends WidgetConf = WidgetConf> extends Widget<FormConf> {
    public static readonly defaultInvalidCssClass: string = 'invalid';
    public skipValidation!: boolean;
    public elContainerCssClass!: string;
    public formMessageContainerCssClass!: string;
    public invalidCssClass!: string;
    protected elChangeEvents!: string;

    public static elValue($el: JQuery): any {
        if ((<any>$el.get(0))['type'] === 'checkbox') {
            return $el.is(':checked') ? 1 : 0;
        }
        return $el.val();
    }

    public static isRequiredEl($el: JQuery): boolean {
        return $el.is('[required]');
    }

    public els(): JQuery {
        return els(this.el);
    }

    public elsToValidate(): JQuery {
        return this.els().filter(function (this: HTMLElement) {
            const $el = $(this);
            return $el.is(':not(:submit)');//input[type=submit])') && $el.is(':not(button)');
        });
    }

    public validate(): boolean {
        this.removeErrors();
        let errors: Array<[JQuery, ErrorMessage[]]> = [];
        this.elsToValidate().each(function (this: HTMLElement) {
            const $el = $(this);
            const elErrors = validateEl($el);
            if (elErrors.length) {
                errors.push([$el, elErrors.map((error: string) => { return new ErrorMessage(error); })]);
            }
        });
        if (errors.length) {
            this.showErrors(errors);
            return false;
        }
        return true;
    }

    public invalidEls(): JQuery {
        const self = this;
        return this.els().filter(function (this: HTMLElement) {
            return $(this).hasClass(self.invalidCssClass);
        });
    }

    public hasErrors(): boolean {
        return this.el.hasClass(this.invalidCssClass);
    }

/*    public elHasErrors($el: JQuery): boolean {
        return $el.hasClass(this.invalidCssClass);
    }*/

    public removeErrors(): void {
        this.invalidEls().each((index: number, el: HTMLElement) => {
            this.removeElErrors($(el));
        });
        this.formMessageContainerEl().remove();
        this.el.removeClass(this.invalidCssClass);
    }

    public submit(): void {
        this.removeErrors();
        if (this.skipValidation) {
            this.send();
        } else if (this.validate()) {
            this.send();
        }
    }

    public send(): JQueryXHR {
        this.disableSubmitButtonEls();
        return this.sendFormData(this.uri(), this.formData());
    }

    /**
     * Displays either form errors or element errors or both.
     */
    public showErrors(errors: Array<ErrorMessage | [JQuery, ErrorMessage[]]>): void {
        let formErrors: ErrorMessage[] = [];
        errors.forEach((err: ErrorMessage | [JQuery, ErrorMessage[]]) => {
            if (Array.isArray(err)) {
                const [$el, elErrors] = err;
                this.showElErrors($el, elErrors);
            } else {
                formErrors.push(err);
            }
        });
        this.showFormErrors(formErrors);
        this.scrollToFirstError();
    }

    public static fieldType($field: JQuery): FieldType {
        const typeAttr = () => {
            const typeAttr = $field.attr('type');
            return typeAttr === undefined ? '' : typeAttr.toLowerCase();
        };
        let typeAttribute;
        switch ($field[0].tagName) {
            case 'INPUT':
                typeAttribute = typeAttr();
                switch (typeAttribute) {
                    case 'text':
                        return FieldType.Textfield;
                    case 'radio':
                        return FieldType.Radio;
                    case 'submit':
                        return FieldType.Submit;
                    case 'button':
                        return FieldType.Button;
                    case 'checkbox':
                        return FieldType.Checkbox;
                    case 'file':
                        return FieldType.File;
                    case 'hidden':
                        return FieldType.Hidden;
                    case 'image':
                        return FieldType.Image;
                    case 'password':
                        return FieldType.Password;
                    case 'reset':
                        return FieldType.Reset;
                }
                break;
            case 'TEXTAREA':
                return FieldType.Textarea;
            case 'SELECT':
                return FieldType.Select;
            case 'BUTTON':
                typeAttribute = typeAttr();
                if (typeAttribute === '' || typeAttribute === 'submit') {
                    return FieldType.Submit;
                }
                if (typeAttribute === 'button') {
                    return FieldType.Button;
                }
                break;
        }
        throw new Error('Unknown field type');
    }

    protected showFormErrors(errors: ErrorMessage[]): void {
        if (errors.length) {
            const rendered: string = '<div class="alert alert-error">' + errors.map(renderMessage).join("\n") + '</div>';
            this.formMessageContainerEl()
                .prepend(rendered);
        }
        this.el.addClass(this.invalidCssClass);
    }

    protected showElErrors($el: JQuery, errors: ErrorMessage[]): void {
        const invalidCssClass = this.invalidCssClass;
        $el.addClass(invalidCssClass).closest('.' + this.elContainerCssClass).addClass(invalidCssClass).addClass('has-error');
        $el.after(errors.map(renderMessage).join("\n"));
    }

    protected removeElErrors($el: JQuery): void {
        const $container = $el.removeClass(this.invalidCssClass).closest('.' + this.elContainerCssClass);
        if (!$container.find('.' + this.invalidCssClass).length) {
            $container.removeClass(this.invalidCssClass).removeClass('has-error');
        }
        $el.next('.error').remove();
    }

    protected formMessageContainerEl(): JQuery {
        const containerCssClass = this.formMessageContainerCssClass;
        let $containerEl = this.el.find('.' + containerCssClass);
        if (!$containerEl.length) {
            $containerEl = $('<div class="' + containerCssClass + '"></div>').prependTo(this.el);
        }
        return $containerEl;
    }

    protected init(): void {
        super.init();
        this.skipValidation = false;
        this.elContainerCssClass = 'form-group';
        this.formMessageContainerCssClass = 'messages';
        this.invalidCssClass = Form.defaultInvalidCssClass;
        this.elChangeEvents = elChangeEvents;
        this.el.attr('novalidate', 'novalidate');
    }

    protected bindHandlers(): void {
        this.el.on('submit', () => {
            this.submit();
            return false;
        });
        const self = this;
        this.elsToValidate().on(this.elChangeEvents, function (this: HTMLElement) {
            const $el = $(this);
            if ($el.hasClass(self.invalidCssClass)) {
                self.removeElErrors($el);
            }
        });
    }

    protected sendFormData(uri: string, requestData: any): JQueryXHR {
        const ajaxSettings = this.ajaxSettings();
        ajaxSettings.url = uri;
        ajaxSettings.data = requestData;
        return $.ajax(ajaxSettings);
    }

    protected ajaxSettings(): JQueryAjaxSettings {
        const self = this;
        return {
            beforeSend(jqXHR: JQueryXHR, settings: JQueryAjaxSettings): any {
                return self.beforeSend(jqXHR, settings);
            },
            success(data: any, textStatus: string, jqXHR: JQueryXHR): any {
                return self.ajaxSuccess(data, textStatus, jqXHR);
            },
            error(jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any {
                return self.ajaxError(jqXHR, textStatus, errorThrown);
            },
            method: this.submitMethod()
        };
    }

    protected submitMethod(): string {
        return this.el.attr('method') || 'GET';
    }

    protected beforeSend(jqXHR: JQueryXHR, settings: JQueryAjaxSettings): any {
    }

    protected ajaxSuccess(responseData: any, textStatus: string, jqXHR: JQueryXHR): any {
        this.enableSubmitButtonEls();
        this.handleResponse(responseData);
    }

    protected ajaxError(jqXHR: JQueryXHR, textStatus: string, errorThrown: string): any {
        this.enableSubmitButtonEls();
        // @TODO: Replace alert with internal method call.
        alert("AJAX error");
    }

    protected formData(): JQuerySerializeArrayElement[] {
        return formData(this.el);
    }

    protected uri(): string {
        return this.el.attr('action') || (<any>window).location.href;
    }

    protected enableSubmitButtonEls(): void {
        this.submitButtonEls().prop('disabled', false);
    }

    protected disableSubmitButtonEls(): void {
        this.submitButtonEls().prop('disabled', true);
    }

    protected submitButtonEls(): JQuery {
        return this.els().filter(function (this: HTMLElement) {
            return $(this).is(':submit');
        });
    }

    protected handleResponse(result: JSONResult): void {
        if (result.err !== undefined) {
            this.handleErrResponse(result.err);
        } else if (result.ok !== undefined) {
            this.handleOkResponse(result.ok);
        } else {
            this.invalidResponseError();
        }
    }

    protected handleOkResponse(responseData: any): any {
        if (responseData && responseData.redirect) {
            redirectTo(responseData.redirect);
            return true;
        }
    }

    protected handleErrResponse(responseData: ResponseError): void {
        if (Array.isArray(responseData)) {
            const errors = responseData.map((message: ResponseErrorMessage) => {
                return new ErrorMessage(message.text, message.args);
            });
            this.showErrors(errors);
        } else {
            this.invalidResponseError();
        }
    }

    protected invalidResponseError(): void {
        alert('Invalid response'); // @TODO
    }

    protected scrollToFirstError(): void {
        let $first = this.el.find('.error:first');
        let $container = $first.closest('.' + this.elContainerCssClass);
        if ($container.length) {
            $first = $container;
        } else {
            $container = $first.closest('.' + this.formMessageContainerCssClass);
            if ($container.length) {
                $first = $container;
            }
        }
        if (!$first.length) {
            return;
        }
        // @TODO: scroll to $first
    }
}
