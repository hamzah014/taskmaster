import { IStyle } from "../types";
interface IMinimalNodeStyle {
    f?: number;
    s?: number;
    t?: string;
    c?: string;
    b?: string;
    a?: string;
}
interface IMinimalBlockStyle {
    a?: number;
    b?: number;
    s?: number;
}
export declare function minimizeTextNodeStyle(style: IStyle): IMinimalNodeStyle;
export declare function textNodeStyleFromMinimize(minimal: IMinimalNodeStyle): IStyle;
export declare function minimizeBlockStyle(style: IStyle): IMinimalBlockStyle;
export declare function blockStyleFromMinimize(minimal: IMinimalBlockStyle): IStyle;
export {};
