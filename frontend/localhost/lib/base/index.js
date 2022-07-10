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
            this.unbindHandlers();
        }
        init() {
            if (this.conf && this.conf.el) {
                this.el = $(this.conf.el);
            }
        }
        bindHandlers() {
        }
        unbindHandlers() {
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
        isActionButtonDisabled() {
            const actionButtons = this.actionButtons();
            if (!actionButtons.length) {
                throw new Error("Empty action buttons");
            }
            return actionButtons.filter(':not(.disabled)').length === 0;
        }
        actionButtons() {
            return this.el.find('.grid__action-btn');
        }
        init() {
            super.init();
            this.initCheckboxes();
            this.initActionButtons();
        }
        bindHandlers() {
            super.bindHandlers();
        }
        unbindHandlers() {
            super.unbindHandlers();
        }
        initCheckboxes() {
            const selectAllCheckbox = this.selectAllCheckbox();
            const checkboxes = this.checkboxes();
            if (selectAllCheckbox.is(':checked') || (checkboxes.length && !checkboxes.not(selectAllCheckbox).not(':checked').length)) {
                this.checkAllCheckboxes();
            }
        }
        initActionButtons() {
        }
        selectAllCheckbox() {
            return this.el.find('.grid__chk-all');
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaW5kZXguanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJldmVudC1tYW5hZ2VyLnRzIiwidXJpLnRzIiwiaHR0cC50cyIsImJhc2UudHMiLCJ3aWRnZXQudHMiLCJtZXNzYWdlLnRzIiwiYXBwLnRzIiwiYm9tLnRzIiwiZXJyb3IudHMiLCJmb3JtLnRzIiwiZ3JpZC50cyIsImkxOG4udHMiLCJqcXVlcnktZXh0LnRzIiwia2V5Ym9hcmQudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7OztJQVNBLE1BQWEsWUFBWTtRQUF6QjtZQUNJOzs7O3VCQUEyRCxFQUFFO2VBQUM7UUFrQmxFLENBQUM7UUFoQlUsRUFBRSxDQUFDLFNBQWlCLEVBQUUsT0FBb0I7WUFDN0MsSUFBSSxDQUFDLFFBQVEsQ0FBQyxTQUFTLENBQUMsR0FBRyxJQUFJLENBQUMsUUFBUSxDQUFDLFNBQVMsQ0FBQyxJQUFJLEVBQUUsQ0FBQztZQUMxRCxJQUFJLENBQUMsUUFBUSxDQUFDLFNBQVMsQ0FBQyxDQUFDLElBQUksQ0FBQyxPQUFPLENBQUMsQ0FBQztRQUMzQyxDQUFDO1FBRU0sT0FBTyxDQUFDLFNBQWlCLEVBQUUsR0FBRyxJQUFXO1lBQzVDLElBQUksUUFBUSxHQUFHLElBQUksQ0FBQyxRQUFRLENBQUMsU0FBUyxDQUFDLENBQUM7WUFDeEMsSUFBSSxDQUFDLFFBQVEsRUFBRTtnQkFDWCxPQUFPO2FBQ1Y7WUFDRCxLQUFLLElBQUksQ0FBQyxHQUFHLENBQUMsRUFBRSxDQUFDLEdBQUcsUUFBUSxDQUFDLE1BQU0sRUFBRSxFQUFFLENBQUMsRUFBRTtnQkFDdEMsSUFBSSxLQUFLLEtBQUssUUFBUSxDQUFDLENBQUMsQ0FBQyxDQUFDLEdBQUcsSUFBSSxDQUFDLEVBQUU7b0JBQ2hDLE1BQU07aUJBQ1Q7YUFDSjtRQUNMLENBQUM7S0FDSjtJQW5CRCxvQ0FtQkM7Ozs7OztJQzVCRCxNQUFhLEdBQUc7S0FFZjtJQUZELGtCQUVDOzs7Ozs7SUNBRCxNQUFNLElBQUk7UUFDQyxHQUFHLENBQUMsR0FBaUI7UUFFNUIsQ0FBQztRQUVNLE1BQU0sQ0FBQyxHQUFpQjtRQUUvQixDQUFDO1FBRU0sSUFBSSxDQUFDLEdBQWlCO1FBRTdCLENBQUM7UUFFTSxPQUFPLENBQUMsR0FBaUI7UUFFaEMsQ0FBQztRQUVNLEtBQUssQ0FBQyxHQUFpQjtRQUU5QixDQUFDO1FBRU0sSUFBSSxDQUFDLEdBQWlCO1FBRTdCLENBQUM7UUFFTSxHQUFHLENBQUMsR0FBaUI7UUFFNUIsQ0FBQztLQUNKO0lBRUQsSUFBWSxVQUVYO0lBRkQsV0FBWSxVQUFVO1FBQ2xCLCtCQUFpQixDQUFBO0lBQ3JCLENBQUMsRUFGVyxVQUFVLEdBQVYsa0JBQVUsS0FBVixrQkFBVSxRQUVyQjtJQUlELFNBQWdCLGVBQWUsQ0FBQyxRQUF3QjtRQUNwRCxPQUFPLENBQUMsUUFBUSxDQUFDLEVBQUUsQ0FBQztJQUN4QixDQUFDO0lBRkQsMENBRUM7SUFFRCxTQUFnQixjQUFjO1FBRTFCLE1BQU0sQ0FBQyxRQUFRLENBQUMsTUFBTSxFQUFFLENBQUM7SUFDN0IsQ0FBQztJQUhELHdDQUdDO0lBRUQsU0FBZ0IsY0FBYztRQUcxQixVQUFVLENBQUMsR0FBRyxDQUFDLENBQUM7SUFDcEIsQ0FBQztJQUpELHdDQUlDO0lBRUQsU0FBZ0IsVUFBVSxDQUFDLEdBQVcsRUFBRSxrQkFBa0IsR0FBRyxJQUFJO1FBQzdELElBQUksa0JBQWtCLEVBQUU7WUFDcEIsTUFBTSxDQUFDLFFBQVEsQ0FBQyxJQUFJLEdBQUcsR0FBRyxDQUFDO1NBQzlCO2FBQU07WUFDSCxNQUFNLENBQUMsUUFBUSxDQUFDLE9BQU8sQ0FBQyxHQUFHLENBQUMsQ0FBQztTQUNoQztJQUNMLENBQUM7SUFORCxnQ0FNQztJQUdELFNBQWdCLFNBQVM7UUFDckIsTUFBTSxNQUFNLEdBQUcsQ0FBQyxLQUFhLEVBQVUsRUFBRSxDQUFDLGtCQUFrQixDQUFDLEtBQUssQ0FBQyxPQUFPLENBQUMsS0FBSyxFQUFFLEdBQUcsQ0FBQyxDQUFDLENBQUM7UUFFeEYsTUFBTSxNQUFNLEdBQUcscUJBQXFCLENBQUM7UUFDckMsSUFBSSxTQUFTLEdBQXVCLEVBQUUsRUFDbEMsSUFBSSxDQUFDO1FBRVQsT0FBTyxJQUFJLEdBQUcsTUFBTSxDQUFDLElBQUksQ0FBQyxNQUFNLENBQUMsUUFBUSxDQUFDLE1BQU0sQ0FBQyxFQUFFO1lBQy9DLElBQUksR0FBRyxHQUFHLE1BQU0sQ0FBQyxJQUFJLENBQUMsQ0FBQyxDQUFDLENBQUMsRUFDckIsS0FBSyxHQUFHLE1BQU0sQ0FBQyxJQUFJLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQztZQUs1QixJQUFJLEdBQUcsSUFBSSxTQUFTLEVBQUU7Z0JBQ2xCLFNBQVM7YUFDWjtZQUNELFNBQVMsQ0FBQyxHQUFHLENBQUMsR0FBRyxLQUFLLENBQUM7U0FDMUI7UUFFRCxPQUFPLFNBQVMsQ0FBQztJQUNyQixDQUFDO0lBckJELDhCQXFCQzs7Ozs7O0lDeEVELFNBQWdCLEVBQUUsQ0FBQyxLQUFVO1FBQ3pCLE9BQU8sS0FBSyxDQUFDO0lBQ2pCLENBQUM7SUFGRCxnQkFFQztJQUVELFNBQWdCLEtBQUssQ0FBQyxJQUFZO1FBQzlCLElBQUksR0FBRyxJQUFJLENBQUMsT0FBTyxDQUFDLEdBQUcsRUFBRSxHQUFHLENBQUMsQ0FBQTtRQUM3QixJQUFJLEdBQUcsSUFBSSxDQUFDLE9BQU8sQ0FBQyxZQUFZLEVBQUUsU0FBUyxjQUFjLENBQUMsS0FBYTtZQUNuRSxPQUFPLEtBQUssQ0FBQyxDQUFDLENBQUMsR0FBRyxHQUFHLEdBQUcsS0FBSyxDQUFDLENBQUMsQ0FBQyxDQUFDLFdBQVcsRUFBRSxDQUFDO1FBQ25ELENBQUMsQ0FBQyxDQUFBO1FBQ0YsSUFBSSxHQUFHLElBQUksQ0FBQyxPQUFPLENBQUMsZ0JBQWdCLEVBQUUsR0FBRyxDQUFDLENBQUE7UUFDMUMsSUFBSSxHQUFHLElBQUksQ0FBQyxPQUFPLENBQUMsSUFBSSxFQUFFLEdBQUcsQ0FBQyxDQUFBO1FBQzlCLE9BQU8sSUFBSSxDQUFDO0lBQ2hCLENBQUM7SUFSRCxzQkFRQztJQUVELFNBQWdCLFNBQVMsQ0FBQyxHQUFRO1FBQzlCLE9BQU8sR0FBRyxJQUFJLE9BQU8sR0FBRyxDQUFDLE9BQU8sS0FBSyxVQUFVLENBQUM7SUFDcEQsQ0FBQztJQUZELDhCQUVDO0lBR0QsU0FBZ0IsU0FBUyxDQUFDLEdBQVE7UUFDOUIsT0FBTyxHQUFHLElBQUksR0FBRyxDQUFDLFFBQVEsR0FBRyxDQUFDLENBQUM7SUFDbkMsQ0FBQztJQUZELDhCQUVDO0lBRUQsU0FBZ0IsV0FBVyxDQUFDLEVBQVk7UUFDcEMsT0FBYSxFQUFFLENBQUMsV0FBWSxDQUFDLElBQUksS0FBSyxtQkFBbUIsQ0FBQztJQUM5RCxDQUFDO0lBRkQsa0NBRUM7SUFFRCxNQUFhLEVBQUU7O0lBQWYsZ0JBRUM7SUFERzs7OztlQUErQixlQUFlO09BQUM7SUFNbkQsU0FBZ0IsZ0JBQWdCLENBQUMsT0FBZ0I7UUFFN0MsS0FBSyxDQUFDLHVDQUF1QyxDQUFDLENBQUM7SUFDbkQsQ0FBQztJQUhELDRDQUdDO0lBSUQsU0FBZ0IsZUFBZSxDQUFDLFFBQWtCLEVBQUUsTUFBYztRQUM5RCxJQUFJLEtBQUssR0FBVyxDQUFDLENBQUM7UUFDdEIsT0FBTztZQUNILE1BQU0sSUFBSSxHQUFHLElBQUksQ0FBQztZQUNsQixNQUFNLElBQUksR0FBRyxTQUFTLENBQUM7WUFDdkIsWUFBWSxDQUFDLEtBQUssQ0FBQyxDQUFDO1lBQ3BCLEtBQUssR0FBRyxNQUFNLENBQUMsVUFBVSxDQUFDO2dCQUN0QixRQUFRLENBQUMsS0FBSyxDQUFDLElBQUksRUFBRSxJQUFJLENBQUMsQ0FBQztZQUMvQixDQUFDLEVBQUUsTUFBTSxDQUFDLENBQUM7UUFDZixDQUFDLENBQUM7SUFDTixDQUFDO0lBVkQsMENBVUM7Ozs7OztJQzlDRCxNQUFzQixNQUE4QyxTQUFRLDRCQUFZO1FBS3BGLFlBQW1CLElBQW9CO1lBQ25DLEtBQUssRUFBRSxDQUFDO1lBTFo7Ozs7O2VBQXNCO1lBRXRCOzs7OztlQUFzQjtZQUlsQixJQUFJLENBQUMsSUFBSSxHQUFHLElBQUksQ0FBQyxhQUFhLENBQUMsSUFBSSxDQUFDLENBQUM7WUFDckMsSUFBSSxDQUFDLElBQUksRUFBRSxDQUFDO1lBQ1osSUFBSSxDQUFDLFlBQVksRUFBRSxDQUFDO1FBQ3hCLENBQUM7UUFFTSxPQUFPO1lBQ1YsSUFBSSxDQUFDLGNBQWMsRUFBRSxDQUFDO1FBQzFCLENBQUM7UUFFUyxJQUFJO1lBQ1YsSUFBSSxJQUFJLENBQUMsSUFBSSxJQUFJLElBQUksQ0FBQyxJQUFJLENBQUMsRUFBRSxFQUFFO2dCQUMzQixJQUFJLENBQUMsRUFBRSxHQUFHLENBQUMsQ0FBUyxJQUFJLENBQUMsSUFBSSxDQUFDLEVBQUUsQ0FBQyxDQUFDO2FBQ3JDO1FBQ0wsQ0FBQztRQUVTLFlBQVk7UUFDdEIsQ0FBQztRQUVTLGNBQWM7UUFDeEIsQ0FBQztRQUVTLGFBQWEsQ0FBQyxJQUFvQjtZQUN4QyxJQUFTLElBQUksWUFBWSxNQUFNLEVBQUU7Z0JBQzdCLE9BQWMsRUFBQyxFQUFFLEVBQVUsSUFBSSxFQUFDLENBQUM7YUFDcEM7WUFDRCxPQUFjLElBQUksQ0FBQztRQUN2QixDQUFDO0tBQ0o7SUFsQ0Qsd0JBa0NDO0lBaUJELFNBQWdCLE9BQU8sQ0FBQyxJQUFZO1FBQ2hDLFFBQVEsQ0FBQztZQUNMLElBQUksRUFBRSxJQUFJO1lBQ1YsZUFBZSxFQUFFLDZDQUE2QztZQUM5RCxTQUFTLEVBQUUsTUFBTTtTQUNwQixDQUFDLENBQUMsU0FBUyxFQUFFLENBQUM7SUFDbkIsQ0FBQztJQU5ELDBCQU1DO0lBRUQsU0FBZ0IsVUFBVSxDQUFDLE9BQTJCLFNBQVM7UUFDM0QsUUFBUSxDQUFDO1lBQ0wsSUFBSSxFQUFFLElBQUksSUFBSSxRQUFRO1lBQ3RCLGVBQWUsRUFBRSw2Q0FBNkM7WUFDOUQsU0FBUyxFQUFFLE1BQU07U0FDcEIsQ0FBQyxDQUFDLFNBQVMsRUFBRSxDQUFDO0lBQ25CLENBQUM7SUFORCxnQ0FNQztJQUVELFNBQWdCLGVBQWUsQ0FBQyxRQUF3QjtRQUNwRCxJQUFJLFFBQVEsQ0FBQyxHQUFHLElBQUksT0FBTyxRQUFRLENBQUMsR0FBRyxJQUFJLFFBQVEsRUFBRTtZQUNqRCxVQUFVLENBQUMsUUFBUSxDQUFDLEdBQUcsQ0FBQyxDQUFDO1NBQzVCO2FBQU07WUFDSCxVQUFVLEVBQUUsQ0FBQztTQUNoQjtJQUNMLENBQUM7SUFORCwwQ0FNQzs7Ozs7O0lDL0VELElBQVksV0FNWDtJQU5ELFdBQVksV0FBVztRQUNuQiwrQ0FBUyxDQUFBO1FBQ1QsbURBQVcsQ0FBQTtRQUNYLDZDQUFRLENBQUE7UUFDUiwrQ0FBUyxDQUFBO1FBQ1QsNENBQW9DLENBQUE7SUFDeEMsQ0FBQyxFQU5XLFdBQVcsR0FBWCxtQkFBVyxLQUFYLG1CQUFXLFFBTXRCO0lBVUQsTUFBYSxhQUFjLFNBQVEsZUFBTTtRQUMzQixnQkFBZ0I7WUFDdEIsT0FBTyxJQUFJLENBQUMsVUFBVSxFQUFFLENBQUMsTUFBTSxDQUFDO1FBQ3BDLENBQUM7UUFFUyxVQUFVO1lBQ2hCLE9BQU8sSUFBSSxDQUFDLEVBQUUsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLENBQUM7UUFDbEMsQ0FBQztRQUVTLFlBQVk7WUFDbEIsS0FBSyxDQUFDLFlBQVksRUFBRSxDQUFDO1lBQ3JCLElBQUksQ0FBQywyQkFBMkIsRUFBRSxDQUFDO1FBQ3ZDLENBQUM7UUFFUywyQkFBMkI7WUFDakMsTUFBTSxJQUFJLEdBQUcsSUFBSSxDQUFDO1lBRWxCLFNBQVMsY0FBYyxDQUFDLEdBQVcsRUFBRSxRQUFxQztnQkFDdEUsR0FBRyxDQUFDLE9BQU8sQ0FBQyxRQUFRLENBQUMsQ0FBQztZQUMxQixDQUFDO1lBRUQsU0FBUyx5QkFBeUI7Z0JBQzlCLGNBQWMsQ0FBQyxJQUFJLENBQUMsRUFBRSxFQUFFO29CQUNwQixJQUFJLENBQUMsRUFBRSxDQUFDLElBQUksQ0FBQyxXQUFXLENBQUMsQ0FBQyxNQUFNLEVBQUUsQ0FBQztvQkFDbkMsSUFBSSxDQUFDLEVBQUUsQ0FBQyxJQUFJLEVBQUUsQ0FBQztnQkFDbkIsQ0FBQyxDQUFDLENBQUM7WUFDUCxDQUFDO1lBRUQsU0FBUyxvQkFBb0IsQ0FBQyxRQUFnQjtnQkFDMUMsSUFBSSxJQUFJLENBQUMsZ0JBQWdCLEVBQUUsS0FBSyxDQUFDLEVBQUU7b0JBQy9CLHlCQUF5QixFQUFFLENBQUM7aUJBQy9CO3FCQUFNO29CQUNILE1BQU0saUJBQWlCLEdBQUcsUUFBUSxDQUFDLE9BQU8sQ0FBQyxXQUFXLENBQUMsQ0FBQztvQkFDeEQsSUFBSSxpQkFBaUIsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLENBQUMsTUFBTSxLQUFLLENBQUMsRUFBRTt3QkFDL0MsY0FBYyxDQUFDLGlCQUFpQixFQUFFOzRCQUM5QixpQkFBaUIsQ0FBQyxNQUFNLEVBQUUsQ0FBQzt3QkFDL0IsQ0FBQyxDQUFDLENBQUM7cUJBQ047eUJBQU07d0JBQ0gsY0FBYyxDQUFDLFFBQVEsRUFBRTs0QkFDckIsUUFBUSxDQUFDLE1BQU0sRUFBRSxDQUFDO3dCQUN0QixDQUFDLENBQUMsQ0FBQztxQkFDTjtpQkFDSjtZQUNMLENBQUM7WUFFRCxJQUFJLENBQUMsRUFBRSxDQUFDLEVBQUUsQ0FBQyxPQUFPLEVBQUUsY0FBYyxFQUFFO2dCQUNoQyxvQkFBb0IsQ0FBQyxDQUFDLENBQUMsSUFBSSxDQUFDLENBQUMsT0FBTyxDQUFDLFFBQVEsQ0FBQyxDQUFDLENBQUM7WUFDcEQsQ0FBQyxDQUFDLENBQUM7WUFDSCxVQUFVLENBQUM7Z0JBQ1AseUJBQXlCLEVBQUUsQ0FBQztZQUNoQyxDQUFDLEVBQUUsSUFBSSxDQUFDLENBQUM7UUFDYixDQUFDO0tBQ0o7SUFwREQsc0NBb0RDO0lBRUQsU0FBZ0IsYUFBYSxDQUFDLE9BQWdCO1FBQzFDLElBQUksSUFBSSxHQUFHLE9BQU8sQ0FBQyxJQUFJLENBQUMsVUFBVSxFQUFFLENBQUM7UUFDckMsSUFBSSxHQUFHLElBQUksQ0FBQyxNQUFNLENBQUMsT0FBTyxDQUFDLElBQUksQ0FBQyxDQUFDO1FBQ2pDLE9BQU8sV0FBVyxDQUFDLElBQUksRUFBRSxnQkFBZ0IsQ0FBQyxPQUFPLENBQUMsSUFBSSxDQUFDLENBQUMsQ0FBQztJQUM3RCxDQUFDO0lBSkQsc0NBSUM7SUFFRCxTQUFTLFdBQVcsQ0FBQyxJQUFZLEVBQUUsSUFBWTtRQUMzQyxPQUFPLGNBQWMsR0FBRyxJQUFJLENBQUMsV0FBVyxFQUFFLENBQUMsVUFBVSxFQUFFLEdBQUcsSUFBSSxHQUFHLElBQUksR0FBRyxRQUFRLENBQUM7SUFDckYsQ0FBQztJQUVELFNBQWdCLGdCQUFnQixDQUFDLElBQWlCO1FBZTlDLE9BQU8sV0FBVyxDQUFDLElBQUksQ0FBQyxDQUFDO0lBQzdCLENBQUM7SUFoQkQsNENBZ0JDO0lBRUQsTUFBYSxPQUFPO1FBQ2hCLFlBQW1CLElBQWlCLEVBQVMsSUFBWSxFQUFTLE9BQWlCLEVBQUU7Ozs7O3VCQUFsRTs7Ozs7O3VCQUEwQjs7Ozs7O3VCQUFxQjs7UUFDbEUsQ0FBQztRQUVNLE9BQU8sQ0FBQyxJQUFpQjtZQUM1QixPQUFPLElBQUksQ0FBQyxJQUFJLEtBQUssSUFBSSxDQUFDO1FBQzlCLENBQUM7S0FDSjtJQVBELDBCQU9DO0lBRUQsTUFBYSxZQUFhLFNBQVEsT0FBTztRQUNyQyxZQUFZLElBQVksRUFBRSxPQUFpQixFQUFFO1lBQ3pDLEtBQUssQ0FBQyxXQUFXLENBQUMsS0FBSyxFQUFFLElBQUksRUFBRSxJQUFJLENBQUMsQ0FBQztRQUN6QyxDQUFDO0tBQ0o7SUFKRCxvQ0FJQztJQUVELE1BQWEsY0FBZSxTQUFRLE9BQU87UUFDdkMsWUFBWSxJQUFZLEVBQUUsT0FBaUIsRUFBRTtZQUN6QyxLQUFLLENBQUMsV0FBVyxDQUFDLE9BQU8sRUFBRSxJQUFJLEVBQUUsSUFBSSxDQUFDLENBQUM7UUFDM0MsQ0FBQztLQUNKO0lBSkQsd0NBSUM7SUFFRCxNQUFhLFdBQVksU0FBUSxPQUFPO1FBQ3BDLFlBQVksSUFBWSxFQUFFLE9BQWlCLEVBQUU7WUFDekMsS0FBSyxDQUFDLFdBQVcsQ0FBQyxPQUFPLEVBQUUsSUFBSSxFQUFFLElBQUksQ0FBQyxDQUFDO1FBQzNDLENBQUM7S0FDSjtJQUpELGtDQUlDO0lBRUQsTUFBYSxZQUFhLFNBQVEsT0FBTztRQUNyQyxZQUFZLElBQVksRUFBRSxPQUFpQixFQUFFO1lBQ3pDLEtBQUssQ0FBQyxXQUFXLENBQUMsS0FBSyxFQUFFLElBQUksRUFBRSxJQUFJLENBQUMsQ0FBQztRQUN6QyxDQUFDO0tBQ0o7SUFKRCxvQ0FJQzs7Ozs7O0lDaElELE1BQWEsR0FBRztRQUdaO1lBRkE7Ozs7dUJBQThCLEVBQUU7ZUFBQztZQUc3QixJQUFJLENBQUMsT0FBTyxDQUFDLGFBQWEsR0FBRyxJQUFJLHVCQUFhLENBQUMsRUFBQyxFQUFFLEVBQUUsQ0FBQyxDQUFDLGdCQUFnQixDQUFDLEVBQUMsQ0FBQyxDQUFDO1lBQzFFLElBQUksQ0FBQyxpQkFBaUIsRUFBRSxDQUFDO1FBQzdCLENBQUM7UUFFUyxpQkFBaUI7WUFDdkIsSUFBSSxDQUFDLG9CQUFvQixFQUFFLENBQUM7UUFDaEMsQ0FBQztRQUVPLG9CQUFvQjtZQUN4QixNQUFNLE9BQU8sR0FBRyxNQUFNLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQztZQUN6QyxDQUFDLENBQUMsY0FBYyxDQUFDLENBQUMsSUFBSSxDQUFDO2dCQUNuQixNQUFNLEVBQUUsR0FBRyxDQUFDLENBQUMsSUFBSSxDQUFDLENBQUM7Z0JBQ25CLElBQUksT0FBTyxHQUFHLEVBQUUsQ0FBQyxJQUFJLENBQUMsTUFBTSxDQUFDLENBQUM7Z0JBQzlCLElBQUksQ0FBQyxPQUFPLEVBQUU7b0JBQ1YsT0FBTztpQkFDVjtnQkFDRCxJQUFJLE9BQU8sQ0FBQyxNQUFNLENBQUMsQ0FBQyxFQUFFLENBQUMsQ0FBQyxLQUFLLEdBQUcsRUFBRTtvQkFDOUIsT0FBTztpQkFDVjtnQkFDRCxJQUFJLE1BQU0sR0FBRyxPQUFPLENBQUMsT0FBTyxDQUFDLEdBQUcsQ0FBQyxDQUFDO2dCQUNsQyxJQUFJLE1BQU0sSUFBSSxDQUFDLEVBQUU7b0JBQ2IsT0FBTyxHQUFHLE9BQU8sQ0FBQyxNQUFNLENBQUMsQ0FBQyxFQUFFLE1BQU0sQ0FBQyxDQUFDO2lCQUN2QztnQkFDRCxNQUFNLEdBQUcsT0FBTyxDQUFDLE9BQU8sQ0FBQyxHQUFHLENBQUMsQ0FBQztnQkFDOUIsSUFBSSxNQUFNLElBQUksQ0FBQyxFQUFFO29CQUNiLE9BQU8sR0FBRyxPQUFPLENBQUMsTUFBTSxDQUFDLENBQUMsRUFBRSxNQUFNLENBQUMsQ0FBQztpQkFDdkM7Z0JBQ0QsSUFBSSxPQUFPLEtBQUssT0FBTyxFQUFFO29CQUNyQixFQUFFLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQyxDQUFBO29CQUNyQixFQUFFLENBQUMsT0FBTyxDQUFDLFdBQVcsQ0FBQyxDQUFDLElBQUksQ0FBQyxpQkFBaUIsQ0FBQyxDQUFDLFFBQVEsQ0FBQyxRQUFRLENBQUMsQ0FBQztpQkFDdEU7WUFDTCxDQUFDLENBQUMsQ0FBQztRQUNQLENBQUM7S0FDSjtJQXJDRCxrQkFxQ0M7Ozs7O0lDdENELElBQUksQ0FBQyxHQUFHLEdBQUcsUUFBUSxDQUFDO0lBRXBCLElBQUksQ0FBQyxVQUFVLEdBQUcsVUFBVSxHQUFXLEVBQUUsWUFBb0IsQ0FBQztRQUMxRCxNQUFNLEVBQUUsR0FBRyxJQUFJLENBQUMsR0FBRyxDQUFDLEVBQUUsRUFBRSxTQUFTLENBQUMsQ0FBQztRQUNuQyxPQUFPLElBQUksQ0FBQyxLQUFLLENBQUMsR0FBRyxHQUFHLEVBQUUsQ0FBQyxHQUFHLEVBQUUsQ0FBQztJQUNyQyxDQUFDLENBQUM7SUFDRixJQUFJLENBQUMsaUJBQWlCLEdBQUcsVUFBVSxHQUFXO1FBQzFDLE9BQU8sR0FBRyxHQUFHLENBQUMsSUFBSSxDQUFDLEdBQUcsQ0FBQztJQUMzQixDQUFDLENBQUM7SUFDRixJQUFJLENBQUMsb0JBQW9CLEdBQUcsVUFBVSxHQUFXO1FBQzdDLE9BQU8sR0FBRyxHQUFHLElBQUksQ0FBQyxHQUFHLENBQUM7SUFDMUIsQ0FBQyxDQUFDO0lBQ0YsSUFBSSxDQUFDLGNBQWMsR0FBRyxVQUFVLEdBQVc7UUFDdkMsT0FBTyxJQUFJLENBQUMsR0FBRyxDQUFDLEdBQUcsQ0FBQyxJQUFJLElBQUksQ0FBQyxHQUFHLENBQUM7SUFDckMsQ0FBQyxDQUFDO0lBQ0YsSUFBSSxDQUFDLFdBQVcsR0FBRyxVQUFVLENBQVMsRUFBRSxDQUFTO1FBQzdDLE9BQU8sSUFBSSxDQUFDLGNBQWMsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxDQUFDLENBQUM7SUFDdEMsQ0FBQyxDQUFDO0lBR0YsSUFBSSxDQUFDLElBQUksR0FBRyxVQUFVLENBQVMsRUFBRSxJQUFZO1FBQ3pDLE9BQU8sSUFBSSxDQUFDLEdBQUcsQ0FBQyxDQUFDLENBQUMsR0FBRyxJQUFJLENBQUMsR0FBRyxDQUFDLElBQUksQ0FBQyxDQUFDO0lBQ3hDLENBQUMsQ0FBQztJQUtGLE1BQU0sQ0FBQyxTQUFTLENBQUMsQ0FBQyxHQUFHO1FBQ2pCLE1BQU0sU0FBUyxHQUFHO1lBQ2QsR0FBRyxFQUFFLE9BQU87WUFDWixHQUFHLEVBQUUsTUFBTTtZQUNYLEdBQUcsRUFBRSxNQUFNO1lBRVgsR0FBRyxFQUFFLFFBQVE7WUFDYixHQUFHLEVBQUUsT0FBTztTQUNmLENBQUM7UUFDRixPQUFPLElBQUksQ0FBQyxPQUFPLENBQUMsVUFBVSxFQUFFLFVBQVUsQ0FBUztZQUMvQyxPQUFhLFNBQVUsQ0FBQyxDQUFDLENBQUMsQ0FBQztRQUMvQixDQUFDLENBQUMsQ0FBQztJQUNQLENBQUMsQ0FBQztJQUVGLE1BQU0sQ0FBQyxTQUFTLENBQUMsUUFBUSxHQUFHO1FBRXhCLE9BQU8sSUFBSSxDQUFDLE1BQU0sQ0FBQyxDQUFDLENBQUMsQ0FBQyxXQUFXLEVBQUUsR0FBRyxJQUFJLENBQUMsS0FBSyxDQUFDLENBQUMsQ0FBQyxDQUFDO0lBQ3hELENBQUMsQ0FBQztJQUVGLE1BQU0sQ0FBQyxTQUFTLENBQUMsTUFBTSxHQUFHLFVBQXdCLElBQWMsRUFBRSxNQUE4QjtRQUM1RixJQUFJLEdBQUcsR0FBRyxJQUFJLENBQUM7UUFDZixJQUFJLENBQUMsT0FBTyxDQUFDLENBQUMsR0FBVyxFQUFFLEtBQWEsRUFBRSxFQUFFO1lBQ3hDLEdBQUcsR0FBRyxHQUFHLENBQUMsT0FBTyxDQUFDLEdBQUcsR0FBRyxLQUFLLEdBQUcsR0FBRyxFQUFFLE1BQU0sQ0FBQyxDQUFDLENBQUMsTUFBTSxDQUFDLEdBQUcsQ0FBQyxDQUFDLENBQUMsQ0FBQyxHQUFHLENBQUMsQ0FBQztRQUNyRSxDQUFDLENBQUMsQ0FBQztRQUNILE9BQU8sR0FBRyxDQUFDO0lBQ2YsQ0FBQyxDQUFBO0lBRUQsTUFBTSxDQUFDLFNBQVMsQ0FBQyxLQUFLLEdBQUc7UUFDckIsT0FBTyxJQUFJLENBQUMsT0FBTyxDQUFDLFFBQVEsRUFBRSxNQUFNLENBQUMsQ0FBQztJQUMxQyxDQUFDLENBQUM7SUFDRixNQUFNLENBQUMsU0FBUyxDQUFDLFVBQVUsR0FBRyxVQUFVLE1BQWMsRUFBRSxPQUFlO1FBQ25FLE9BQU8sSUFBSSxDQUFDLEtBQUssQ0FBQyxNQUFNLENBQUMsQ0FBQyxJQUFJLENBQUMsT0FBTyxDQUFDLENBQUM7SUFDNUMsQ0FBQyxDQUFDO0lBRUYsTUFBTSxDQUFDLFNBQVMsQ0FBQyxPQUFPLEdBQUc7UUFDdkIsT0FBTyxJQUFJLENBQUMsTUFBTSxDQUFDLENBQUMsQ0FBQyxDQUFDLFdBQVcsRUFBRSxHQUFHLElBQUksQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLENBQUM7SUFDeEQsQ0FBQyxDQUFDO0lBR0YsTUFBTSxDQUFDLFNBQVMsQ0FBQyxLQUFLLEdBQUcsVUFBd0IsS0FBYztRQUMzRCxJQUFJLEtBQUssS0FBSyxTQUFTLEVBQUU7WUFDckIsT0FBTyxJQUFJLENBQUMsT0FBTyxDQUFDLElBQUksTUFBTSxDQUFDLE9BQU8sQ0FBQyxFQUFFLEVBQUUsQ0FBQyxDQUFDO1NBQ2hEO1FBQ0QsT0FBTyxJQUFJLENBQUMsT0FBTyxDQUFDLElBQUksTUFBTSxDQUFDLEdBQUcsR0FBRyxNQUFNLENBQUMsQ0FBQyxDQUFDLEtBQUssQ0FBQyxHQUFHLEtBQUssQ0FBQyxFQUFFLEVBQUUsQ0FBQyxDQUFDO0lBQ3ZFLENBQUMsQ0FBQztJQUNGLE1BQU0sQ0FBQyxTQUFTLENBQUMsS0FBSyxHQUFHLFVBQXdCLEtBQWM7UUFDM0QsSUFBSSxLQUFLLEtBQUssU0FBUyxFQUFFO1lBQ3JCLE9BQU8sSUFBSSxDQUFDLE9BQU8sQ0FBQyxJQUFJLE1BQU0sQ0FBQyxPQUFPLENBQUMsRUFBRSxFQUFFLENBQUMsQ0FBQztTQUNoRDtRQUNELE9BQU8sSUFBSSxDQUFDLE9BQU8sQ0FBQyxJQUFJLE1BQU0sQ0FBQyxJQUFJLEdBQUcsTUFBTSxDQUFDLENBQUMsQ0FBQyxLQUFLLENBQUMsR0FBRyxJQUFJLENBQUMsRUFBRSxFQUFFLENBQUMsQ0FBQztJQUN2RSxDQUFDLENBQUM7SUFDRixNQUFNLENBQUMsU0FBUyxDQUFDLE1BQU0sR0FBRyxVQUF3QixLQUFjO1FBQzVELElBQUksS0FBSyxJQUFJLFNBQVMsRUFBRTtZQUNwQixPQUFPLElBQUksQ0FBQyxJQUFJLEVBQUUsQ0FBQztTQUN0QjtRQUNELE9BQU8sSUFBSSxDQUFDLEtBQUssQ0FBQyxLQUFLLENBQUMsQ0FBQyxLQUFLLENBQUMsS0FBSyxDQUFDLENBQUM7SUFDMUMsQ0FBQyxDQUFBO0lBTUQsTUFBTSxDQUFDLENBQUMsR0FBRyxVQUFVLENBQVM7UUFDMUIsT0FBTyxNQUFNLENBQUMsQ0FBQyxDQUFDLENBQUMsT0FBTyxDQUFDLHFCQUFxQixFQUFFLE1BQU0sQ0FBQyxDQUFDO0lBQzVELENBQUMsQ0FBQztJQWNGLE1BQU0sQ0FBQyxJQUFJLEdBQUcsVUFBVSxNQUFXLEVBQUUsSUFBYztRQUMvQyxPQUFPLElBQUksQ0FBQyxNQUFNLENBQUMsQ0FBQyxHQUFHLEVBQUUsR0FBRyxFQUFFLEVBQUU7WUFDNUIsSUFBSSxNQUFNLElBQUksTUFBTSxDQUFDLGNBQWMsQ0FBQyxHQUFHLENBQUMsRUFBRTtnQkFDdEMsR0FBRyxDQUFDLEdBQUcsQ0FBQyxHQUFHLE1BQU0sQ0FBQyxHQUFHLENBQUMsQ0FBQzthQUMxQjtZQUNELE9BQU8sR0FBRyxDQUFDO1FBQ2YsQ0FBQyxFQUF5QixFQUFFLENBQUMsQ0FBQztJQUNsQyxDQUFDLENBQUE7Ozs7OztJQ25IRCxNQUFhLFNBQVUsU0FBUSxLQUFLO1FBR2hDLFlBQW1CLE9BQWU7WUFDOUIsS0FBSyxDQUFDLE9BQU8sQ0FBQyxDQUFDOzs7Ozt1QkFEQTs7WUFFZixJQUFJLENBQUMsSUFBSSxHQUFHLFdBQVcsQ0FBQztZQUN4QixJQUFJLENBQUMsT0FBTyxHQUFHLE9BQU8sQ0FBQztRQUUzQixDQUFDO1FBRU0sUUFBUTtZQUNYLE9BQU8sSUFBSSxDQUFDLElBQUksR0FBRyxJQUFJLEdBQUcsSUFBSSxDQUFDLE9BQU8sQ0FBQztRQUMzQyxDQUFDO0tBQ0o7SUFiRCw4QkFhQztJQUVELE1BQWEsdUJBQXdCLFNBQVEsU0FBUztLQUNyRDtJQURELDBEQUNDO0lBRUQsTUFBYSx3QkFBeUIsU0FBUSxTQUFTO0tBQ3REO0lBREQsNERBQ0M7Ozs7OztJQ1pELE1BQWEsbUJBQW1CO1FBR3JCLFFBQVEsQ0FBQyxHQUFXO1lBQ3ZCLElBQUksSUFBSSxDQUFDLFlBQVksQ0FBQyxHQUFHLENBQUMsRUFBRTtnQkFDeEIsSUFBSSxJQUFJLENBQUMsT0FBTyxDQUFDLEdBQUcsQ0FBQyxDQUFDLElBQUksRUFBRSxDQUFDLE1BQU0sR0FBRyxDQUFDLEVBQUU7b0JBQ3JDLE9BQU8sQ0FBQyxtQkFBbUIsQ0FBQyxpQkFBaUIsQ0FBQyxDQUFDO2lCQUNsRDthQUNKO1lBQ0QsT0FBTyxFQUFFLENBQUM7UUFDZCxDQUFDOztJQVZMLGtEQVdDO0lBVkc7Ozs7ZUFBMkMsd0JBQXdCO09BQUM7SUFnQnhFLFNBQWdCLGlCQUFpQjtRQUM3QixPQUFPO1lBQ0gsSUFBSSxtQkFBbUIsRUFBRTtTQUM1QixDQUFDO0lBQ04sQ0FBQztJQUpELDhDQUlDO0lBRUQsU0FBZ0IsVUFBVSxDQUFDLEdBQVcsRUFBRSxVQUEwQjtRQUM5RCxJQUFJLENBQUMsVUFBVSxFQUFFO1lBQ2IsVUFBVSxHQUFHLGlCQUFpQixFQUFFLENBQUM7U0FDcEM7UUFDRCxJQUFJLE1BQU0sR0FBYSxFQUFFLENBQUM7UUFDMUIsVUFBVSxDQUFDLE9BQU8sQ0FBQyxVQUFVLFNBQXNCO1lBQy9DLE1BQU0sR0FBRyxNQUFNLENBQUMsTUFBTSxDQUFDLFNBQVMsQ0FBQyxRQUFRLENBQUMsR0FBRyxDQUFDLENBQUMsQ0FBQztRQUNwRCxDQUFDLENBQUMsQ0FBQztRQUNILE9BQU8sTUFBTSxDQUFDO0lBQ2xCLENBQUM7SUFURCxnQ0FTQztJQUVELFNBQWdCLFFBQVEsQ0FBQyxLQUFhO1FBRWxDLE1BQU0sSUFBSSxHQUFrQyxFQUFFLENBQUM7UUFDL0MsR0FBRyxDQUFDLEtBQUssQ0FBQyxDQUFDLElBQUksQ0FBQyxDQUFDLEtBQUssRUFBRSxJQUFJLEVBQUUsRUFBRTtZQUM1QixNQUFNLElBQUksR0FBRyxJQUFJLENBQUMsWUFBWSxDQUFDLE1BQU0sQ0FBQyxDQUFDO1lBQ3ZDLElBQUksQ0FBQyxJQUFJLEVBQUU7Z0JBQ1AsT0FBTzthQUNWO1lBQ0QsSUFBSSxDQUFDLElBQUksQ0FBQztnQkFDTixJQUFJO2dCQUNKLEtBQUssRUFBRSxJQUFJLENBQUMsT0FBTyxDQUFDLENBQUMsQ0FBQyxJQUFJLENBQUMsQ0FBQzthQUMvQixDQUFDLENBQUM7UUFDUCxDQUFDLENBQUMsQ0FBQztRQUNILE9BQU8sSUFBSSxDQUFDO0lBQ2hCLENBQUM7SUFkRCw0QkFjQztJQUVELFNBQWdCLFNBQVMsQ0FBQyxLQUFhLEVBQUUsRUFBaUQ7UUFDdEYsT0FBTyxHQUFHLENBQUMsS0FBSyxDQUFDLENBQUMsSUFBSSxDQUFDLFVBQVUsS0FBYSxFQUFFLEVBQWU7WUFDM0QsSUFBSSxLQUFLLEtBQUssRUFBRSxDQUFDLENBQUMsQ0FBQyxFQUFFLENBQUMsRUFBRSxLQUFLLENBQUMsRUFBRTtnQkFDNUIsT0FBTyxLQUFLLENBQUM7YUFDaEI7WUFDRCxPQUFPLFNBQVMsQ0FBQztRQUNyQixDQUFDLENBQUMsQ0FBQztJQUNQLENBQUM7SUFQRCw4QkFPQztJQUVELFNBQWdCLEdBQUcsQ0FBQyxLQUFhO1FBQzdCLE9BQU8sQ0FBQyxDQUFRLEtBQUssQ0FBQyxDQUFDLENBQUUsQ0FBQyxRQUFRLENBQUMsQ0FBQztJQUN4QyxDQUFDO0lBRkQsa0JBRUM7SUFFRCxJQUFZLFNBYVg7SUFiRCxXQUFZLFNBQVM7UUFDakIsOEJBQWlCLENBQUE7UUFDakIsa0NBQXFCLENBQUE7UUFDckIsMEJBQWEsQ0FBQTtRQUNiLDhCQUFpQixDQUFBO1FBQ2pCLDRCQUFlLENBQUE7UUFDZixrQ0FBcUIsQ0FBQTtRQUNyQiw0QkFBZSxDQUFBO1FBQ2YsNEJBQWUsQ0FBQTtRQUNmLDhCQUFpQixDQUFBO1FBQ2pCLDhCQUFpQixDQUFBO1FBQ2pCLGtDQUFxQixDQUFBO1FBQ3JCLCtCQUFrQixDQUFBO0lBQ3RCLENBQUMsRUFiVyxTQUFTLEdBQVQsaUJBQVMsS0FBVCxpQkFBUyxRQWFwQjtJQUVZLFFBQUEsY0FBYyxHQUFHLDZCQUE2QixDQUFDO0lBRTVELE1BQWEsSUFBNEIsU0FBUSxlQUFnQjtRQUFqRTs7WUFFSTs7Ozs7ZUFBZ0M7WUFDaEM7Ozs7O2VBQW9DO1lBQ3BDOzs7OztlQUE2QztZQUM3Qzs7Ozs7ZUFBZ0M7WUFDaEM7Ozs7O2VBQWtDO1FBdVR0QyxDQUFDO1FBclRVLE1BQU0sQ0FBQyxPQUFPLENBQUMsR0FBVztZQUM3QixJQUFVLEdBQUcsQ0FBQyxHQUFHLENBQUMsQ0FBQyxDQUFFLENBQUMsTUFBTSxDQUFDLEtBQUssVUFBVSxFQUFFO2dCQUMxQyxPQUFPLEdBQUcsQ0FBQyxFQUFFLENBQUMsVUFBVSxDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDO2FBQ3JDO1lBQ0QsT0FBTyxHQUFHLENBQUMsR0FBRyxFQUFFLENBQUM7UUFDckIsQ0FBQztRQUVNLE1BQU0sQ0FBQyxZQUFZLENBQUMsR0FBVztZQUNsQyxPQUFPLEdBQUcsQ0FBQyxFQUFFLENBQUMsWUFBWSxDQUFDLENBQUM7UUFDaEMsQ0FBQztRQUVNLEdBQUc7WUFDTixPQUFPLEdBQUcsQ0FBQyxJQUFJLENBQUMsRUFBRSxDQUFDLENBQUM7UUFDeEIsQ0FBQztRQUVNLGFBQWE7WUFDaEIsT0FBTyxJQUFJLENBQUMsR0FBRyxFQUFFLENBQUMsTUFBTSxDQUFDO2dCQUNyQixNQUFNLEdBQUcsR0FBRyxDQUFDLENBQUMsSUFBSSxDQUFDLENBQUM7Z0JBQ3BCLE9BQU8sR0FBRyxDQUFDLEVBQUUsQ0FBQyxlQUFlLENBQUMsQ0FBQztZQUNuQyxDQUFDLENBQUMsQ0FBQztRQUNQLENBQUM7UUFFTSxRQUFRO1lBQ1gsSUFBSSxDQUFDLFlBQVksRUFBRSxDQUFDO1lBQ3BCLElBQUksTUFBTSxHQUFvQyxFQUFFLENBQUM7WUFDakQsSUFBSSxDQUFDLGFBQWEsRUFBRSxDQUFDLElBQUksQ0FBQztnQkFDdEIsTUFBTSxHQUFHLEdBQUcsQ0FBQyxDQUFDLElBQUksQ0FBQyxDQUFDO2dCQUNwQixNQUFNLFFBQVEsR0FBRyxVQUFVLENBQUMsR0FBRyxDQUFDLENBQUM7Z0JBQ2pDLElBQUksUUFBUSxDQUFDLE1BQU0sRUFBRTtvQkFDakIsTUFBTSxDQUFDLElBQUksQ0FBQyxDQUFDLEdBQUcsRUFBRSxRQUFRLENBQUMsR0FBRyxDQUFDLENBQUMsS0FBYSxFQUFFLEVBQUUsR0FBRyxPQUFPLElBQUksc0JBQVksQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQztpQkFDNUY7WUFDTCxDQUFDLENBQUMsQ0FBQztZQUNILElBQUksTUFBTSxDQUFDLE1BQU0sRUFBRTtnQkFDZixJQUFJLENBQUMsVUFBVSxDQUFDLE1BQU0sQ0FBQyxDQUFDO2dCQUN4QixPQUFPLEtBQUssQ0FBQzthQUNoQjtZQUNELE9BQU8sSUFBSSxDQUFDO1FBQ2hCLENBQUM7UUFFTSxVQUFVO1lBQ2IsTUFBTSxJQUFJLEdBQUcsSUFBSSxDQUFDO1lBQ2xCLE9BQU8sSUFBSSxDQUFDLEdBQUcsRUFBRSxDQUFDLE1BQU0sQ0FBQztnQkFDckIsT0FBTyxDQUFDLENBQUMsSUFBSSxDQUFDLENBQUMsUUFBUSxDQUFDLElBQUksQ0FBQyxlQUFlLENBQUMsQ0FBQztZQUNsRCxDQUFDLENBQUMsQ0FBQztRQUNQLENBQUM7UUFFTSxTQUFTO1lBQ1osT0FBTyxJQUFJLENBQUMsRUFBRSxDQUFDLFFBQVEsQ0FBQyxJQUFJLENBQUMsZUFBZSxDQUFDLENBQUM7UUFDbEQsQ0FBQztRQU1NLFlBQVk7WUFDZixJQUFJLENBQUMsVUFBVSxFQUFFLENBQUMsSUFBSSxDQUFDLENBQUMsS0FBYSxFQUFFLEVBQWUsRUFBRSxFQUFFO2dCQUN0RCxJQUFJLENBQUMsY0FBYyxDQUFDLENBQUMsQ0FBQyxFQUFFLENBQUMsQ0FBQyxDQUFDO1lBQy9CLENBQUMsQ0FBQyxDQUFDO1lBQ0gsSUFBSSxDQUFDLHNCQUFzQixFQUFFLENBQUMsTUFBTSxFQUFFLENBQUM7WUFDdkMsSUFBSSxDQUFDLEVBQUUsQ0FBQyxXQUFXLENBQUMsSUFBSSxDQUFDLGVBQWUsQ0FBQyxDQUFDO1FBQzlDLENBQUM7UUFFTSxNQUFNO1lBQ1QsSUFBSSxDQUFDLFlBQVksRUFBRSxDQUFDO1lBQ3BCLElBQUksSUFBSSxDQUFDLGNBQWMsRUFBRTtnQkFDckIsSUFBSSxDQUFDLElBQUksRUFBRSxDQUFDO2FBQ2Y7aUJBQU0sSUFBSSxJQUFJLENBQUMsUUFBUSxFQUFFLEVBQUU7Z0JBQ3hCLElBQUksQ0FBQyxJQUFJLEVBQUUsQ0FBQzthQUNmO1FBQ0wsQ0FBQztRQUVNLElBQUk7WUFDUCxJQUFJLENBQUMsc0JBQXNCLEVBQUUsQ0FBQztZQUM5QixPQUFPLElBQUksQ0FBQyxZQUFZLENBQUMsSUFBSSxDQUFDLEdBQUcsRUFBRSxFQUFFLElBQUksQ0FBQyxRQUFRLEVBQUUsQ0FBQyxDQUFDO1FBQzFELENBQUM7UUFLTSxVQUFVLENBQUMsTUFBc0Q7WUFDcEUsSUFBSSxVQUFVLEdBQW1CLEVBQUUsQ0FBQztZQUNwQyxNQUFNLENBQUMsT0FBTyxDQUFDLENBQUMsR0FBNEMsRUFBRSxFQUFFO2dCQUM1RCxJQUFJLEtBQUssQ0FBQyxPQUFPLENBQUMsR0FBRyxDQUFDLEVBQUU7b0JBQ3BCLE1BQU0sQ0FBQyxHQUFHLEVBQUUsUUFBUSxDQUFDLEdBQUcsR0FBRyxDQUFDO29CQUM1QixJQUFJLENBQUMsWUFBWSxDQUFDLEdBQUcsRUFBRSxRQUFRLENBQUMsQ0FBQztpQkFDcEM7cUJBQU07b0JBQ0gsVUFBVSxDQUFDLElBQUksQ0FBQyxHQUFHLENBQUMsQ0FBQztpQkFDeEI7WUFDTCxDQUFDLENBQUMsQ0FBQztZQUNILElBQUksQ0FBQyxjQUFjLENBQUMsVUFBVSxDQUFDLENBQUM7WUFDaEMsSUFBSSxDQUFDLGtCQUFrQixFQUFFLENBQUM7UUFDOUIsQ0FBQztRQUVNLE1BQU0sQ0FBQyxTQUFTLENBQUMsTUFBYztZQUNsQyxNQUFNLFFBQVEsR0FBRyxHQUFHLEVBQUU7Z0JBQ2xCLE1BQU0sUUFBUSxHQUFHLE1BQU0sQ0FBQyxJQUFJLENBQUMsTUFBTSxDQUFDLENBQUM7Z0JBQ3JDLE9BQU8sUUFBUSxLQUFLLFNBQVMsQ0FBQyxDQUFDLENBQUMsRUFBRSxDQUFDLENBQUMsQ0FBQyxRQUFRLENBQUMsV0FBVyxFQUFFLENBQUM7WUFDaEUsQ0FBQyxDQUFDO1lBQ0YsSUFBSSxhQUFhLENBQUM7WUFDbEIsUUFBUSxNQUFNLENBQUMsQ0FBQyxDQUFDLENBQUMsT0FBTyxFQUFFO2dCQUN2QixLQUFLLE9BQU87b0JBQ1IsYUFBYSxHQUFHLFFBQVEsRUFBRSxDQUFDO29CQUMzQixRQUFRLGFBQWEsRUFBRTt3QkFDbkIsS0FBSyxNQUFNOzRCQUNQLE9BQU8sU0FBUyxDQUFDLFNBQVMsQ0FBQzt3QkFDL0IsS0FBSyxPQUFPOzRCQUNSLE9BQU8sU0FBUyxDQUFDLEtBQUssQ0FBQzt3QkFDM0IsS0FBSyxRQUFROzRCQUNULE9BQU8sU0FBUyxDQUFDLE1BQU0sQ0FBQzt3QkFDNUIsS0FBSyxRQUFROzRCQUNULE9BQU8sU0FBUyxDQUFDLE1BQU0sQ0FBQzt3QkFDNUIsS0FBSyxVQUFVOzRCQUNYLE9BQU8sU0FBUyxDQUFDLFFBQVEsQ0FBQzt3QkFDOUIsS0FBSyxNQUFNOzRCQUNQLE9BQU8sU0FBUyxDQUFDLElBQUksQ0FBQzt3QkFDMUIsS0FBSyxRQUFROzRCQUNULE9BQU8sU0FBUyxDQUFDLE1BQU0sQ0FBQzt3QkFDNUIsS0FBSyxPQUFPOzRCQUNSLE9BQU8sU0FBUyxDQUFDLEtBQUssQ0FBQzt3QkFDM0IsS0FBSyxVQUFVOzRCQUNYLE9BQU8sU0FBUyxDQUFDLFFBQVEsQ0FBQzt3QkFDOUIsS0FBSyxPQUFPOzRCQUNSLE9BQU8sU0FBUyxDQUFDLEtBQUssQ0FBQztxQkFDOUI7b0JBQ0QsTUFBTTtnQkFDVixLQUFLLFVBQVU7b0JBQ1gsT0FBTyxTQUFTLENBQUMsUUFBUSxDQUFDO2dCQUM5QixLQUFLLFFBQVE7b0JBQ1QsT0FBTyxTQUFTLENBQUMsTUFBTSxDQUFDO2dCQUM1QixLQUFLLFFBQVE7b0JBQ1QsYUFBYSxHQUFHLFFBQVEsRUFBRSxDQUFDO29CQUMzQixJQUFJLGFBQWEsS0FBSyxFQUFFLElBQUksYUFBYSxLQUFLLFFBQVEsRUFBRTt3QkFDcEQsT0FBTyxTQUFTLENBQUMsTUFBTSxDQUFDO3FCQUMzQjtvQkFDRCxJQUFJLGFBQWEsS0FBSyxRQUFRLEVBQUU7d0JBQzVCLE9BQU8sU0FBUyxDQUFDLE1BQU0sQ0FBQztxQkFDM0I7b0JBQ0QsTUFBTTthQUNiO1lBQ0QsTUFBTSxJQUFJLEtBQUssQ0FBQyxvQkFBb0IsQ0FBQyxDQUFDO1FBQzFDLENBQUM7UUFFUyxjQUFjLENBQUMsTUFBc0I7WUFDM0MsSUFBSSxNQUFNLENBQUMsTUFBTSxFQUFFO2dCQUNmLE1BQU0sUUFBUSxHQUFXLGlDQUFpQyxHQUFHLE1BQU0sQ0FBQyxHQUFHLENBQUMsdUJBQWEsQ0FBQyxDQUFDLElBQUksQ0FBQyxJQUFJLENBQUMsR0FBRyxRQUFRLENBQUM7Z0JBQzdHLElBQUksQ0FBQyxzQkFBc0IsRUFBRTtxQkFDeEIsT0FBTyxDQUFDLFFBQVEsQ0FBQyxDQUFDO2FBQzFCO1lBQ0QsSUFBSSxDQUFDLEVBQUUsQ0FBQyxRQUFRLENBQUMsSUFBSSxDQUFDLGVBQWUsQ0FBQyxDQUFDO1FBQzNDLENBQUM7UUFFUyxZQUFZLENBQUMsR0FBVyxFQUFFLE1BQXNCO1lBQ3RELE1BQU0sZUFBZSxHQUFHLElBQUksQ0FBQyxlQUFlLENBQUM7WUFDN0MsR0FBRyxDQUFDLFFBQVEsQ0FBQyxlQUFlLENBQUMsQ0FBQyxPQUFPLENBQUMsR0FBRyxHQUFHLElBQUksQ0FBQyxtQkFBbUIsQ0FBQyxDQUFDLFFBQVEsQ0FBQyxlQUFlLENBQUMsQ0FBQyxRQUFRLENBQUMsV0FBVyxDQUFDLENBQUM7WUFDdEgsR0FBRyxDQUFDLEtBQUssQ0FBQyxNQUFNLENBQUMsR0FBRyxDQUFDLHVCQUFhLENBQUMsQ0FBQyxJQUFJLENBQUMsSUFBSSxDQUFDLENBQUMsQ0FBQztRQUNwRCxDQUFDO1FBRVMsY0FBYyxDQUFDLEdBQVc7WUFDaEMsTUFBTSxVQUFVLEdBQUcsR0FBRyxDQUFDLFdBQVcsQ0FBQyxJQUFJLENBQUMsZUFBZSxDQUFDLENBQUMsT0FBTyxDQUFDLEdBQUcsR0FBRyxJQUFJLENBQUMsbUJBQW1CLENBQUMsQ0FBQztZQUNqRyxJQUFJLENBQUMsVUFBVSxDQUFDLElBQUksQ0FBQyxHQUFHLEdBQUcsSUFBSSxDQUFDLGVBQWUsQ0FBQyxDQUFDLE1BQU0sRUFBRTtnQkFDckQsVUFBVSxDQUFDLFdBQVcsQ0FBQyxJQUFJLENBQUMsZUFBZSxDQUFDLENBQUMsV0FBVyxDQUFDLFdBQVcsQ0FBQyxDQUFDO2FBQ3pFO1lBQ0QsR0FBRyxDQUFDLElBQUksQ0FBQyxRQUFRLENBQUMsQ0FBQyxNQUFNLEVBQUUsQ0FBQztRQUNoQyxDQUFDO1FBRVMsc0JBQXNCO1lBQzVCLE1BQU0saUJBQWlCLEdBQUcsSUFBSSxDQUFDLDRCQUE0QixDQUFDO1lBQzVELElBQUksWUFBWSxHQUFHLElBQUksQ0FBQyxFQUFFLENBQUMsSUFBSSxDQUFDLEdBQUcsR0FBRyxpQkFBaUIsQ0FBQyxDQUFDO1lBQ3pELElBQUksQ0FBQyxZQUFZLENBQUMsTUFBTSxFQUFFO2dCQUN0QixZQUFZLEdBQUcsQ0FBQyxDQUFDLGNBQWMsR0FBRyxpQkFBaUIsR0FBRyxVQUFVLENBQUMsQ0FBQyxTQUFTLENBQUMsSUFBSSxDQUFDLEVBQUUsQ0FBQyxDQUFDO2FBQ3hGO1lBQ0QsT0FBTyxZQUFZLENBQUM7UUFDeEIsQ0FBQztRQUVTLElBQUk7WUFDVixLQUFLLENBQUMsSUFBSSxFQUFFLENBQUM7WUFDYixJQUFJLENBQUMsY0FBYyxHQUFHLEtBQUssQ0FBQztZQUM1QixJQUFJLENBQUMsbUJBQW1CLEdBQUcsWUFBWSxDQUFDO1lBQ3hDLElBQUksQ0FBQyw0QkFBNEIsR0FBRyxVQUFVLENBQUM7WUFDL0MsSUFBSSxDQUFDLGVBQWUsR0FBRyxJQUFJLENBQUMsc0JBQXNCLENBQUM7WUFDbkQsSUFBSSxDQUFDLGNBQWMsR0FBRyxzQkFBYyxDQUFDO1lBQ3JDLElBQUksQ0FBQyxFQUFFLENBQUMsSUFBSSxDQUFDLFlBQVksRUFBRSxZQUFZLENBQUMsQ0FBQztRQUM3QyxDQUFDO1FBRVMsWUFBWTtZQUNsQixJQUFJLENBQUMsRUFBRSxDQUFDLEVBQUUsQ0FBQyxRQUFRLEVBQUUsR0FBRyxFQUFFO2dCQUN0QixJQUFJLENBQUMsTUFBTSxFQUFFLENBQUM7Z0JBQ2QsT0FBTyxLQUFLLENBQUM7WUFDakIsQ0FBQyxDQUFDLENBQUM7WUFDSCxNQUFNLElBQUksR0FBRyxJQUFJLENBQUM7WUFDbEIsSUFBSSxDQUFDLGFBQWEsRUFBRSxDQUFDLEVBQUUsQ0FBQyxJQUFJLENBQUMsY0FBYyxFQUFFO2dCQUN6QyxNQUFNLEdBQUcsR0FBRyxDQUFDLENBQUMsSUFBSSxDQUFDLENBQUM7Z0JBQ3BCLElBQUksR0FBRyxDQUFDLFFBQVEsQ0FBQyxJQUFJLENBQUMsZUFBZSxDQUFDLEVBQUU7b0JBQ3BDLElBQUksQ0FBQyxjQUFjLENBQUMsR0FBRyxDQUFDLENBQUM7aUJBQzVCO1lBQ0wsQ0FBQyxDQUFDLENBQUM7UUFDUCxDQUFDO1FBRVMsWUFBWSxDQUFDLEdBQVcsRUFBRSxXQUFnQjtZQUNoRCxNQUFNLFlBQVksR0FBRyxJQUFJLENBQUMsWUFBWSxFQUFFLENBQUM7WUFDekMsWUFBWSxDQUFDLEdBQUcsR0FBRyxHQUFHLENBQUM7WUFDdkIsWUFBWSxDQUFDLElBQUksR0FBRyxXQUFXLENBQUM7WUFDaEMsT0FBTyxDQUFDLENBQUMsSUFBSSxDQUFDLFlBQVksQ0FBQyxDQUFDO1FBQ2hDLENBQUM7UUFFUyxZQUFZO1lBQ2xCLE1BQU0sSUFBSSxHQUFHLElBQUksQ0FBQztZQUNsQixPQUFPO2dCQUNILFVBQVUsQ0FBQyxLQUFnQixFQUFFLFFBQTRCO29CQUNyRCxPQUFPLElBQUksQ0FBQyxVQUFVLENBQUMsS0FBSyxFQUFFLFFBQVEsQ0FBQyxDQUFDO2dCQUM1QyxDQUFDO2dCQUNELE9BQU8sQ0FBQyxJQUFTLEVBQUUsVUFBa0IsRUFBRSxLQUFnQjtvQkFDbkQsT0FBTyxJQUFJLENBQUMsV0FBVyxDQUFDLElBQUksRUFBRSxVQUFVLEVBQUUsS0FBSyxDQUFDLENBQUM7Z0JBQ3JELENBQUM7Z0JBQ0QsS0FBSyxDQUFDLEtBQWdCLEVBQUUsVUFBa0IsRUFBRSxXQUFtQjtvQkFDM0QsT0FBTyxJQUFJLENBQUMsU0FBUyxDQUFDLEtBQUssRUFBRSxVQUFVLEVBQUUsV0FBVyxDQUFDLENBQUM7Z0JBQzFELENBQUM7Z0JBQ0QsTUFBTSxFQUFFLElBQUksQ0FBQyxZQUFZLEVBQUU7YUFDOUIsQ0FBQztRQUNOLENBQUM7UUFFUyxZQUFZO1lBQ2xCLE9BQU8sSUFBSSxDQUFDLEVBQUUsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLElBQUksS0FBSyxDQUFDO1FBQzNDLENBQUM7UUFFUyxVQUFVLENBQUMsS0FBZ0IsRUFBRSxRQUE0QjtRQUNuRSxDQUFDO1FBRVMsV0FBVyxDQUFDLFlBQWlCLEVBQUUsVUFBa0IsRUFBRSxLQUFnQjtZQUN6RSxJQUFJLENBQUMscUJBQXFCLEVBQUUsQ0FBQztZQUM3QixJQUFJLENBQUMsY0FBYyxDQUFDLFlBQVksQ0FBQyxDQUFDO1FBQ3RDLENBQUM7UUFFUyxTQUFTLENBQUMsS0FBZ0IsRUFBRSxVQUFrQixFQUFFLFdBQW1CO1lBQ3pFLElBQUksQ0FBQyxxQkFBcUIsRUFBRSxDQUFDO1lBRTdCLEtBQUssQ0FBQyxZQUFZLENBQUMsQ0FBQztRQUN4QixDQUFDO1FBRVMsUUFBUTtZQUNkLE9BQU8sUUFBUSxDQUFDLElBQUksQ0FBQyxFQUFFLENBQUMsQ0FBQztRQUM3QixDQUFDO1FBRVMsR0FBRztZQUNULE9BQU8sSUFBSSxDQUFDLEVBQUUsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLElBQVUsTUFBTyxDQUFDLFFBQVEsQ0FBQyxJQUFJLENBQUM7UUFDakUsQ0FBQztRQUVTLHFCQUFxQjtZQUMzQixJQUFJLENBQUMsZUFBZSxFQUFFLENBQUMsSUFBSSxDQUFDLFVBQVUsRUFBRSxLQUFLLENBQUMsQ0FBQztRQUNuRCxDQUFDO1FBRVMsc0JBQXNCO1lBQzVCLElBQUksQ0FBQyxlQUFlLEVBQUUsQ0FBQyxJQUFJLENBQUMsVUFBVSxFQUFFLElBQUksQ0FBQyxDQUFDO1FBQ2xELENBQUM7UUFFUyxlQUFlO1lBQ3JCLE9BQU8sSUFBSSxDQUFDLEdBQUcsRUFBRSxDQUFDLE1BQU0sQ0FBQztnQkFDckIsT0FBTyxDQUFDLENBQUMsSUFBSSxDQUFDLENBQUMsRUFBRSxDQUFDLFNBQVMsQ0FBQyxDQUFDO1lBQ2pDLENBQUMsQ0FBQyxDQUFDO1FBQ1AsQ0FBQztRQUVTLGNBQWMsQ0FBQyxNQUFrQjtZQUN2QyxJQUFJLE1BQU0sQ0FBQyxHQUFHLEtBQUssU0FBUyxFQUFFO2dCQUMxQixJQUFJLENBQUMsaUJBQWlCLENBQUMsTUFBTSxDQUFDLEdBQUcsQ0FBQyxDQUFDO2FBQ3RDO2lCQUFNLElBQUksTUFBTSxDQUFDLEVBQUUsS0FBSyxTQUFTLEVBQUU7Z0JBQ2hDLElBQUksQ0FBQyxnQkFBZ0IsQ0FBQyxNQUFNLENBQUMsRUFBRSxDQUFDLENBQUM7YUFDcEM7aUJBQU07Z0JBQ0gsSUFBSSxDQUFDLG9CQUFvQixFQUFFLENBQUM7YUFDL0I7UUFDTCxDQUFDO1FBRVMsZ0JBQWdCLENBQUMsWUFBaUI7WUFDeEMsSUFBSSxZQUFZLElBQUksWUFBWSxDQUFDLFFBQVEsRUFBRTtnQkFDdkMsaUJBQVUsQ0FBQyxZQUFZLENBQUMsUUFBUSxDQUFDLENBQUM7Z0JBQ2xDLE9BQU8sSUFBSSxDQUFDO2FBQ2Y7UUFDTCxDQUFDO1FBRVMsaUJBQWlCLENBQUMsWUFBMkI7WUFDbkQsSUFBSSxLQUFLLENBQUMsT0FBTyxDQUFDLFlBQVksQ0FBQyxFQUFFO2dCQUM3QixNQUFNLE1BQU0sR0FBRyxZQUFZLENBQUMsR0FBRyxDQUFDLENBQUMsT0FBNkIsRUFBRSxFQUFFO29CQUM5RCxPQUFPLElBQUksc0JBQVksQ0FBQyxPQUFPLENBQUMsSUFBSSxFQUFFLE9BQU8sQ0FBQyxJQUFJLENBQUMsQ0FBQztnQkFDeEQsQ0FBQyxDQUFDLENBQUM7Z0JBQ0gsSUFBSSxDQUFDLFVBQVUsQ0FBQyxNQUFNLENBQUMsQ0FBQzthQUMzQjtpQkFBTTtnQkFDSCxJQUFJLENBQUMsb0JBQW9CLEVBQUUsQ0FBQzthQUMvQjtRQUNMLENBQUM7UUFFUyxvQkFBb0I7WUFDMUIsS0FBSyxDQUFDLGtCQUFrQixDQUFDLENBQUM7UUFDOUIsQ0FBQztRQUVTLGtCQUFrQjtZQUN4QixJQUFJLE1BQU0sR0FBRyxJQUFJLENBQUMsRUFBRSxDQUFDLElBQUksQ0FBQyxjQUFjLENBQUMsQ0FBQztZQUMxQyxJQUFJLFVBQVUsR0FBRyxNQUFNLENBQUMsT0FBTyxDQUFDLEdBQUcsR0FBRyxJQUFJLENBQUMsbUJBQW1CLENBQUMsQ0FBQztZQUNoRSxJQUFJLFVBQVUsQ0FBQyxNQUFNLEVBQUU7Z0JBQ25CLE1BQU0sR0FBRyxVQUFVLENBQUM7YUFDdkI7aUJBQU07Z0JBQ0gsVUFBVSxHQUFHLE1BQU0sQ0FBQyxPQUFPLENBQUMsR0FBRyxHQUFHLElBQUksQ0FBQyw0QkFBNEIsQ0FBQyxDQUFDO2dCQUNyRSxJQUFJLFVBQVUsQ0FBQyxNQUFNLEVBQUU7b0JBQ25CLE1BQU0sR0FBRyxVQUFVLENBQUM7aUJBQ3ZCO2FBQ0o7WUFDRCxJQUFJLENBQUMsTUFBTSxDQUFDLE1BQU0sRUFBRTtnQkFDaEIsT0FBTzthQUNWO1FBRUwsQ0FBQzs7SUE1VEwsb0JBNlRDO0lBNVRHOzs7O2VBQXdELFNBQVM7T0FBQzs7Ozs7O0lDN0V0RSxNQUFhLElBQUssU0FBUSxlQUFNO1FBQ3JCLGtCQUFrQjtZQUNyQixJQUFJLENBQUMsVUFBVSxFQUFFLENBQUMsSUFBSSxDQUFDLFNBQVMsRUFBRSxJQUFJLENBQUMsQ0FBQyxPQUFPLENBQUMsUUFBUSxDQUFDLENBQUM7UUFDOUQsQ0FBQztRQUVNLG9CQUFvQjtZQUN2QixJQUFJLENBQUMsVUFBVSxFQUFFLENBQUMsSUFBSSxDQUFDLFNBQVMsRUFBRSxLQUFLLENBQUMsQ0FBQyxPQUFPLENBQUMsUUFBUSxDQUFDLENBQUM7UUFDL0QsQ0FBQztRQUVNLGlCQUFpQjtZQUNwQixPQUFPLElBQUksQ0FBQyxVQUFVLENBQUMsVUFBVSxDQUFDLENBQUM7UUFDdkMsQ0FBQztRQUVNLFVBQVUsQ0FBQyxRQUFpQjtZQUMvQixPQUFPLElBQUksQ0FBQyxFQUFFLENBQUMsSUFBSSxDQUFDLFlBQVksR0FBRyxDQUFDLFFBQVEsSUFBSSxFQUFFLENBQUMsQ0FBQyxDQUFDO1FBQ3pELENBQUM7UUFFTSxzQkFBc0I7WUFDekIsTUFBTSxhQUFhLEdBQUcsSUFBSSxDQUFDLGFBQWEsRUFBRSxDQUFDO1lBQzNDLElBQUksQ0FBQyxhQUFhLENBQUMsTUFBTSxFQUFFO2dCQUN2QixNQUFNLElBQUksS0FBSyxDQUFDLHNCQUFzQixDQUFDLENBQUM7YUFDM0M7WUFDRCxPQUFPLGFBQWEsQ0FBQyxNQUFNLENBQUMsaUJBQWlCLENBQUMsQ0FBQyxNQUFNLEtBQUssQ0FBQyxDQUFDO1FBQ2hFLENBQUM7UUFTTSxhQUFhO1lBQ2hCLE9BQU8sSUFBSSxDQUFDLEVBQUUsQ0FBQyxJQUFJLENBQUMsbUJBQW1CLENBQUMsQ0FBQztRQUM3QyxDQUFDO1FBRVMsSUFBSTtZQUNWLEtBQUssQ0FBQyxJQUFJLEVBQUUsQ0FBQztZQUNiLElBQUksQ0FBQyxjQUFjLEVBQUUsQ0FBQztZQUN0QixJQUFJLENBQUMsaUJBQWlCLEVBQUUsQ0FBQztRQUM3QixDQUFDO1FBRVMsWUFBWTtZQUNsQixLQUFLLENBQUMsWUFBWSxFQUFFLENBQUM7UUFNekIsQ0FBQztRQUVTLGNBQWM7WUFDcEIsS0FBSyxDQUFDLGNBQWMsRUFBRSxDQUFDO1FBRTNCLENBQUM7UUFFUyxjQUFjO1lBQ3BCLE1BQU0saUJBQWlCLEdBQUcsSUFBSSxDQUFDLGlCQUFpQixFQUFFLENBQUM7WUFDbkQsTUFBTSxVQUFVLEdBQUcsSUFBSSxDQUFDLFVBQVUsRUFBRSxDQUFDO1lBQ3JDLElBQUksaUJBQWlCLENBQUMsRUFBRSxDQUFDLFVBQVUsQ0FBQyxJQUFJLENBQUMsVUFBVSxDQUFDLE1BQU0sSUFBSSxDQUFDLFVBQVUsQ0FBQyxHQUFHLENBQUMsaUJBQWlCLENBQUMsQ0FBQyxHQUFHLENBQUMsVUFBVSxDQUFDLENBQUMsTUFBTSxDQUFDLEVBQUU7Z0JBQ3RILElBQUksQ0FBQyxrQkFBa0IsRUFBRSxDQUFDO2FBQzdCO1FBQ0wsQ0FBQztRQUVTLGlCQUFpQjtRQU8zQixDQUFDO1FBRVMsaUJBQWlCO1lBQ3ZCLE9BQU8sSUFBSSxDQUFDLEVBQUUsQ0FBQyxJQUFJLENBQUMsZ0JBQWdCLENBQUMsQ0FBQztRQUMxQyxDQUFDO0tBQ0o7SUE1RUQsb0JBNEVDOzs7Ozs7SUN2RkQsU0FBZ0IsRUFBRSxDQUFDLE9BQWU7UUFFOUIsT0FBTyxPQUFPLENBQUM7SUFDbkIsQ0FBQztJQUhELGdCQUdDOzs7Ozs7SUNIRCxDQUFDLEdBQUcsRUFBRTtRQUNGLElBQUksTUFBTSxHQUFXLENBQUMsQ0FBQztRQUN2QixDQUFDLENBQUMsRUFBRSxDQUFDLElBQUksR0FBRyxVQUF3QixFQUFpQztZQUNqRSxJQUFJLFFBQVEsR0FBVyxNQUFNLENBQUMsTUFBTSxFQUFFLENBQUMsR0FBRyxZQUFZLENBQUM7WUFDdkQsT0FBTyxJQUFJLENBQUMsR0FBRyxDQUFDLEdBQUcsR0FBRyxRQUFRLENBQUM7aUJBQzFCLFFBQVEsQ0FBQyxRQUFRLENBQUM7aUJBQ2xCLElBQUksQ0FBQyxFQUFFLENBQUMsQ0FBQztRQUNsQixDQUFDLENBQUM7SUFDTixDQUFDLENBQUMsRUFBRSxDQUFDO0lBRUwsQ0FBQyxDQUFDLGVBQWUsR0FBRyxVQUFVLEtBQVcsRUFBRSxHQUFHLElBQVc7UUFDckQsT0FBTyxDQUFDLENBQUMsUUFBUSxFQUFFLENBQUMsT0FBTyxDQUFDLEtBQUssRUFBRSxHQUFHLElBQUksQ0FBQyxDQUFDLE9BQU8sRUFBRSxDQUFDO0lBQzFELENBQUMsQ0FBQztJQUVGLENBQUMsQ0FBQyxlQUFlLEdBQUcsVUFBVSxLQUFXLEVBQUUsR0FBRyxJQUFXO1FBQ3JELE9BQU8sQ0FBQyxDQUFDLFFBQVEsRUFBRSxDQUFDLE1BQU0sQ0FBQyxLQUFLLEVBQUUsR0FBRyxJQUFJLENBQUMsQ0FBQyxPQUFPLEVBQUUsQ0FBQztJQUN6RCxDQUFDLENBQUM7SUFPVyxRQUFBLE9BQU8sR0FBRyxJQUFJLENBQUM7SUFHNUIsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxNQUFNLENBQUM7UUFDUixNQUFNLEVBQUUsQ0FBQztZQUNMLElBQUksSUFBSSxHQUFHLENBQUMsQ0FBQztZQUNiLE9BQU87Z0JBQ0gsT0FBTyxJQUFJLENBQUMsSUFBSSxDQUFDO29CQUNiLElBQUksQ0FBQyxJQUFJLENBQUMsRUFBRSxFQUFFO3dCQUNWLElBQUksQ0FBQyxFQUFFLEdBQUcsUUFBUSxHQUFHLENBQUUsRUFBRSxJQUFJLENBQUUsQ0FBQztxQkFDbkM7Z0JBQ0wsQ0FBQyxDQUFDLENBQUM7WUFDUCxDQUFDLENBQUM7UUFDTixDQUFDLENBQUMsRUFBRTtRQUVKLFlBQVksRUFBRTtZQUNWLE9BQU8sSUFBSSxDQUFDLElBQUksQ0FBQztnQkFDYixJQUFJLGFBQWEsQ0FBQyxJQUFJLENBQUMsSUFBSSxDQUFDLEVBQUUsQ0FBQyxFQUFFO29CQUM3QixDQUFDLENBQUMsSUFBSSxDQUFDLENBQUMsVUFBVSxDQUFDLElBQUksQ0FBQyxDQUFDO2lCQUM1QjtZQUNMLENBQUMsQ0FBQyxDQUFDO1FBQ1AsQ0FBQztLQUNKLENBQUMsQ0FBQzs7Ozs7O0lDNUJILFNBQWdCLElBQUksQ0FBQyxDQUFvQixFQUFFLE9BQTZCO0lBVXhFLENBQUM7SUFWRCxvQkFVQyJ9