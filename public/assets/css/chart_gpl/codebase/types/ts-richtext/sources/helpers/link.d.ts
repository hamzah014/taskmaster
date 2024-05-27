import { View } from "../../../ts-common/view";
import { Editor } from "../models/Editor";
import { IRichTextEventSystem } from "../types";
interface ILinkEditorConfig {
    events: IRichTextEventSystem;
    editor: Editor;
    getRootView: () => void;
}
export declare class LinkEditor extends View {
    private _events;
    private _editor;
    private _getParentView;
    private _popup;
    private _handlers;
    private _editMode;
    private _initText;
    private _link;
    private _text;
    private _currentLinkRef;
    constructor(container: any, config: ILinkEditorConfig);
    isVisible(): boolean;
    show(node: HTMLElement, mode: any): void;
    hide(): void;
    get(): {
        link: string;
        text: string;
    };
    getCurrentLinkRef(): string;
    set(link: string, text: string): void;
    focus(): void;
    private _destroyCurrentLinkRef;
    private _initEvents;
    private _draw;
    private _drawEditMode;
    private _drawViewMode;
    private _modifyLink;
    private _apply;
    private _show;
}
export {};
