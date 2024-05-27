import { IBoxSize, ICoords, ILinkConfig } from "../../types";
import { BaseShape } from "../Base";
export declare class Line extends BaseShape {
    config: ILinkConfig;
    constructor(config: ILinkConfig);
    isConnector(): boolean;
    getMetaInfo(): {
        id: string;
        type: string;
        label: string;
        connector: boolean;
    }[];
    setDefaults(config: ILinkConfig): ILinkConfig;
    toSVG(): string;
    getBox(): IBoxSize;
    protected _getType(): string;
    protected _getPoints(): string;
    protected _getStringPoints(): string;
    protected _getArrowLine(): any[];
    protected _arrow(from: ICoords, to: ICoords): string;
}
