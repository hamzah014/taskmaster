export type Template = (text: string, field: IField) => string;
export type CellTemplate = (text: string, row: any, col: any) => string;
export interface IField {
    id: string;
    label?: string;
    type?: string;
    group?: string;
    method?: string;
    format?: string;
    inputFormat?: string;
    sortDir?: string;
    template?: Template;
    cellTemplate?: CellTemplate;
    aliases?: {
        [key: string]: {
            [key: string]: string | number;
        };
    };
    $filter?: boolean;
    $uid?: string;
    $field?: string;
}
export interface IValue extends IField {
    id: string;
    method: string;
    label?: string;
}
export interface ITypes {
    operations?: Array<{
        id: string;
        label: string;
    }>;
    dates?: Array<{
        id: string;
        label: string;
    }>;
}
export interface IRawPivotFields {
    columns: Array<IField | string>;
    values: IValue[];
    rows: Array<IField | string>;
}
export interface IPivotFields extends IRawPivotFields {
    columns: IField[];
    rows: IField[];
    values: IValue[];
    free?: IField[];
}
export interface IPivotConfig {
    data: any[];
    fields: IRawPivotFields;
    fieldList: IField[];
    types?: ITypes;
    layout?: ILayout;
    mark?: IMark | MarkFunction;
    dataFormat?: any;
    customFormat?: any;
}
type MarkFunction = (cell: any, columnCells: any[], row: any, column: any) => string;
export interface IMark {
    min?: string;
    max?: string;
}
export interface IParsedPivotConfig extends IPivotConfig {
    fields: IPivotFields;
}
export interface ILayout {
    leftMargin?: number;
    gridOnly?: boolean;
    readonly?: boolean;
    liveReload?: boolean;
    rowsHeadersWidth?: number | "auto";
    rowsWidth?: number;
    columnsWidth?: number | "auto";
    fieldsCloseBtn?: boolean;
    showFilters?: boolean;
    footer?: boolean;
}
export type mathMethod = (cellData: number[]) => number | null;
export interface IPivotBase {
    getFields(): IPivotFields;
    setData(data: any[]): void;
    paint(): void;
    addMathMethod(id: string, label: string, func: mathMethod): void;
    setFilterValue(fieldId: string, meta: IFilterMeta): any;
    getFilterValue(fieldId: string): IFilterMeta;
    setFiltersValues(filters: {
        [key: string]: IFilterMeta;
    }): any;
    getFiltersValues(): any;
    getConfig(): IPivotConfig;
}
interface IPivotLocalization {
    availableFields: string;
    data: string;
    columns: string;
    rows: string;
    moveFieldsHere: string;
    hideSettings: string;
    showSettings: string;
    apply: string;
    day: string;
    month: string;
    quarter: string;
    year: string;
    min: string;
    max: string;
    sum: string;
    count: string;
    date: any;
}
export interface IDHXGlobal {
    i18n: {
        pivot: IPivotLocalization;
    };
}
export interface IDataPreprocessor {
    getData(data: any[], fields: IPivotFields): any[];
}
export interface IExportConfig {
    name?: string;
    url?: string;
}
export interface IPivotRichData {
    aliases?: {
        [key: string]: {
            [key: string]: string | number;
        };
    };
    data?: any[];
}
export type IPivotData = IPivotRichData | string[] | any;
export interface IPopup {
    attachEvent(name: string, callback: any): any;
    detachEvent(name: string): any;
    callEvent(name: string, args: any[]): any;
    addDomEvent(name: string): any;
    removeDomEvent(name: string): any;
    show(node: HTMLElement): any;
    hide(): any;
}
export type IFilterType = "string" | "number" | "date";
export interface IFilterMeta {
    operation?: string;
    filter?: string | {
        from: string;
        to: string;
    };
    values?: string[];
}
export interface IDateFilterMeta extends IFilterMeta {
    filter?: {
        from: string;
        to: string;
    };
    type: string;
    inputFormat?: string;
}
export interface IFilterPopup extends IPopup {
    getFilter(): IFilterMeta;
    setFilter(meta: IFilterMeta): any;
    setOptions(data: any[]): any;
    isActive(): boolean;
}
export interface IFilterInfo {
    handler: (value: any) => boolean;
    meta?: IFilterMeta;
}
export interface IPos {
    top: number;
    left: number;
}
export declare enum PivotEvents {
    itemClick = "itemClick",
    apply = "apply",
    change = "change",
    update = "update",
    filterApply = "filterApply"
}
export {};
