// Interfaces, this will potentially go to a specific folder in the future
interface Payment {
    isPaid: Boolean;
    dueDate: string;
    amount:2499.1
    description: string;
    payment_id: string;
    title: string;
    category: string;
    timePeriod: string;
    id?: string|number;
}
interface PaymentList extends Array<Payment>{}
interface TrackerList extends Array<Tracker>{}


interface General {
    payments: PaymentList;
    tracker: Tracker;
    history: TrackerList;
    fetched: Boolean;
    historyFetched: Boolean;
}

interface Tracker {
    payments: PaymentList;
    id?: string;
    user_id: string;
    createdAt: string;
}

enum SortType {
    Asc = "asc",
    Desc = "desc",
}

enum SortFields {
    Date = "date",
    Amount = "amount",
}

interface SortOptions {
    type: SortType, 
    field: SortFields
} 