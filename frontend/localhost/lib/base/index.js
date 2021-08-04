define("localhost/lib/base/event-manager", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.EventManager = void 0;
    class EventManager {
        constructor() {
            Object.defineProperty(this, "handlers", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: {}
            });
        }
        on(eventName, handler) {
            this.handlers[eventName] = this.handlers[eventName] || [];
            this.handlers[eventName].push(handler);
        }
        trigger(eventName, ...args) {
            let handlers = this.handlers[eventName];
            if (!handlers) {
                return;
            }
            for (let i = 0; i < handlers.length; ++i) {
                if (false === handlers[i](...args)) {
                    break;
                }
            }
        }
    }
    exports.EventManager = EventManager;
});
define("localhost/lib/base/uri", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.Uri = void 0;
    class Uri {
    }
    exports.Uri = Uri;
});
define("localhost/lib/base/http", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.queryArgs = exports.redirectTo = exports.redirectToHome = exports.redirectToSelf = exports.isResponseError = exports.RestAction = void 0;
    class Http {
        get(uri) {
        }
        delete(uri) {
        }
        head(uri) {
        }
        options(uri) {
        }
        patch(uri) {
        }
        post(uri) {
        }
        put(uri) {
        }
    }
    var RestAction;
    (function (RestAction) {
        RestAction["Delete"] = "delete";
    })(RestAction = exports.RestAction || (exports.RestAction = {}));
    function isResponseError(response) {
        return !response.ok;
    }
    exports.isResponseError = isResponseError;
    function redirectToSelf() {
        window.location.reload();
    }
    exports.redirectToSelf = redirectToSelf;
    function redirectToHome() {
        redirectTo('/');
    }
    exports.redirectToHome = redirectToHome;
    function redirectTo(uri, storePageInHistory = true) {
        if (storePageInHistory) {
            window.location.href = uri;
        }
        else {
            window.location.replace(uri);
        }
    }
    exports.redirectTo = redirectTo;
    function queryArgs() {
        const decode = (input) => decodeURIComponent(input.replace(/\+/g, ' '));
        const parser = /([^=?&]+)=?([^&]*)/g;
        let queryArgs = {}, part;
        while (part = parser.exec(window.location.search)) {
            let key = decode(part[1]), value = decode(part[2]);
            if (key in queryArgs) {
                continue;
            }
            queryArgs[key] = value;
        }
        return queryArgs;
    }
    exports.queryArgs = queryArgs;
});
define("localhost/lib/base/base", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.delayedCallback = exports.showUnknownError = exports.Re = exports.isGenerator = exports.isDomNode = exports.isPromise = exports.lname = exports.id = void 0;
    function id(value) {
        return value;
    }
    exports.id = id;
    function lname(name) {
        name = name.replace('_', '-');
        name = name.replace(/[a-z][A-Z]/, function camelizeNextCh(match) {
            return match[0] + '-' + match[1].toLowerCase();
        });
        name = name.replace(/[^-A-Za-z.0-9]/, '-');
        name = name.replace(/-+/, '-');
        return name;
    }
    exports.lname = lname;
    function isPromise(val) {
        return val && typeof val.promise === 'function';
    }
    exports.isPromise = isPromise;
    function isDomNode(obj) {
        return obj && obj.nodeType > 0;
    }
    exports.isDomNode = isDomNode;
    function isGenerator(fn) {
        return fn.constructor.name === 'GeneratorFunction';
    }
    exports.isGenerator = isGenerator;
    class Re {
    }
    exports.Re = Re;
    Object.defineProperty(Re, "email", {
        enumerable: true,
        configurable: true,
        writable: true,
        value: /^[^@]+@[^@]+$/
    });
    function showUnknownError(message) {
        alert("Unknown error, please contact support");
    }
    exports.showUnknownError = showUnknownError;
    function delayedCallback(callback, waitMs) {
        let timer = 0;
        return function () {
            const self = this;
            const args = arguments;
            clearTimeout(timer);
            timer = window.setTimeout(function () {
                callback.apply(self, args);
            }, waitMs);
        };
    }
    exports.delayedCallback = delayedCallback;
});
define("localhost/lib/base/widget", ["require", "exports", "localhost/lib/base/event-manager"], function (require, exports, event_manager_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.showResponseErr = exports.errorToast = exports.okToast = exports.Widget = void 0;
    class Widget extends event_manager_1.EventManager {
        constructor(conf) {
            super();
            Object.defineProperty(this, "el", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: void 0
            });
            Object.defineProperty(this, "conf", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: void 0
            });
            this.conf = this.normalizeConf(conf);
            this.init();
            this.bindHandlers();
        }
        dispose() {
        }
        init() {
            if (this.conf && this.conf.el) {
                this.el = $(this.conf.el);
            }
        }
        bindHandlers() {
        }
        normalizeConf(conf) {
            if (conf instanceof jQuery) {
                return { el: conf };
            }
            return conf;
        }
    }
    exports.Widget = Widget;
    function okToast(text) {
        Toastify({
            text: text,
            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
            className: "info",
        }).showToast();
    }
    exports.okToast = okToast;
    function errorToast(text = undefined) {
        Toastify({
            text: text || 'Error.',
            backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
            className: "info",
        }).showToast();
    }
    exports.errorToast = errorToast;
    function showResponseErr(response) {
        if (response.err && typeof response.err == 'string') {
            errorToast(response.err);
        }
        else {
            errorToast();
        }
    }
    exports.showResponseErr = showResponseErr;
});
define("localhost/lib/base/message", ["require", "exports", "localhost/lib/base/widget"], function (require, exports, widget_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.DebugMessage = exports.InfoMessage = exports.WarningMessage = exports.ErrorMessage = exports.Message = exports.messageTypeToStr = exports.renderMessage = exports.PageMessenger = exports.MessageType = void 0;
    var MessageType;
    (function (MessageType) {
        MessageType[MessageType["Error"] = 1] = "Error";
        MessageType[MessageType["Warning"] = 2] = "Warning";
        MessageType[MessageType["Info"] = 4] = "Info";
        MessageType[MessageType["Debug"] = 8] = "Debug";
        MessageType[MessageType["All"] = 15] = "All";
    })(MessageType = exports.MessageType || (exports.MessageType = {}));
    class PageMessenger extends widget_1.Widget {
        numberOfMessages() {
            return this.messageEls().length;
        }
        messageEls() {
            return this.el.find('.alert');
        }
        bindHandlers() {
            super.bindHandlers();
            this.registerCloseMessageHandler();
        }
        registerCloseMessageHandler() {
            const self = this;
            function hideElWithAnim($el, complete) {
                $el.fadeOut(complete);
            }
            function hideMainContainerWithAnim() {
                hideElWithAnim(self.el, function () {
                    self.el.find('.messages').remove();
                    self.el.hide();
                });
            }
            function closeMessageWithAnim($message) {
                if (self.numberOfMessages() === 1) {
                    hideMainContainerWithAnim();
                }
                else {
                    const $messageContainer = $message.closest('.messages');
                    if ($messageContainer.find('.alert').length === 1) {
                        hideElWithAnim($messageContainer, function () {
                            $messageContainer.remove();
                        });
                    }
                    else {
                        hideElWithAnim($message, function () {
                            $message.remove();
                        });
                    }
                }
            }
            this.el.on('click', 'button.close', function () {
                closeMessageWithAnim($(this).closest('.alert'));
            });
            setTimeout(function () {
                hideMainContainerWithAnim();
            }, 5000);
        }
    }
    exports.PageMessenger = PageMessenger;
    function renderMessage(message) {
        let text = message.text.encodeHtml();
        text = text.format(message.args);
        return wrapMessage(text, messageTypeToStr(message.type));
    }
    exports.renderMessage = renderMessage;
    function wrapMessage(text, type) {
        return '<div class="' + type.toLowerCase().encodeHtml() + '">' + text + '</div>';
    }
    function messageTypeToStr(type) {
        return MessageType[type];
    }
    exports.messageTypeToStr = messageTypeToStr;
    class Message {
        constructor(type, text, args = []) {
            Object.defineProperty(this, "type", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: type
            });
            Object.defineProperty(this, "text", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: text
            });
            Object.defineProperty(this, "args", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: args
            });
        }
        hasType(type) {
            return this.type === type;
        }
    }
    exports.Message = Message;
    class ErrorMessage extends Message {
        constructor(text, args = []) {
            super(MessageType.Error, text, args);
        }
    }
    exports.ErrorMessage = ErrorMessage;
    class WarningMessage extends Message {
        constructor(text, args = []) {
            super(MessageType.Warning, text, args);
        }
    }
    exports.WarningMessage = WarningMessage;
    class InfoMessage extends Message {
        constructor(text, args = []) {
            super(MessageType.Warning, text, args);
        }
    }
    exports.InfoMessage = InfoMessage;
    class DebugMessage extends Message {
        constructor(text, args = []) {
            super(MessageType.Debug, text, args);
        }
    }
    exports.DebugMessage = DebugMessage;
});
define("localhost/lib/base/app", ["require", "exports", "localhost/lib/base/message"], function (require, exports, message_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.App = void 0;
    class App {
        constructor() {
            Object.defineProperty(this, "context", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: {}
            });
            this.context.pageMessenger = new message_1.PageMessenger({ el: $('#page-messages') });
            this.bindEventHandlers();
        }
        bindEventHandlers() {
            this.bindMainMenuHandlers();
        }
        bindMainMenuHandlers() {
            const uriPath = window.location.pathname;
            $('#main-menu a').each(function () {
                const $a = $(this);
                let linkUri = $a.attr('href');
                if (!linkUri) {
                    return;
                }
                if (linkUri.substr(0, 1) !== '/') {
                    return;
                }
                let offset = linkUri.indexOf('?');
                if (offset >= 0) {
                    linkUri = linkUri.substr(0, offset);
                }
                offset = linkUri.indexOf('#');
                if (offset >= 0) {
                    linkUri = linkUri.substr(0, offset);
                }
                if (linkUri === uriPath) {
                    $a.addClass('active');
                    $a.closest('.dropdown').find('.nav-link:first').addClass('active');
                }
            });
        }
    }
    exports.App = App;
});
define("localhost/lib/base/bom", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    Math.EPS = 0.000001;
    Math.roundFloat = function (val, precision = 2) {
        const dd = Math.pow(10, precision);
        return Math.round(val * dd) / dd;
    };
    Math.floatLessThanZero = function (val) {
        return val < -Math.EPS;
    };
    Math.floatGreaterThanZero = function (val) {
        return val > Math.EPS;
    };
    Math.floatEqualZero = function (val) {
        return Math.abs(val) <= Math.EPS;
    };
    Math.floatsEqual = function (a, b) {
        return Math.floatEqualZero(a - b);
    };
    Math.logN = function (n, base) {
        return Math.log(n) / Math.log(base);
    };
    String.prototype.e = function () {
        const entityMap = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': '&quot;',
            "'": '&#39;'
        };
        return this.replace(/[&<>"']/g, function (s) {
            return entityMap[s];
        });
    };
    String.prototype.titleize = function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };
    String.prototype.format = function (args, filter) {
        let val = this;
        args.forEach((arg, index) => {
            val = val.replace('{' + index + '}', filter ? filter(arg) : arg);
        });
        return val;
    };
    String.prototype.nl2Br = function () {
        return this.replace(/\r?\n/g, '<br>');
    };
    String.prototype.replaceAll = function (search, replace) {
        return this.split(search).join(replace);
    };
    String.prototype.ucFirst = function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };
    String.prototype.trimR = function (chars) {
        if (chars === undefined) {
            return this.replace(new RegExp('\\s+$'), '');
        }
        return this.replace(new RegExp("[" + RegExp.e(chars) + "]+$"), '');
    };
    String.prototype.trimL = function (chars) {
        if (chars === undefined) {
            return this.replace(new RegExp('^\\s+'), '');
        }
        return this.replace(new RegExp("^[" + RegExp.e(chars) + "]+"), '');
    };
    String.prototype.trimLR = function (chars) {
        if (chars == undefined) {
            return this.trim();
        }
        return this.trimL(chars).trimR(chars);
    };
    RegExp.e = function (s) {
        return String(s).replace(/[\\^$*+?.()|[\]{}]/g, '\\$&');
    };
    Object.pick = function (object, keys) {
        return keys.reduce((obj, key) => {
            if (object && object.hasOwnProperty(key)) {
                obj[key] = object[key];
            }
            return obj;
        }, {});
    };
});
define("localhost/lib/base/error", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.UnexpectedValueException = exports.NotImplementedException = exports.Exception = void 0;
    class Exception extends Error {
        constructor(message) {
            super(message);
            Object.defineProperty(this, "message", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: message
            });
            this.name = 'Exception';
            this.message = message;
        }
        toString() {
            return this.name + ': ' + this.message;
        }
    }
    exports.Exception = Exception;
    class NotImplementedException extends Exception {
    }
    exports.NotImplementedException = NotImplementedException;
    class UnexpectedValueException extends Exception {
    }
    exports.UnexpectedValueException = UnexpectedValueException;
});
define("localhost/lib/base/form", ["require", "exports", "localhost/lib/base/message", "localhost/lib/base/widget", "localhost/lib/base/http"], function (require, exports, message_2, widget_2, http_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.Form = exports.elChangeEvents = exports.FieldType = exports.els = exports.forEachEl = exports.formData = exports.validateEl = exports.defaultValidators = exports.RequiredElValidator = void 0;
    class RequiredElValidator {
        validate($el) {
            if (Form.isRequiredEl($el)) {
                if (Form.elValue($el).trim().length < 1) {
                    return [RequiredElValidator.EmptyValueMessage];
                }
            }
            return [];
        }
    }
    exports.RequiredElValidator = RequiredElValidator;
    Object.defineProperty(RequiredElValidator, "EmptyValueMessage", {
        enumerable: true,
        configurable: true,
        writable: true,
        value: 'This field is required'
    });
    function defaultValidators() {
        return [
            new RequiredElValidator()
        ];
    }
    exports.defaultValidators = defaultValidators;
    function validateEl($el, validators) {
        if (!validators) {
            validators = defaultValidators();
        }
        let errors = [];
        validators.forEach(function (validator) {
            errors = errors.concat(validator.validate($el));
        });
        return errors;
    }
    exports.validateEl = validateEl;
    function formData($form) {
        const data = [];
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
    exports.formData = formData;
    function forEachEl($form, fn) {
        return els($form).each(function (index, el) {
            if (false === fn($(el), index)) {
                return false;
            }
            return undefined;
        });
    }
    exports.forEachEl = forEachEl;
    function els($form) {
        return $($form[0].elements);
    }
    exports.els = els;
    var FieldType;
    (function (FieldType) {
        FieldType["Button"] = "button";
        FieldType["Checkbox"] = "checkbox";
        FieldType["File"] = "file";
        FieldType["Hidden"] = "hidden";
        FieldType["Image"] = "image";
        FieldType["Password"] = "password";
        FieldType["Radio"] = "radio";
        FieldType["Reset"] = "reset";
        FieldType["Select"] = "select";
        FieldType["Submit"] = "submit";
        FieldType["Textarea"] = "textarea";
        FieldType["Textfield"] = "text";
    })(FieldType = exports.FieldType || (exports.FieldType = {}));
    exports.elChangeEvents = 'keyup blur change paste cut';
    class Form extends widget_2.Widget {
        constructor() {
            super(...arguments);
            Object.defineProperty(this, "skipValidation", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: void 0
            });
            Object.defineProperty(this, "elContainerCssClass", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: void 0
            });
            Object.defineProperty(this, "formMessageContainerCssClass", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: void 0
            });
            Object.defineProperty(this, "invalidCssClass", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: void 0
            });
            Object.defineProperty(this, "elChangeEvents", {
                enumerable: true,
                configurable: true,
                writable: true,
                value: void 0
            });
        }
        static elValue($el) {
            if ($el.get(0)['type'] === 'checkbox') {
                return $el.is(':checked') ? 1 : 0;
            }
            return $el.val();
        }
        static isRequiredEl($el) {
            return $el.is('[required]');
        }
        els() {
            return els(this.el);
        }
        elsToValidate() {
            return this.els().filter(function () {
                const $el = $(this);
                return $el.is(':not(:submit)');
            });
        }
        validate() {
            this.removeErrors();
            let errors = [];
            this.elsToValidate().each(function () {
                const $el = $(this);
                const elErrors = validateEl($el);
                if (elErrors.length) {
                    errors.push([$el, elErrors.map((error) => { return new message_2.ErrorMessage(error); })]);
                }
            });
            if (errors.length) {
                this.showErrors(errors);
                return false;
            }
            return true;
        }
        invalidEls() {
            const self = this;
            return this.els().filter(function () {
                return $(this).hasClass(self.invalidCssClass);
            });
        }
        hasErrors() {
            return this.el.hasClass(this.invalidCssClass);
        }
        removeErrors() {
            this.invalidEls().each((index, el) => {
                this.removeElErrors($(el));
            });
            this.formMessageContainerEl().remove();
            this.el.removeClass(this.invalidCssClass);
        }
        submit() {
            this.removeErrors();
            if (this.skipValidation) {
                this.send();
            }
            else if (this.validate()) {
                this.send();
            }
        }
        send() {
            this.disableSubmitButtonEls();
            return this.sendFormData(this.uri(), this.formData());
        }
        showErrors(errors) {
            let formErrors = [];
            errors.forEach((err) => {
                if (Array.isArray(err)) {
                    const [$el, elErrors] = err;
                    this.showElErrors($el, elErrors);
                }
                else {
                    formErrors.push(err);
                }
            });
            this.showFormErrors(formErrors);
            this.scrollToFirstError();
        }
        static fieldType($field) {
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
        showFormErrors(errors) {
            if (errors.length) {
                const rendered = '<div class="alert alert-error">' + errors.map(message_2.renderMessage).join("\n") + '</div>';
                this.formMessageContainerEl()
                    .prepend(rendered);
            }
            this.el.addClass(this.invalidCssClass);
        }
        showElErrors($el, errors) {
            const invalidCssClass = this.invalidCssClass;
            $el.addClass(invalidCssClass).closest('.' + this.elContainerCssClass).addClass(invalidCssClass).addClass('has-error');
            $el.after(errors.map(message_2.renderMessage).join("\n"));
        }
        removeElErrors($el) {
            const $container = $el.removeClass(this.invalidCssClass).closest('.' + this.elContainerCssClass);
            if (!$container.find('.' + this.invalidCssClass).length) {
                $container.removeClass(this.invalidCssClass).removeClass('has-error');
            }
            $el.next('.error').remove();
        }
        formMessageContainerEl() {
            const containerCssClass = this.formMessageContainerCssClass;
            let $containerEl = this.el.find('.' + containerCssClass);
            if (!$containerEl.length) {
                $containerEl = $('<div class="' + containerCssClass + '"></div>').prependTo(this.el);
            }
            return $containerEl;
        }
        init() {
            super.init();
            this.skipValidation = false;
            this.elContainerCssClass = 'form-group';
            this.formMessageContainerCssClass = 'messages';
            this.invalidCssClass = Form.defaultInvalidCssClass;
            this.elChangeEvents = exports.elChangeEvents;
            this.el.attr('novalidate', 'novalidate');
        }
        bindHandlers() {
            this.el.on('submit', () => {
                this.submit();
                return false;
            });
            const self = this;
            this.elsToValidate().on(this.elChangeEvents, function () {
                const $el = $(this);
                if ($el.hasClass(self.invalidCssClass)) {
                    self.removeElErrors($el);
                }
            });
        }
        sendFormData(uri, requestData) {
            const ajaxSettings = this.ajaxSettings();
            ajaxSettings.url = uri;
            ajaxSettings.data = requestData;
            return $.ajax(ajaxSettings);
        }
        ajaxSettings() {
            const self = this;
            return {
                beforeSend(jqXHR, settings) {
                    return self.beforeSend(jqXHR, settings);
                },
                success(data, textStatus, jqXHR) {
                    return self.ajaxSuccess(data, textStatus, jqXHR);
                },
                error(jqXHR, textStatus, errorThrown) {
                    return self.ajaxError(jqXHR, textStatus, errorThrown);
                },
                method: this.submitMethod()
            };
        }
        submitMethod() {
            return this.el.attr('method') || 'GET';
        }
        beforeSend(jqXHR, settings) {
        }
        ajaxSuccess(responseData, textStatus, jqXHR) {
            this.enableSubmitButtonEls();
            this.handleResponse(responseData);
        }
        ajaxError(jqXHR, textStatus, errorThrown) {
            this.enableSubmitButtonEls();
            alert("AJAX error");
        }
        formData() {
            return formData(this.el);
        }
        uri() {
            return this.el.attr('action') || window.location.href;
        }
        enableSubmitButtonEls() {
            this.submitButtonEls().prop('disabled', false);
        }
        disableSubmitButtonEls() {
            this.submitButtonEls().prop('disabled', true);
        }
        submitButtonEls() {
            return this.els().filter(function () {
                return $(this).is(':submit');
            });
        }
        handleResponse(result) {
            if (result.err !== undefined) {
                this.handleErrResponse(result.err);
            }
            else if (result.ok !== undefined) {
                this.handleOkResponse(result.ok);
            }
            else {
                this.invalidResponseError();
            }
        }
        handleOkResponse(responseData) {
            if (responseData && responseData.redirect) {
                http_1.redirectTo(responseData.redirect);
                return true;
            }
        }
        handleErrResponse(responseData) {
            if (Array.isArray(responseData)) {
                const errors = responseData.map((message) => {
                    return new message_2.ErrorMessage(message.text, message.args);
                });
                this.showErrors(errors);
            }
            else {
                this.invalidResponseError();
            }
        }
        invalidResponseError() {
            alert('Invalid response');
        }
        scrollToFirstError() {
            let $first = this.el.find('.error:first');
            let $container = $first.closest('.' + this.elContainerCssClass);
            if ($container.length) {
                $first = $container;
            }
            else {
                $container = $first.closest('.' + this.formMessageContainerCssClass);
                if ($container.length) {
                    $first = $container;
                }
            }
            if (!$first.length) {
                return;
            }
        }
    }
    exports.Form = Form;
    Object.defineProperty(Form, "defaultInvalidCssClass", {
        enumerable: true,
        configurable: true,
        writable: true,
        value: 'invalid'
    });
});
define("localhost/lib/base/grid", ["require", "exports", "localhost/lib/base/widget"], function (require, exports, widget_3) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.Grid = void 0;
    class Grid extends widget_3.Widget {
        bindHandlers() {
            super.bindHandlers();
            this.el.find('.grid__chk').on('change', function () {
                console.log(this);
            });
        }
        checkAllCheckboxes() {
            this.checkboxes().prop('checked', true).trigger('change');
        }
        uncheckAllCheckboxes() {
            this.checkboxes().prop('checked', false).trigger('change');
        }
        checkedCheckboxes() {
            return this.checkboxes(':checked');
        }
        checkboxes(selector) {
            return this.el.find('.grid__chk' + (selector || ''));
        }
        isActionButtonsDisabled() {
            const actionsButtons = this.actionButtons();
            if (!actionsButtons.length) {
                throw new Error("Empty action buttons");
            }
            return actionsButtons.filter(':not(.disabled)').length === 0;
        }
        actionButtons() {
            return this.el.find('.grid__action-btn');
        }
    }
    exports.Grid = Grid;
});
define("localhost/lib/base/i18n", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.tr = void 0;
    function tr(message) {
        return message;
    }
    exports.tr = tr;
});
define("localhost/lib/base/jquery-ext", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.__dummy = void 0;
    (() => {
        let uniqId = 0;
        $.fn.once = function (fn) {
            let cssClass = String(uniqId++) + '-processed';
            return this.not('.' + cssClass)
                .addClass(cssClass)
                .each(fn);
        };
    })();
    $.resolvedPromise = function (value, ...args) {
        return $.Deferred().resolve(value, ...args).promise();
    };
    $.rejectedPromise = function (value, ...args) {
        return $.Deferred().reject(value, ...args).promise();
    };
    exports.__dummy = null;
    $.fn.extend({
        uniqId: (function () {
            var uuid = 0;
            return function () {
                return this.each(function () {
                    if (!this.id) {
                        this.id = "ui-id-" + (++uuid);
                    }
                });
            };
        })(),
        removeUniqId: function () {
            return this.each(function () {
                if (/^ui-id-\d+$/.test(this.id)) {
                    $(this).removeAttr("id");
                }
            });
        }
    });
});
define("localhost/lib/base/keyboard", ["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.bind = void 0;
    function bind(k, handler) {
    }
    exports.bind = bind;
});
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaW5kZXguanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJldmVudC1tYW5hZ2VyLnRzIiwidXJpLnRzIiwiaHR0cC50cyIsImJhc2UudHMiLCJ3aWRnZXQudHMiLCJtZXNzYWdlLnRzIiwiYXBwLnRzIiwiYm9tLnRzIiwiZXJyb3IudHMiLCJmb3JtLnRzIiwiZ3JpZC50cyIsImkxOG4udHMiLCJqcXVlcnktZXh0LnRzIiwia2V5Ym9hcmQudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7OztJQVNBLE1BQWEsWUFBWTtRQUF6QjtZQUNJOzs7O3VCQUEyRCxFQUFFO2VBQUM7UUFrQmxFLENBQUM7UUFoQlUsRUFBRSxDQUFDLFNBQWlCLEVBQUUsT0FBb0I7WUFDN0MsSUFBSSxDQUFDLFFBQVEsQ0FBQyxTQUFTLENBQUMsR0FBRyxJQUFJLENBQUMsUUFBUSxDQUFDLFNBQVMsQ0FBQyxJQUFJLEVBQUUsQ0FBQztZQUMxRCxJQUFJLENBQUMsUUFBUSxDQUFDLFNBQVMsQ0FBQyxDQUFDLElBQUksQ0FBQyxPQUFPLENBQUMsQ0FBQztRQUMzQyxDQUFDO1FBRU0sT0FBTyxDQUFDLFNBQWlCLEVBQUUsR0FBRyxJQUFXO1lBQzVDLElBQUksUUFBUSxHQUFHLElBQUksQ0FBQyxRQUFRLENBQUMsU0FBUyxDQUFDLENBQUM7WUFDeEMsSUFBSSxDQUFDLFFBQVEsRUFBRTtnQkFDWCxPQUFPO2FBQ1Y7WUFDRCxLQUFLLElBQUksQ0FBQyxHQUFHLENBQUMsRUFBRSxDQUFDLEdBQUcsUUFBUSxDQUFDLE1BQU0sRUFBRSxFQUFFLENBQUMsRUFBRTtnQkFDdEMsSUFBSSxLQUFLLEtBQUssUUFBUSxDQUFDLENBQUMsQ0FBQyxDQUFDLEdBQUcsSUFBSSxDQUFDLEVBQUU7b0JBQ2hDLE1BQU07aUJBQ1Q7YUFDSjtRQUNMLENBQUM7S0FDSjtJQW5CRCxvQ0FtQkM7Ozs7OztJQzVCRCxNQUFhLEdBQUc7S0FFZjtJQUZELGtCQUVDOzs7Ozs7SUNBRCxNQUFNLElBQUk7UUFDQyxHQUFH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