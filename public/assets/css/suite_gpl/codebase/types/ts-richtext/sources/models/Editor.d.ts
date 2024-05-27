import { Manager } from "../Manager";
import { IRichTextEventSystem } from "../types";
import { SelectionHelper } from "./../SelectionHelper";
import { Block } from "./Block";
import { TextNode } from "./TextNode";
interface IEditorConfig {
    events: IRichTextEventSystem;
    uid: string;
    getRootView: () => any;
    repaint: () => void;
}
export declare class Editor {
    selection: SelectionHelper;
    manager: Manager;
    blocks: Block[];
    private _events;
    private _getRootView;
    private _repaint;
    private _tempNode;
    constructor({ events, uid, getRootView, repaint }: IEditorConfig);
    clear(): void;
    getState(): string;
    setState(state: string): void;
    parse(value: any, mode?: "html" | "markdown" | "inner"): boolean;
    serialize(mode?: "html" | "markdown" | "text"): string;
    getTextElement: (blockIndex: any, textIndex: any) => any;
    removeBlock(index: number): void;
    insertBlock(): void;
    eachSelectedNode(fn: (textNode: TextNode, block?: Block) => any): void;
    eachSelectedBlock(fn: (block: Block, index?: number) => any): void;
    copy(): false | {
        content: any[];
        raw: string;
    };
    cut(): false | {
        content: any[];
        raw: string;
    };
    paste(value: any, mode?: "text" | "inner" | "html"): boolean;
    add(data: any): boolean;
    remove(prev: boolean): boolean;
    update({ modifier, modifierValue }: {
        modifier: any;
        modifierValue: any;
    }): boolean;
    clearStyles(): boolean;
    balance(): void;
    getLinkInfo(): {
        text: string;
        link: any;
    };
    updateLinkText(text: string): void;
    private _splitRange;
    private _splitCarret;
    private _removeRange;
    private _removeSymbol;
    private _eachLink;
    private _createTempTextNode;
    private _createNodeFromTemp;
}
export {};
