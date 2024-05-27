import { IEventSystem } from "../../../ts-common/events";
import { IItemConfig, IPropertyConfig, PropertyEvents } from "../types";
import { BaseItem } from "./BaseItem";
export declare class Checkbox extends BaseItem {
    constructor(itemConfig: IItemConfig, propertyConfig: IPropertyConfig, events: IEventSystem<PropertyEvents>);
    toVDOM(): any;
}
