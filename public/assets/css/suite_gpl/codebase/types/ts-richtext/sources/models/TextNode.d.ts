import { IStyle } from "../types";
export declare enum RemoveTextResult {
    textIsEmpty = 0,
    complete = 1,
    nullTextLength = 2,
    forceRemovePrevious = 3,
    forceRemoveNext = 4
}
export declare class TextNode {
    style: IStyle;
    text: string;
    constructor(text: string, style?: IStyle);
    isEmpty(): boolean;
    insert(data: string, offset: number): void;
    remove(offset: number, prev: boolean): RemoveTextResult;
}
