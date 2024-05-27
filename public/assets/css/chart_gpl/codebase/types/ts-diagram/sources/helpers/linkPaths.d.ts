import { IItemConfig, ILinkConfig } from "../types";
interface IPoint {
    x: number;
    y: number;
}
export declare function nearestLinkPath(link: ILinkConfig, from: IItemConfig, to: IItemConfig, config: any): IPoint[];
export declare function directLinkPath(link: ILinkConfig, from: IItemConfig, to: IItemConfig, config: any): void;
export {};
