export declare class TextBuffer {
    private _editor;
    private _rawText;
    private _editorContent;
    constructor(editor: any);
    copy(event: ClipboardEvent): boolean;
    cut(event: ClipboardEvent): boolean;
    paste(event: ClipboardEvent): boolean;
    private _setBuffer;
}
