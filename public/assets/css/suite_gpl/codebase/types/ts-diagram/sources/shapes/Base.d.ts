import { Id } from "../../../ts-common/types";
import { ICoords, IItemConfig, IShape } from "../types";
export declare class BaseShape implements IShape {
    config: IItemConfig;
    id: Id;
    constructor(config: IItemConfig);
    isConnector(): boolean;
    canResize(): boolean;
    getCenter(): ICoords;
    getBox(): {
        left: number;
        right: number;
        top: number;
        bottom: number;
    };
    getMetaInfo(): {
        id: string;
        label: string;
        type: string;
    }[];
    move(x: number, y: number): void;
    resize(width: number, height: number): void;
    rotate(angle: number): void;
    update(conf: IItemConfig): void;
    toSVG(): string;
    getPoint(x: number, y: number): ICoords;
    setCss(value: string): void;
    protected getCss(): string;
    protected setDefaults(config: IItemConfig): IItemConfig;
    protected getCoords(conf: any): ICoords;
}
