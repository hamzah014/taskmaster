export interface ISession {
    get(name: string): any;
    set(name: string, data: any): void;
}
export interface IEventConfig {
    bind?: any;
    tag?: any;
}
export interface IEventSystem {
    on(name: string, handler: ICallable, config?: IEventConfig): any;
    detach(config: IEventConfig): any;
    fire(name: string, data: any): any;
}
export interface IServiceConfig {
    tag?: any;
}
export interface ICell {
    attach(content: any): any;
}
export interface IDHXView {
    on(obj: any, name: string, handler: ICallable): void;
    getService(name: string): any;
    setService(name: string, obj: any, config: IServiceConfig): void;
    render(cell: ICell): any;
    destructor(): void;
}
export interface IDHXApp extends IDHXView {
    session: ISession;
    config: any;
    events: IEventSystem;
}
export type ICallable = (...args: any[]) => any;
export type hash = {
    [key: string]: any;
};
export type IDHXViewFactory = new (app: IDHXApp) => IDHXView;
