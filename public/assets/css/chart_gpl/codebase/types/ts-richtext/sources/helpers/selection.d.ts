import { Editor } from "../models/Editor";
export declare function stateFromDom(editor: Editor): {
    range: boolean;
    left: any;
    right?: undefined;
} | {
    left: any;
    right: any;
    range: boolean;
};
