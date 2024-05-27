import { IEventSystem } from "../../../ts-common/events";
import { IItem, IItemConfig, IPropertyConfig, PropertyEvents } from "../types";
import { Checkbox } from "./Checkbox";
import { ColorPicker } from "./Colorpicker";
import { Combo } from "./Combo";
import { Label } from "./Label";
import { Select } from "./Select";
import { Text } from "./Text";
import { Textarea } from "./Textarea";
export declare const items: {
    text: typeof Text;
    colorpicker: typeof ColorPicker;
    textarea: typeof Textarea;
    label: typeof Label;
    select: typeof Select;
    checkbox: typeof Checkbox;
    combo: typeof Combo;
};
export declare function itemsFactory(itemConfig: IItemConfig, propertyConfig: IPropertyConfig, events: IEventSystem<PropertyEvents>): IItem;
