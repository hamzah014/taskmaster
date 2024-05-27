import { ISession } from "./types";
export declare class LocalSession implements ISession {
    get(key: string, def?: any): any;
    set(key: string, value: any): void;
}
