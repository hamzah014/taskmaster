import { IStyle } from "./../types";
import { TextNode } from "./TextNode";
export declare enum SplitResult {
    invalidNode = 0,
    offsetStart = 1,
    offsetEnd = 2,
    complete = 3
}
export declare class Block {
    style: IStyle;
    textNodes: TextNode[];
    constructor(textNodes?: TextNode[], style?: IStyle);
    clear(): void;
    isEmpty(): boolean;
    splitTextNode(index: number, offset: number): SplitResult;
    removeNodes(index: number, left?: boolean): boolean;
    offsetToNodePosition(offset: any): {
        textIndex: number;
        offset: any;
    };
    nodePositionToOffset(textIndex: any, offset: any): any;
    getBlockEnd(): {
        textIndex: number;
        offset: number;
    };
    balance(): boolean;
}
