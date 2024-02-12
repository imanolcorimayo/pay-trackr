import { defineStore } from 'pinia'
import { collection, query, where, getDocs, addDoc, serverTimestamp } from 'firebase/firestore';

interface General {
    payments: Array<any>;
    tracker: Tracker;
}

interface Tracker {
    payments: Array<any>;
    id: string;
    user_id: string;
    createdAt: string;
}

const defaultObject = {
    // Payments will be requested only once
    payments: [],
    // Tracker is the object containing all elements 
    tracker: {
        payments: [],
        id: '',
        user_id: '',
        createdAt: ''
    }
}
export const useIndexStore = defineStore('index', {
    state: (): General => {
        return defaultObject;
    },
    getters: {
        getPayments: (state) => state.payments,
        getTracker: (state) => state.tracker,
    },
    actions: {
        async fetchData() {
            // Index store will manage all logic needed in the app to run
            // First check if there is a user
            const user = useCurrentUser();
            const { $dayjs } = useNuxtApp();
            const payments: Array<any> = [];
            let tracker: Tracker = {
                payments: [],
                id: '',
                user_id: '',
                createdAt: ''
            };

            if (!user || !user.value) {
                // Handle the case when there is no user
                return;
            }

            // Connect with firebase and get payments structure
            const db = useFirestore();
            const querySnapshot = await getDocs(query(collection(db, 'payment'), where('user_id', '==', user.value.uid)));

            querySnapshot.forEach((doc) => {
                payments.push({
                    id: doc.id,
                    ...doc.data(),
                });
            });

            if (!payments.length) {
                return;
            }



            const currentMonthStart = $dayjs().startOf('month');
            const currentMonthEnd = $dayjs().endOf('month');

            // Create logic for the tracker object
            const trackerPayment = await getDocs(query(
                collection(db, 'tracker'), 
                where('user_id', '==', user.value.uid),
                where('createdAt', '>=', currentMonthStart.toDate()),
                where('createdAt', '<=', currentMonthEnd.toDate()),
            ));
            // Should be only one document
            trackerPayment.forEach((doc) => {
                tracker = {
                    id: doc.id,
                    ...doc.data(),
                } as Tracker;
            });

            // If no tacker but payments, then create trackers per this month per each payment
            if (tracker.id === "") {
                tracker = {
                    user_id: user.value.uid,
                    // @ts-ignore
                    createdAt: serverTimestamp(),
                    payments: []
                }
                for (let i = 0; i < payments.length; i++) {
                    // Update payments month
                    const parsedDate = $dayjs(payments[i].dueDate, { format: 'MM/DD/YYYY' });
                    const updatedDate = parsedDate.month($dayjs().month());
                    const updatedDateString = updatedDate.format('MM/DD/YYYY');

                    // Send some data twice to save a log history
                    tracker.payments.push({
                        payment_id: payments[i].id,
                        dueDate: updatedDateString,
                        isPaid: false,
                        title: payments[i].title,
                        description: payments[i].description,
                        amount: payments[i].amount
                    })
                }

                // Post tracker object on Firestore
                await addDoc(collection(db, "tracker"), tracker);
            }


            this.$state = {
                payments: payments,
                tracker: tracker,
            };
        },

        addPayment(payments: Array<any>) {
            this.$state.payments = payments
        },
        editTracker(tracker: Tracker) {
            this.$state.tracker = tracker
        },
    }
})