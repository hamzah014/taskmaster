import { DiagramCard } from "./DiagramCard";
export declare class DiagramSvgCard extends DiagramCard {
    toSVG(): any;
    getMetaInfo(): {
        id: string;
        label: string;
        type: string;
    }[];
    protected text(): string;
    protected title(): string;
    protected getCss(): string;
}
