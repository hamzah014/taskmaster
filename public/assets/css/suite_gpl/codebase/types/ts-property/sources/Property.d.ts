import { IItemConfig, IProperty, IPropertyConfig, IValues, PropertyEvents } from "./types";
import { IEventSystem } from "../../ts-common/events";
import { View } from "../../ts-common/view";
import { IDataCollection } from "../../ts-data";
export declare class Property extends View implements IProperty {
    events: IEventSystem<PropertyEvents>;
    data: IDataCollection<IItemConfig>;
    constructor(cont: string | HTMLElement, config?: IPropertyConfig);
    destructor(): void;
    clear(): void;
    getValues(): {};
    setValues(data: IValues): void;
    setFocus(id: string): void;
    private _parseStruct;
    private _toVDOM;
}
