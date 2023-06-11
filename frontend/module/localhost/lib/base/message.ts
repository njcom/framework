/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
///<amd-module name="localhost/lib/base/message" />
///<reference path="./index.d.ts" />

import {Widget} from "./widget";

export enum MessageType {
    Error = 1,
    Warning = 2,
    Info = 4,
    Debug = 8,
    All = Error | Warning | Info | Debug
}

interface ExtendedWindow extends Window {
    pageMessenger: PageMessenger;
}
declare const window: ExtendedWindow;

/*interface MessageRenderer {
    (message: Message): string;
}*/
export class PageMessenger extends Widget {
    protected numberOfMessages(): number {
        return this.messageEls().length;
    }

    protected messageEls(): JQuery {
        return this.el.find('.alert');
    }

    protected bindHandlers(): void {
        super.bindHandlers();
        this.registerCloseMessageHandler();
    }

    protected registerCloseMessageHandler(): void {
        const self = this;

        function hideElWithAnim($el: JQuery, complete: (this: HTMLElement) => void) {
            $el.fadeOut(complete);
        }

        function hideMainContainerWithAnim() {
            hideElWithAnim(self.el, function () {
                self.el.find('.messages').remove();
                self.el.hide();
            });
        }

        function closeMessageWithAnim($message: JQuery): any {
            if (self.numberOfMessages() === 1) {
                hideMainContainerWithAnim();
            } else {
                const $messageContainer = $message.closest('.messages');
                if ($messageContainer.find('.alert').length === 1) {
                    hideElWithAnim($messageContainer, function () {
                        $messageContainer.remove();
                    });
                } else {
                    hideElWithAnim($message, function () {
                        $message.remove();
                    });
                }
            }
        }

        this.el.on('click', 'button.close', function (this: JQuery) {
            closeMessageWithAnim($(this).closest('.alert'));
        });
        setTimeout(function () {
            hideMainContainerWithAnim();
        }, 5000);
    }
}

export function renderMessage(message: Message): string {
    let text = message.text.encodeHtml();
    text = text.format(message.args);
    return wrapMessage(text, messageTypeToStr(message.type));
}

function wrapMessage(text: string, type: string): string {
    return '<div class="' + type.toLowerCase().encodeHtml() + '">' + text + '</div>';
}

export function messageTypeToStr(type: MessageType): string {
/*
    switch (messageType) {
        case MessageType.Debug:
            return 'debug';
        case MessageType.Info:
            return 'info';
        case MessageType.Warning:
            return 'warning';
        case MessageType.Error:
            return 'error';
        default:
            throw new Error("Invalid message type");
    }
*/
    return MessageType[type];
}

export class Message {
    constructor(public type: MessageType, public text: string, public args: string[] = []) {
    }

    public hasType(type: MessageType): boolean {
        return this.type === type;
    }
}

export class ErrorMessage extends Message {
    constructor(text: string, args: string[] = []) {
        super(MessageType.Error, text, args);
    }
}

export class WarningMessage extends Message {
    constructor(text: string, args: string[] = []) {
        super(MessageType.Warning, text, args);
    }
}

export class InfoMessage extends Message {
    constructor(text: string, args: string[] = []) {
        super(MessageType.Warning, text, args);
    }
}

export class DebugMessage extends Message {
    constructor(text: string, args: string[] = []) {
        super(MessageType.Debug, text, args);
    }
}
