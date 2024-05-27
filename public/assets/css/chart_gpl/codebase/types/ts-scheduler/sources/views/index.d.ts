import { Agenda } from "./Agenda";
import { Day } from "./Day";
import { Month } from "./Month";
import { Week } from "./Week";
export { IEventsView } from "./Base";
export declare const modes: {
    day: typeof Day;
    month: typeof Month;
    week: typeof Week;
    agenda: typeof Agenda;
};
