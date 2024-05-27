export declare const dataSample: {
    name: string;
    year: number;
    continent: string;
    form: string;
    gdp: number;
    oil: number;
    balance: number;
}[];
export declare const returnedConfig: {
    rows: string[];
    columns: string[];
    values: {
        id: string;
        method: string;
    }[];
};
export declare const header: ({
    text: number;
    colspan: number;
} | {
    text: string;
    colspan?: undefined;
})[][];
export declare const dataAfterGroup: string[][];
export declare const filteredData: {
    name: string;
    year: number;
    continent: string;
    form: string;
    gdp: number;
    oil: number;
    balance: number;
}[];
export declare const filteredByArray: {
    name: string;
    year: number;
    continent: string;
    form: string;
    gdp: number;
    oil: number;
    balance: number;
}[];
export declare const configSample: {
    data: {
        name: string;
        year: number;
        continent: string;
        form: string;
        gdp: number;
        oil: number;
        balance: number;
    }[];
    fields: {
        rows: string[];
        columns: string[];
        values: {
            id: string;
            method: string;
            label: string;
        }[];
    };
    fieldList: {
        id: string;
        label: string;
    }[];
};
export declare const configWithDate: {
    data: {
        name: string;
        oil: number;
        when: string;
    }[];
    fields: {
        rows: string[];
        columns: (string | {
            id: string;
            group: string;
        })[];
        values: {
            id: string;
            method: string;
            label: string;
        }[];
    };
    fieldList: ({
        id: string;
        label: string;
        type?: undefined;
    } | {
        id: string;
        type: string;
        label?: undefined;
    })[];
};
export declare const normalizedFields: {
    rows: {
        id: string;
    }[];
    columns: {
        id: string;
    }[];
    values: {
        id: string;
        method: string;
    }[];
};
