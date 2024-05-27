import { IItemConfig } from "../types";
import { BaseShape } from "./Base";
export declare class TextShape extends BaseShape {
    toSVG(): any;
    getMetaInfo(): ({
        id: string;
        label: string;
        type: string;
        alignments?: undefined;
    } | {
        id: string;
        type: string;
        label: string;
        alignments: boolean;
    })[];
    canResize(): boolean;
    protected setDefaults(obj: IItemConfig): IItemConfig;
    private _getText;
}
