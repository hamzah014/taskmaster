import { IEventSystem } from "../../../ts-common/events";
import { IItemConfig, IPropertyConfig, PropertyEvents } from "../types";
import { BaseItem } from "./BaseItem";
export declare class Combo extends BaseItem {
    private _combo;
    constructor(itemConfig: IItemConfig, propertyConfig: IPropertyConfig, ev: IEventSystem<PropertyEvents>);
    getValue(): string;
    toVDOM(): any;
}
