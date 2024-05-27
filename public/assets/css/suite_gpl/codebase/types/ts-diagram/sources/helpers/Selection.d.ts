import { IEventSystem } from "../../../ts-common/events";
import { Id } from "../../../ts-common/types";
import { DataCollection } from "../../../ts-data";
import { SelectionEvents } from "../types";
export declare class Selection {
    events: IEventSystem<SelectionEvents>;
    private _selected;
    private _data;
    constructor(_config: any, data?: DataCollection, events?: IEventSystem<any>);
    getId(): Id;
    getItem(): any;
    remove(id?: Id): boolean;
    add(id: Id): void;
}
