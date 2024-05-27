import { Editor } from "./models/Editor";
import { IOperation } from "./types";
export declare class Manager {
    private _editor;
    private _changes;
    private _buffer;
    private _inactionTimer;
    constructor(editor: Editor);
    execute(operation: IOperation): boolean;
    canUndo(): boolean;
    canRedo(): boolean;
    undo(): boolean;
    redo(): boolean;
    clearChanges(): void;
    private _change;
}
