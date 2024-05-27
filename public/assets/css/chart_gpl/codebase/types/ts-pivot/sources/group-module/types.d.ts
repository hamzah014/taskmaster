import { ILayout, IPivotFields } from "./../types";
export { IField } from "./../types";
export interface IHeaderCell {
    id: string | number;
    width?: number;
    text?: string | null;
    colspan?: number;
    header?: any;
}
export interface IConfig {
    data: any[];
    fields: IPivotFields;
    layout?: ILayout;
    mark?: any;
}
export interface ICell {
    $keyArr: string[];
    id?: any;
}
