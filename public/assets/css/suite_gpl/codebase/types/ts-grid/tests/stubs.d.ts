export declare const sizes: {
    scrollTop: number;
    scrollLeft: number;
    scrollWidth: number;
    offsetWidth: number;
    clientWidth: number;
};
export declare const headers: {
    header: ({
        text: string;
        colspan: number;
    } | {
        text: string;
        colspan?: undefined;
    })[];
    width: number;
    id: number;
}[];
export declare const stringHeaders: {
    header: string;
    width: number;
    id: number;
}[];
export declare const dataAfterGroup: ({
    id: string;
    0: string;
    1: any;
    2: string;
    3: string;
    4: any;
    5: any;
} | {
    id: string;
    0: any;
    1: string;
    2: string;
    3: string;
    4: any;
    5: any;
} | {
    id: string;
    0: string;
    1: any;
    2: any;
    3: any;
    4: string;
    5: string;
} | {
    id: string;
    0: any;
    1: string;
    2: any;
    3: any;
    4: string;
    5: string;
})[];
export declare const treeData: ({
    id: string;
    a: number;
    d: string;
    parent?: undefined;
} | {
    id: string;
    a: string;
    d: string;
    parent: string;
})[];
export declare const config: {
    totalWidth: number;
    columns: {
        header: ({
            text: string;
            colspan: number;
        } | {
            text: string;
            colspan?: undefined;
        })[];
        width: number;
        id: number;
    }[];
    headerRowHeight: number;
    span: any;
};
