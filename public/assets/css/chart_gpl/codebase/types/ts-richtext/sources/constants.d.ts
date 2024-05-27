import { IStyle, RTEToolbarBlock } from "./types";
export declare const fontSizes: {
    "12px": boolean;
    "14px": boolean;
    "16px": boolean;
    "18px": boolean;
    "20px": boolean;
    "24px": boolean;
    "28px": boolean;
    "32px": boolean;
    "36px": boolean;
};
export declare const fonts: {
    Roboto: boolean;
    Arial: boolean;
    Georgia: boolean;
    Tahoma: boolean;
    "Times New Roman": boolean;
    Verdana: boolean;
};
export declare function getToolbarData(toolbarBlocks: RTEToolbarBlock[], defaultStyle: IStyle): any[];
export declare const emptyStyle: {};
export declare const defaultBlocks: RTEToolbarBlock[];
export declare const defaultFontSizeByBlock: {
    p: string;
    h1: string;
    h2: string;
    h3: string;
    h4: string;
    h5: string;
    h6: string;
};
export declare const defaultDiffrentStyle: {
    "font-size": string;
    "font-family": string;
    style: string;
    bold: boolean;
    underline: boolean;
    italic: boolean;
    strike: boolean;
    color: string;
    background: string;
    align: boolean;
};
