import { IEventSystem } from "../../../ts-common/events";
import { IItem, IItemConfig, IPropertyConfig, PropertyEvents } from "../types";
export declare class BaseItem implements IItem {
    config: IItemConfig;
    propertyConfig: IPropertyConfig;
    events: IEventSystem<PropertyEvents>;
    protected _uid: string;
    protected _handlers: any;
    protected _isValid: boolean;
    protected _visibleValue: any;
    constructor(itemConfig: IItemConfig, propertyConfig: IPropertyConfig, events: IEventSystem<PropertyEvents>);
    setValue(value: any, silent?: boolean): any;
    getValue(): any;
    clear(): void;
    toVDOM(): any;
}
