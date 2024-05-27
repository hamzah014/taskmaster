import { IParsedPivotConfig } from "../../sources/types";
export declare const dataSample: {
    name: string;
    continent: string;
    form: string;
    oil: number;
}[];
export declare const undefinedDataSample: {
    name: string;
    continent: string;
    form: string;
    oil: number;
}[];
export declare const columns: {
    name: string;
    $keyArr: string[];
}[][];
export declare const baseConfig: {
    data: {
        name: string;
        continent: string;
        form: string;
        oil: number;
    }[];
    fields: {
        rows: {
            id: string;
            label: string;
        }[];
        columns: {
            id: string;
            label: string;
        }[];
        values: {
            id: string;
            method: string;
            label: string;
        }[];
    };
    fieldList: any[];
};
export declare const sortConfig: IParsedPivotConfig;
export declare const headers: {
    header: ({
        text: string;
        colspan: number;
    } | {
        text: string;
        colspan?: undefined;
    })[];
    width: number;
    id: number;
}[];
export declare const dataAfterGroup: string[][];
