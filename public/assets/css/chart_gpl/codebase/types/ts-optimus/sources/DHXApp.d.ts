import { DHXView } from "./DHXView";
import { ICell, IEventConfig, IEventSystem, ISession } from "./types";
export declare class DHXApp extends DHXView {
    session: ISession;
    config: any;
    events: IEventSystem;
    private services;
    constructor(cell: ICell | HTMLElement | string, config?: any);
    setService(name: string, obj: any, config?: IEventConfig): void;
    getService(name: string): any;
    destructor(): void;
}
