export declare const i18n: any;
import "../../styles/pivot.scss";
export { Pivot } from "./Pivot";
import { CsvDriver, DataProxy, JsonDriver } from "../../ts-data";
export declare const data: {
    CsvDriver: typeof CsvDriver;
    JsonDriver: typeof JsonDriver;
    Proxy: typeof DataProxy;
};
