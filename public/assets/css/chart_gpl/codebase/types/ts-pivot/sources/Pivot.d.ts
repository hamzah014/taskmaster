import { IEventSystem } from "../../ts-common/events";
import { View } from "../../ts-common/view";
import { IFilterMeta, IParsedPivotConfig, IPivotBase, IPivotConfig, IPivotData, IPivotFields, mathMethod, PivotEvents } from "./types";
export declare class Pivot extends View implements IPivotBase {
    config: IParsedPivotConfig;
    events: IEventSystem<PivotEvents>;
    private _configurator;
    private _grid;
    private _dataPrep;
    private _isSettingsHidden;
    private _htmlEvents;
    constructor(container: HTMLElement | string, config: IPivotConfig);
    setFields(fields: IPivotFields): void;
    getFields(): IPivotFields;
    getConfig(): IPivotConfig;
    load(url: string, type?: string): Promise<void>;
    setData(data: IPivotData, type?: any): void;
    addMathMethod(id: string, label: string, func: mathMethod): void;
    addSubField(name: string, functor: any, label: string): void;
    setFilterValue(field: string, meta: IFilterMeta): void;
    setFiltersValues(filters: {
        [key: string]: IFilterMeta;
    }): void;
    getFilterValue(fieldId: string): IFilterMeta;
    getFiltersValues(): any;
    clearFiltersValues(): void;
    setGlobalFilter(handler?: any): void;
    addFilter(): void;
    removeFilter(): void;
    getFilter(): void;
    export(url: any): {
        name: string;
        columns: any[];
        header: any[][];
        data: string[][];
        styles: {
            cells: any[];
            css: {
                default: {
                    color: string;
                    background: string;
                    fontSize: number;
                    bold: boolean;
                };
            };
        };
    };
    private _setEventsHandlers;
    private _render;
    private _update;
    private _init;
}
