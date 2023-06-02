/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
///<amd-module name="localhost/lib/base/event-manager" />

type TArgHandler = (...args: any[]) => any;

export class EventManager {
    private handlers: { [eventName: string]: TArgHandler[] } = {};

    public on(eventName: string, handler: TArgHandler): void {
        this.handlers[eventName] = this.handlers[eventName] || [];
        this.handlers[eventName].push(handler);
    }

    public trigger(eventName: string, ...args: any[]): void {
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
