import { IEventSystem } from "../../ts-common/events";
import { Toolbar } from "../../ts-toolbar";
export interface INodePosition {
    offset: number;
    textIndex: number;
    blockIndex: number;
}
export interface ISelectionState {
    range?: boolean;
    left?: INodePosition;
    right?: INodePosition;
}
export interface IRichText {
    toolbar: Toolbar;
    events: IEventSystem<RichTextEvents>;
    config: IRichTextConfig;
    getStats(selection?: boolean): {
        [key: string]: number;
    };
    getValue(mode?: "html" | "markdown" | "text"): string;
    setValue(value: string, mode?: "html" | "markdown"): void;
    fullScreen(): void;
    exitFullScreen(): void;
    getEditorAPI(): IEditorAPI;
    destructor(): void;
}
export interface IStat {
    name: string;
    cb?: ((text: string) => number) | string;
}
export interface IRichTextConfig {
    css?: string;
    mode?: "document" | "classic";
    toolbarBlocks?: RTEToolbarBlock[];
    customStats: IStat[];
}
export declare enum Modifier {
    none = 0,
    style = "style",
    align = "align",
    blockquote = "blockquote",
    bold = "bold",
    italic = "italic",
    strike = "strike",
    fontSize = "font-size",
    fontFamily = "font-family",
    underline = "underline",
    color = "color",
    background = "background",
    link = "link"
}
export declare enum Action {
    add = "add",
    innerAdd = "innerAdd",
    remove = "remove",
    update = "update",
    undo = "undo",
    paste = "paste",
    copy = "copy",
    cut = "cut",
    redo = "redo",
    clear = "clear",
    parse = "parse",
    selectAll = "selectAll"
}
export declare enum RTEToolbarBlock {
    default = "default",
    undo = "undo",
    style = "style",
    decoration = "decoration",
    color = "colors",
    align = "align",
    link = "link",
    clear = "clear",
    fullscreen = "fullscreen",
    stats = "stats"
}
export interface IOperation {
    action: Action;
    data?: any;
}
export declare enum RichTextEvents {
    change = "change",
    action = "action",
    selectionChange = "selectionchange",
    selectionRefresh = "selectionrefresh",
    showLinkEditor = "showlinkeditor",
    compositionStart = "compositionstart",
    compositionUpdate = "compositionupdate",
    compositionEnd = "compositionend"
}
export interface IText {
    id: string;
    text: string;
    styles: IStyle;
}
export interface IStyle {
    [key: string]: any;
}
interface IEventHandlersMap {
    [key: string]: (...args: any[]) => any;
    [RichTextEvents.action]: (action: Action, data?: any, restoreFocus?: boolean) => any;
    [RichTextEvents.showLinkEditor]: () => void;
    [RichTextEvents.selectionChange]: (selection: ISelectionState) => any;
    [RichTextEvents.selectionRefresh]: () => void;
    [RichTextEvents.change]: (action: Action, canUndo: boolean, canRedo: boolean) => any;
}
export interface IRichTextEventSystem extends IEventSystem<RichTextEvents> {
    on<K extends keyof IEventHandlersMap>(event: K, callback: IEventHandlersMap[K], context?: any): boolean | void;
}
export interface IAddConfig {
    type: "full" | "textnode" | "text";
    data: IBlock[] | string | ITextNode;
}
export interface ISelectionPosition {
    xStart?: number;
    yStart?: number;
    xEnd?: number;
    yEnd?: number;
}
interface IUpdateConfig {
    modifier: Modifier;
    value: any;
}
export interface ITextNode {
    text: string;
    style: IStyle;
}
export interface IBlock {
    textNodes: ITextNode[];
    style: IStyle;
}
export interface IEditorAPI {
    getPosition(selection: ISelectionState): Promise<ISelectionPosition>;
    getSelection(): ISelectionState;
    setSelection(selection: ISelectionState): void;
    add(cfg: IAddConfig | string, selection?: ISelectionState): any;
    update(cfg: IUpdateConfig, selection?: ISelectionState): any;
    remove(selection?: ISelectionState): any;
    getModel(): IBlock[];
    setModel(blocks: IBlock[], selection?: ISelectionState): any;
}
export {};
