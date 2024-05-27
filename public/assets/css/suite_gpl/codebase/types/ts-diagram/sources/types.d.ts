import { Id } from "../../ts-common/types";
type textAlign = "left" | "center" | "right";
type textVerticalAlign = "top" | "center" | "bottom";
type strokeType = "line" | "dash" | any;
type arrows = "filled" | any;
type connectType = "straight" | "curved" | "elbow";
type fontStyle = "normal" | "italic" | "oblique";
export { SelectionEvents } from "../../ts-common/types";
export interface IItemConfig {
    id?: Id;
    type?: string;
    parent?: string;
    img?: string;
    x?: number;
    y?: number;
    dx?: number;
    dy?: number;
    height?: number;
    width?: number;
    angle?: number;
    text?: string;
    title?: string;
    css?: string;
    color?: string;
    hidden?: boolean;
    from?: string;
    to?: string;
    points?: ICoords[];
    fill?: string;
    stroke?: string;
    extraLinesStroke?: string;
    strokeWidth?: number;
    strokeType?: strokeType;
    strokeDash?: string;
    fontColor?: string;
    fontStyle?: fontStyle;
    fontWeight?: string;
    fontSize?: number;
    textAlign?: textAlign;
    textVerticalAlign?: textVerticalAlign;
    lineHeight?: number;
    open?: boolean;
    dir?: string;
    $count?: number;
    $kids?: any[];
    $selected?: boolean;
    $expandColor?: string;
    $parent?: string;
    $shape?: IShape;
    $move?: boolean;
    $connectMode?: boolean;
}
export interface IButtonConfig {
    content: any;
    check?: any;
    id: Id;
    css?: any;
}
export interface IDiagramConfig {
    toolbar?: IButtonConfig[];
    type?: string;
    select?: boolean;
    defaultShapeType?: string;
    defaultLinkType?: string;
    scale?: number;
    scroll?: boolean;
    dragMode?: boolean;
    showGrid?: boolean;
    gridStep?: number;
    margin?: {
        x?: number;
        y?: number;
        itemX?: number;
        itemY?: number;
    };
    lineGap?: number;
    $svg?: any;
}
export interface IBoxSize {
    left: number;
    right: number;
    top: number;
    bottom: number;
}
export interface IShape {
    config: IItemConfig;
    id: Id;
    move(x: number, y: number): any;
    resize(width: number, height: number): any;
    rotate(angle: number): any;
    update(config: IItemConfig): any;
    getPoint(x: number, y: number): ICoords;
    toSVG(): string;
    getCenter(): ICoords;
    setCss(value: string): any;
    getMetaInfo(): any[];
    isConnector(): boolean;
    getBox(): IBoxSize;
    canResize(): boolean;
}
export interface ICoords {
    x: number;
    y: number;
    side?: string;
    $custom?: boolean;
}
export interface ILinkConfig extends IItemConfig {
    id?: string;
    from: string;
    to: string;
    type?: strokeType;
    arrow?: string;
    backArrow?: arrows;
    forwardArrow?: arrows;
    fromSide?: string;
    toSide?: string;
    cornersRadius?: number;
    points?: ICoords[];
    connectType?: connectType;
    $selected?: boolean;
    $move?: boolean;
    $selectedPoint?: string;
}
export type FilterHandler = (item: IItemConfig) => boolean;
export interface IDiagram {
    config: IDiagramConfig;
    collapseItem(id: string): any;
    expandItem(id: string): any;
    paint(): any;
    getScrollState(): ICoords;
    scrollTo(x: number, y: number): any;
    showItem(id: string): any;
}
export interface IContent {
    shapes?: string;
    width?: number;
    height?: number;
    minX?: number;
    minY?: number;
    gap?: number;
    container?: any;
    root?: string;
    scroll?: any;
}
export type SelectShape = (item: IItemConfig) => boolean;
export type ActionShape = (item: IItemConfig) => void;
export declare enum DiagramEvents {
    scroll = "scroll",
    beforeCollapse = "beforecollapse",
    afterCollapse = "aftercollapse",
    beforeExpand = "beforeexpand",
    afterExpand = "afterexpand",
    shapeMouseDown = "shapemousedown",
    shapeClick = "shapeclick",
    shapedDblClick = "shapedblclick",
    shapeIconClick = "shapeiconclick",
    beforeRender = "beforerender",
    shapeHover = "shapeHover",
    emptyAreaClick = "emptyAreaClick",
    load = "load"
}
