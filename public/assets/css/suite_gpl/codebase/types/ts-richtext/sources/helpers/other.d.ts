export declare function getTextHash(blockIndex: number, textIndex: number): string;
export declare function getLinkRefer(): () => string;
export declare function hashToInfo(hash?: string): number[];
export declare function actionIdToModifierValue(id: string): string;
export declare function textColor(color: any): string;
export declare function textSelectColor(color: any): string;
export declare function isEqual(source: any, target: any, deep?: boolean): boolean;
interface IObject {
    [key: string]: any;
}
export declare function copyWithout(obj: IObject, forbidden: IObject): IObject;
export declare function startsWith(str: string, pattern: string): boolean;
export {};
