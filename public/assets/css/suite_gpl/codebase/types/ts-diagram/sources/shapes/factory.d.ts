import { IShape } from "../types";
import { BaseShape } from "./Base";
export interface IShapeMap {
    [type: string]: typeof BaseShape;
}
export declare const shapes: IShapeMap;
export declare function shapesFactory(config: any): IShape;
