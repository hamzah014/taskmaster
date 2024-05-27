import { IItemConfig } from "../../types";
import { DiagramCard } from "./DiagramCard";
export declare class DiagramImgCard extends DiagramCard {
    getMetaInfo(): {
        id: string;
        label: string;
        type: string;
    }[];
    protected setDefaults(obj: IItemConfig): IItemConfig;
    protected getCss(): string;
    protected text(): any[];
}
