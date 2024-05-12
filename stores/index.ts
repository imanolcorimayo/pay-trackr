import { defineStore } from 'pinia'
import { collection, query, where, getDocs, addDoc, serverTimestamp, doc, updateDoc, deleteDoc, orderBy } from 'firebase/firestore';
import type { User } from 'firebase/auth';


const defaultObject = {
    // Payments will be requested only once
    payments: [],
    // Tracker is the object containing all elements 
    tracker: {
        payments: [],
        id: '',
        user_id: '',
        createdAt: ''
    },
    history: [],
    fetched: false
}
export const useIndexStore = defineStore('index', {
    state: (): General => {
        return Object.assign({}, defaultObject) ;
    },
    getters: {
        getPayments: (state) => state.payments,
        getTracker: (state) => state.tracker,
        getHistory: (state) => state.history,
        isDataFetched: (state) => state.fetched,
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
                tracker = createTrackerObject(user as Ref<User>, payments);

                // Post tracker object on Firestore
                const newTracker = await addDoc(collection(db, "tracker"), tracker);
                // Add the id in case it's the first time the document is created
                tracker.id = newTracker.id
            }


            this.$state = {
                payments: payments,
                tracker: Object.assign({}, tracker),
                history: [],
                fetched: true
            };
        },
        async addPayment(payment: any) {
            const user = useCurrentUser();
            const db = useFirestore();

            if (!user || !user.value) {
                return 'Login first to be able to add payments';
            }

            try {

                // ---- Step 1. Create recurrent payment if it's the case. 
                // In case it's a one time payment, simulate an id
                if(payment.timePeriod !== "one-time") {
                    // Handle recurrent payments
                    const newPayment = await addDoc(collection(db, "payment"), {
                        ...payment,
                        createdAt: serverTimestamp(),
                        user_id: user.value.uid
                    });
                    payment.id = newPayment.id;
                } else {
                    // Handle one time payments. Create an unique uuid
                    payment.id = uuidv4();
                }

                this.$state.payments = [
                    ...this.$state.payments,
                    payment
                ];

                // ---- Step 2. Update tracker object 
                let tracker = Object.assign({}, this.$state.tracker) ;

                if(tracker.id === "") {
                    // Add this to the tracker object and update it if exist or create it
                    tracker = createTrackerObject(user as Ref<User>, this.$state.payments);

                    // Post tracker object on Firestore
                    const newTracker = await addDoc(collection(db, "tracker"), tracker);
                    // Add the id in case it's the first time the document is created
                    tracker.id = newTracker.id
                    // Update Pinia state
                    this.$state.tracker = tracker;

                    return true;
                } 
                // Update current tracker object
                else {
                    const paymentTracker = createPaymentTracker(payment);
                    tracker.payments.push(paymentTracker)

                    // Update Pinia state
                    this.$state.tracker = Object.assign({}, tracker) ;

                    // Update in firebase
                    if (!this.$state.tracker.id) {
                        console.log("Error: Tracker id does not exist");
                        return false;
                    }
                    const trackerRef = doc(db, "tracker", this.$state.tracker.id);
                    // Update doc using paymentRef
                    delete tracker.id; // We remove the id as it's not needed on the firebase object
                    await updateDoc(trackerRef, tracker);
                }

                return true;

            } catch (error) {
                console.error(error)
                return false
            }
        },
        async removePayment(id: string) {
            const db = useFirestore();
            // If last payment the logic should change
            const isLastPayment = this.$state.payments.length == 1;
            const isLastTrackerPayment = this.$state.tracker.payments.length == 1;

            try {
                // Remove first from main payment object
                await deleteDoc(doc(db, 'payment', id))
                // Update Pinia
                if (!isLastPayment) {
                    const paymentIds = this.$state.payments.map(e => e.id);
                    const index = paymentIds.indexOf(id);
                    if (index > -1) {
                        this.$state.payments.splice(index, 1);
                    }
                } else {
                    this.$state.payments = [];
                }

                // Update payment tracker
                const tracker = Object.assign({}, this.$state.tracker)

                // Only update when there is more than one payment
                if(!isLastTrackerPayment && this.$state.tracker.id) {
                    const paymentsInTracker = tracker.payments.map(e => e.payment_id);
                    const index = paymentsInTracker.indexOf(id);
                    if (index > -1 && !tracker.payments[index].isPaid) {
                        tracker.payments.splice(index, 1);
                    }
                    
                    // Update tracker in firebase
                    const trackerRef = doc(db, "tracker", this.$state.tracker.id);
                    
                    // Update tracker in Pinia
                    this.$state.tracker = Object.assign({}, tracker) ;

                    delete tracker.id;
                    await updateDoc(trackerRef, tracker);

                    return true;
                } else if(!tracker.payments[0].isPaid && this.$state.tracker.id) {
                    // Delete document directly
                    await deleteDoc(doc(db, "tracker", this.$state.tracker.id))

                    // Update tracker in Pinia
                    this.$state.tracker = Object.assign({}, defaultObject.tracker);
                    return true;
                }
            } catch (error) {
                console.error(error)
                return false
            }
        },
        async editPayment(payment: any, id: string) {
            const db = useFirestore()
            const paymentRef = doc(db, "payment", id);
            const payIndex = this.$state.payments.map(el => el.id).indexOf(id);
            const trackerPayIndex = this.$state.tracker.payments.map(el => el.payment_id).indexOf(id);

            try {
                // Update doc using paymentRef
                await updateDoc(paymentRef, payment);
                this.$state.payments[payIndex] = Object.assign(payment)

                // Update only if not paid yet
                if(!this.$state.tracker.payments[trackerPayIndex].isPaid) {
                    const trackerPayment = createPaymentTracker(payment);
                    // Update in Pinia
                    this.$state.tracker.payments[trackerPayIndex] = trackerPayment;

                    // Update in firebase
                    if (!this.$state.tracker.id) {
                        console.log("Error: Tracker id does not exist");
                        return false;
                    }
                    const customTrackerForFirebase = Object.assign({}, this.$state.tracker);
                    delete customTrackerForFirebase.id;
                    const trackerRef = doc(db, "tracker", this.$state.tracker.id);
                    await updateDoc(trackerRef, customTrackerForFirebase);
                }

                return true
            } catch (error) {
                console.error(error)
                return false
            }

        },
        async editIsPaid(id: string, value: Boolean) {
            const db = useFirestore()
            const trackerPayIndex = this.$state.tracker.payments.map(el => el.payment_id).indexOf(id);
            const tracker = Object.assign({}, this.$state.tracker);

            if(!this.$state.tracker.id) {
                return false;
            }

            // Update firebase
            tracker.payments[trackerPayIndex].isPaid = value;
            const trackerRef = doc(db, "tracker", this.$state.tracker.id);
            delete tracker.id;
            await updateDoc(trackerRef, tracker);

            // Update Pinia
            this.$state.tracker.payments[trackerPayIndex].isPaid = value;

            return true;
        },
        async editIsPaidInHistory(paymentId: string, trackerId: string, value: Boolean) {
            const db = useFirestore()

            // If there is no elements on history, return false
            if(!this.$state.history.length) {
                return false;
            }

            // 1. Find the tracker based on the tracker id
            // Look for the index of the tracker
            const trackerIndex = this.$state.history.map(el => {
                // "el" en "el.id" could be undefined
                if(el && el.id) {
                    return el.id
                }
            }).indexOf(trackerId);

            // Get a clean history that will be modified
            const history: TrackerList = Object.assign({}, this.$state.history);

            // Ensure the tracker exists
            if(trackerIndex === -1) {
                return false;
            }

            // 2. Find the payment in the tracker found based on the payment id
            const auxTracker: Tracker|undefined = history[trackerIndex];
            if(auxTracker === undefined || auxTracker.id === undefined) {
                return false;
            }

            const trackerPayIndex = auxTracker.payments.map(el => el.payment_id).indexOf(paymentId);

            if(trackerPayIndex === -1) {
                return false;
            }

            // Update firebase
            auxTracker.payments[trackerPayIndex].isPaid = value;
            const trackerRef = doc(db, "tracker", auxTracker.id as string);
            delete auxTracker.id; // We don't update the id

            // @ts-ignore
            await updateDoc(trackerRef, auxTracker);

            // Update Pinia && auxiliary variables
            this.$state.history[trackerIndex]!.payments[trackerPayIndex].isPaid = value;

            // Order the payments positions in history
            for (let index in this.$state.history) {
                this.$state.history[index].payments = orderPayments(this.$state.history[index].payments)
            }

            return true;
        },
        // History is all trackers from all time
        async loadHistory() {
            // If already loaded, return
            if (this.$state.history.length) {
                return;
            }

            // Get user id
            const user = useCurrentUser()

            if (!user || !user.value) {
                // Handle the case when there is no user
                return;
            }

            // Connect with firebase and get payments structure
            const db = useFirestore();
            const querySnapshot = await getDocs(query(collection(db, 'tracker'), where('user_id', '==', user.value.uid), orderBy("createdAt", "desc")));

            // Tracker history object 
            const trackerHistory:TrackerList = [];
            querySnapshot.forEach((doc) => {
                trackerHistory.push({
                    id: doc.id,
                    ...doc.data(),
                } as Tracker);
            });

            if (!trackerHistory.length) {
                return this.$state.history = [];
            }

            // Order the payments positions
            for (let index in trackerHistory) {
                trackerHistory[index].payments = orderPayments(trackerHistory[index].payments)
            }

            this.$state.history = trackerHistory;
        }
    }
})


function createTrackerObject(user:Ref<User>, payments: Array<any>):Tracker {

    const tracker: Tracker = {
        user_id: user.value.uid,
        // @ts-ignore
        createdAt: serverTimestamp(),
        payments: []
    }
    for (let i = 0; i < payments.length; i++) {
        tracker.payments.push(createPaymentTracker(payments[i]))
    }

    return tracker;

}

function createPaymentTracker(payment: any):Payment {
    const { $dayjs } = useNuxtApp();

    // Update payment month
    const parsedDate = $dayjs(payment.dueDate, { format: 'MM/DD/YYYY' });
    const updatedDate = parsedDate.month($dayjs().month());
    const updatedDateString = updatedDate.format('MM/DD/YYYY');
    
    // Send some data twice to save a log history
    return {
        payment_id: payment.id,
        dueDate: updatedDateString,
        isPaid: false,
        title: payment.title,
        description: payment.description,
        amount: payment.amount,
        category: payment.category ? payment.category : 'other',
    }

}

// Creates a random UUID
function uuidv4() {
    return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
        (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16)
    );
}