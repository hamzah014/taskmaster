import { View } from "../../../ts-common/view";
import { IEventsView } from "./Base";
export declare class Month extends View implements IEventsView {
    private _scale;
    private _layout;
    constructor(container: string | HTMLElement, config: any);
    getRootView(): any;
}
