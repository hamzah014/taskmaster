import { ICallable, ICell, IDHXApp, IServiceConfig } from "./types";
export declare class DHXView {
    app: IDHXApp;
    private _events;
    constructor(cell: ICell, app: IDHXApp);
    destructor(): void;
    setService(name: string, obj: any, config?: IServiceConfig): void;
    getService(name: string): any;
    on(obj: any, name: string, handler: ICallable): void;
    render(_cell: any): void;
}
