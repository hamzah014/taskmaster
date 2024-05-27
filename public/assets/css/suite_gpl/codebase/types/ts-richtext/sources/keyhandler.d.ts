import { IOperation } from "./types";
export declare enum Key {
    backspace = 8,
    enter = 13,
    tab = 9,
    esc = 27,
    space = 32,
    left = 37,
    up = 38,
    right = 39,
    down = 40,
    insert = 45,
    del = 46,
    ctrl = 17,
    a = 65,
    b = 66,
    c = 67,
    s = 83,
    u = 85,
    v = 86,
    x = 88,
    y = 89,
    z = 90,
    i = 73
}
export declare function keyToOperation(e: KeyboardEvent): IOperation;
