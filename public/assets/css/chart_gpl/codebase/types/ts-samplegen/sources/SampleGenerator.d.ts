interface ISampleGenConfig {
    sampleFolders?: string[];
}
export declare class SampleGenerator {
    config: ISampleGenConfig;
    private _tabbar;
    private _sampleTabbar;
    private _layout;
    private _toolbar;
    private _input;
    private _state;
    private _form;
    constructor(container: any, config?: {});
    private _attachMainTabbar;
    private _attachToolbar;
    private _drawSampleTabs;
    private _showForm;
    private _parseJSON;
}
export {};
