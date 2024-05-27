import { View } from "../../ts-common/view";
import { Toolbar } from "../../ts-toolbar";
import { IEditorAPI, IRichText, IRichTextConfig, IRichTextEventSystem } from "./types";
export declare class Richtext extends View implements IRichText {
    events: IRichTextEventSystem;
    config: IRichTextConfig;
    toolbar: Toolbar;
    private _editor;
    private _layout;
    private _handlers;
    private _initContainer;
    private _toolbarHelper;
    private _scrollHelper;
    private _linkEditor;
    private _restoreFocusTimeout;
    private _compositionEventsDestructor;
    private _editorView;
    private _composition;
    constructor(container: HTMLElement | string, config: any);
    getRootView(): any;
    setValue(value: string, mode?: "html" | "markdown"): void;
    getValue(mode?: "html" | "text" | "markdown"): string;
    getStats(selection?: boolean): {
        [key: string]: number;
    };
    destructor(): void;
    fullScreen(): void;
    exitFullScreen(): void;
    getEditorAPI(): IEditorAPI;
    private _initUI;
    private _initHandlers;
    private _initEvents;
    private _restoreFocus;
    private _draw;
    private _renderTextNode;
}
