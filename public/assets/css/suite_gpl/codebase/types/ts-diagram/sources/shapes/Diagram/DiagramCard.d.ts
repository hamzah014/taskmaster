import { IItemConfig } from "../../types";
import { BaseShape } from "../Base";
export declare class DiagramCard extends BaseShape {
    toSVG(): any;
    getMetaInfo(): {
        id: string;
        label: string;
        type: string;
    }[];
    protected getCss(): string;
    protected setDefaults(obj: IItemConfig): IItemConfig;
    protected text(): any;
}
