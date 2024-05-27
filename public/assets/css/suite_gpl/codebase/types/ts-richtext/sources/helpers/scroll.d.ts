export interface IScrollHelper {
    update: () => void;
    saveScrollTop: () => void;
    restoreScrollTop: () => void;
}
export declare function createScrollHelper(element: HTMLElement): {
    update(): void;
    saveScrollTop(): void;
    restoreScrollTop(): void;
};
