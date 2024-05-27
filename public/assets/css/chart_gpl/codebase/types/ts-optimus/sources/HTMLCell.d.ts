import { ICell } from "./types";
export declare class HTMLCell implements ICell {
    config: any;
    private root;
    constructor(root: HTMLElement | string);
    isVisible(): boolean;
    attach(cell: any): void;
}
