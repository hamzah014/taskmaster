import { View } from "../../../ts-common/view";
import { IEventsView } from "./Base";
export declare class Agenda extends View implements IEventsView {
    constructor(container: string | HTMLElement, config: any);
    private _render;
}
