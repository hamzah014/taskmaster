import { IEventSystem } from "../../ts-common/events";
import { IDataCollection } from "../../ts-data";
import { items } from "./items";
export interface IValues {
    [key: string]: any;
}
export interface IPropertyConfig {
    title?: string;
    labelsWidth?: number;
}
export interface IProperty {
    events: IEventSystem<PropertyEvents>;
    data: IDataCollection;
    destructor(): void;
    clear(): void;
    getValues(): IValues;
    setValues(data: IValues): void;
    setFocus(id: string): void;
}
export declare enum PropertyEvents {
    beforeValueSet = "beforeValueSet",
    afterValueSet = "afterValueSet",
    validationError = "validationError"
}
export interface IItem {
    config: IItemConfig;
    events: IEventSystem<PropertyEvents>;
    setValue(value: any, silent?: boolean): void;
    getValue(): any;
    clear(): void;
    toVDOM(): any;
}
export type ItemTypes = keyof typeof items;
export type DefaultValidationRules = "number" | "email" | "color" | "date";
export type ValidationFunction = (val: any) => boolean;
export interface IItemConfig {
    id: string;
    type: ItemTypes;
    validation?: DefaultValidationRules | ValidationFunction;
    icon?: any;
    label?: string;
    value?: any;
    options?: any[];
    placeholder?: string;
    readonly?: boolean;
    $item?: IItem;
}
