import { IParsedPivotConfig } from "./../types";
export declare function getValue(obj: any, key: string, fallback?: any): any;
export declare function getGridData(config: IParsedPivotConfig, data: any[]): {
    headers: {
        header: any[];
        footer: {
            text: string;
        }[];
        width: any;
        id: number;
    }[];
    groupedData: any[];
};
