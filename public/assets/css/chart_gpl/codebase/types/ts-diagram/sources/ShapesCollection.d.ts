import { IEventSystem } from "../../ts-common/events";
import { Id } from "../../ts-common/types";
import { DataCollection, DataEvents } from "../../ts-data";
import { IItemConfig } from "./types";
export declare class ShapesCollection extends DataCollection {
    private _roots;
    private _orgMode;
    constructor(config: any, events: IEventSystem<DataEvents>);
    getNearId(id: Id): Id;
    mapVisible(handler: any): any[];
    getRoots(): Id[];
    protected _removeNested(obj: any): void;
    protected _eachBranch(item: IItemConfig, handler: any, stack: any[]): void;
    protected _parse_data(data: any[]): void;
    protected _mark_chains(): void;
}
