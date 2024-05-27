import { Colorpicker } from "../../../ts-colorpicker";
import { Toolbar } from "../../../ts-toolbar";
import { Editor } from "../models/Editor";
import { IRichText } from "../types";
export declare class RichTextToolbarHelper {
    toolbar: Toolbar;
    colorpicker: Colorpicker;
    private _statsPopup;
    private _colorpickerPopup;
    private _colorpickerTarget;
    private _parent;
    private _editor;
    private _defaultToolbarState;
    constructor(parent: IRichText, editor: Editor);
    private _initEvents;
    private _toolbarAction;
    private _updateState;
    private _updateUndoRedoState;
    private _getCommonModifiers;
    private _getColorState;
}
