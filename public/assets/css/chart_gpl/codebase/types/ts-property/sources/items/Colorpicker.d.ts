import { IEventSystem } from "../../../ts-common/events";
import { IItemConfig, IPropertyConfig, PropertyEvents } from "../types";
import { BaseItem } from "./BaseItem";
export declare class ColorPicker extends BaseItem {
    private _colorPicker;
    private _popup;
    constructor(itemConfig: IItemConfig, propertyConfig: IPropertyConfig, ev: IEventSystem<PropertyEvents>);
    toVDOM(): any;
    private _showPopup;
}
