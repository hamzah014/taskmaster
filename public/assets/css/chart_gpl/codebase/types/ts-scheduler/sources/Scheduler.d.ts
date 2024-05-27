import { IEventSystem } from "../../ts-common/events";
import { DataEvents } from "../../ts-data";
import { View } from "../../ts-common/view";
export interface ISchedulerConfig {
    mode: string;
}
export declare class Scheduler extends View {
    version: string;
    config: ISchedulerConfig;
    events: IEventSystem<DataEvents>;
    private _layout;
    private _modes;
    private _center;
    constructor(container: string | HTMLElement, config: ISchedulerConfig);
    paint(): void;
    setMode(name: string): void;
    private _initUI;
}
