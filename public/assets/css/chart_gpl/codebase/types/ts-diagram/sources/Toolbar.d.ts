import { IEventSystem } from "../../ts-common/events";
import { DiagramEvents, IButtonConfig, IItemConfig, SelectionEvents } from "./types";
import { DataEvents } from "../../ts-data";
export declare class Toolbar {
    config: any;
    private events;
    private _pressCoords;
    private _handlers;
    constructor(events: IEventSystem<DataEvents | DiagramEvents | SelectionEvents>, icons: IButtonConfig[]);
    toSVG(item: any, size: any): any;
    protected _getIcons(item: IItemConfig, icons: IButtonConfig[]): any[];
    protected _getCoords(target: IItemConfig, width: number, height: number, scale: number): {
        x: number;
        y: number;
    };
}
