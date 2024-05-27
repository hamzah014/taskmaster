import { IStat } from "../types";
export declare function getStats(text: string, customStats?: IStat[]): {
    [key: string]: number;
};
export declare function statsRenderer(stats: {
    [key: string]: number;
}, statSequence?: IStat[]): () => any;
