import { ICallable } from "./types";
export declare class EventSystem {
    private evs;
    private master;
    constructor(master: any);
    detach(config: any): void;
    on(name: string, code: ICallable, config: any): void;
    fire(name: string, event: any): boolean;
}
