import { defineStore } from 'pinia'
import { collection, query, where, getDocs, getDoc, addDoc, serverTimestamp, doc, updateDoc, deleteDoc, orderBy } from 'firebase/firestore';
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
    fetched: false,
    historyFetched: false,
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
        isHistoryFetched: (state) => state.historyFetched,
    },
    actions: {
        async fetchData() {
            if(this.isDataFetched) {
                return;
            }
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

                // Fix payments format when they don't contains the timePeriod
                tracker.payments = tracker.payments.map(el => ({...el, timePeriod: (el.timePeriod ? el.timePeriod : "monthly")}))
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
                fetched: true,
                historyFetched: false
            };
        },
        // Create a record
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

                    // Add to recurrent payments only if it's recurrent payment
                    this.$state.payments = [
                        ...this.$state.payments,
                        payment
                    ];
                } else {
                    // Handle one time payments. Create an unique uuid
                    payment.id = uuidv4();
                }

                // ---- Step 2. Update tracker object 
                return await this.addPaymentInTracker(payment);

            } catch (error) {
                console.error(error)
                return false
            }
        },
        async addPaymentInTracker(payment: Payment, trackerParam?: Tracker) {

            const user = useCurrentUser();
            const db = useFirestore();

            if (!user || !user.value) {
                return 'Login first to be able to add payments';
            }

            // Select proper tracker to work with
            let tracker = trackerParam ? trackerParam : Object.assign({}, this.$state.tracker);

            try {
                if(tracker.id === "" && !trackerParam) {
                    // Add this to the tracker object and update it if exist or create it
                    tracker = createTrackerObject(user as Ref<User>, [payment]);
    
                    // Post tracker object on Firestore
                    const newTracker = await addDoc(collection(db, "tracker"), tracker);

                    // Get tracker generated from Firestore in order to have the correct object
                    // This solves a a createdAt format issue
                    const querySnapshot = await getDoc(doc(db, "tracker", newTracker.id));

                    // Create tracker object from snapshot
                    const trackerCreated: Tracker = {
                        id: querySnapshot.id,
                        ...querySnapshot.data(),
                    } as Tracker;

                    tracker.id = newTracker.id
                    // Update Tracker in Pinia state
                    this.$state.tracker = trackerCreated; 
                    // Update History in Pinia state
                    this.$state.history.push(trackerCreated);
                }
                // Update current tracker object
                else if(tracker.id !== "") {
                    const paymentTracker = createPaymentTracker(payment);
                    tracker.payments.push(paymentTracker)
    
                    // Update in firebase. Double check the id exists to avoid typescript errors
                    if (!tracker.id || typeof tracker.id !== "string") {
                        console.error("Error: Tracker id does not exist or has an incorrect format");
                        return false;
                    }
                    const trackerRef = doc(db, "tracker", tracker.id);
                    const auxTracker = Object.assign({}, tracker);
                    // Update doc using paymentRef
                    delete auxTracker.id; // We remove the id as it's not needed on the firebase object
                    // @ts-ignore
                    await updateDoc(trackerRef, auxTracker);

                    // Update Tracker Pinia state if it's not for history only
                    if(!trackerParam) {
                        this.$state.tracker = Object.assign({}, tracker) ;
                    }

                    // Update History in Pinia
                    const trackerIds = this.$state.history.map(e => e.id);
                    // Search index in history using trackerId
                    const trackerIndex = trackerIds.indexOf(tracker.id);

                    // If tracker index is found, update in history array
                    if (trackerIndex !== -1) {
                        this.$state.history[trackerIndex] = tracker;
                    }
                }
                return true;
            } catch (error) {
                console.error(error)
                return false
            }
        },
        async addPaymentInHistory(payment: any, trackerId: string) {
            // Retrieve all tracker ids
            const trackerIds = this.$state.history.map(e => e.id);
            // Search index in history using trackerId
            const trackerIndex = trackerIds.indexOf(trackerId);

            // If tracker index is found, run the addPaymentInTracker function
            if (trackerIndex !== -1) {
                // Verify it's a one time payment
                if(payment.timePeriod === "one-time") {
                    // Handle one time payments. Create an unique uuid (This let us edit the payment later)
                    payment.id = uuidv4();
                    return await this.addPaymentInTracker(payment, this.$state.history[trackerIndex]); 
                } else {
                    // If it's not one time, then it's an error
                    return "Error: When creating payment in history it must be one-time payment.";
                }
            }
        },
        // Remove a record
        async removePayment(paymentId: string) {
            const db = useFirestore();
            // If last payment the logic should change
            const isLastPayment = this.$state.payments.length == 1;

            try {
                // Remove first from main payment object
                await deleteDoc(doc(db, 'payment', paymentId))
                // Update Pinia
                if (!isLastPayment) {
                    const paymentIds = this.$state.payments.map(e => e.id);
                    const index = paymentIds.indexOf(paymentId);
                    if (index > -1) {
                        this.$state.payments.splice(index, 1);
                    }
                } else {
                    this.$state.payments = [];
                }

            } catch (error) {
                console.error(error)
                return false
            }

            // Update tracker
            return await this.removePayInTracker(paymentId);

        },
        async removePayInTracker(paymentId: string, trackerParam?: Tracker) {
            const db = useFirestore();
            const trackerToRemove = trackerParam ? trackerParam : this.$state.tracker;
 
            try {
                // Update payment tracker in firestore and pinia
                // If last element, delete document
                const isLastTrackerPayment = trackerToRemove.payments.length == 1;
                if(
                    isLastTrackerPayment && 
                    !trackerToRemove.payments[0].isPaid && // Last payment should not be paid
                    trackerToRemove.id
                ) {
                    // Delete document directly
                    await deleteDoc(doc(db, "tracker", trackerToRemove.id))

                    // Update history array
                    this.updateTrackerInHistory(trackerToRemove, true);
                    
                    // Update tracker in Pinia only if not passed a specific tracker in parameters
                    if(!trackerParam) {
                        this.$state.tracker = Object.assign({}, defaultObject.tracker);
                    }
                    return true;
                }

                // Only update when there is more than one payment
                const tracker = await removePaymentFromTracker(trackerToRemove, paymentId);

                // Update tracker in Firestore. This is only possible if exists tracker id
                if(tracker.id) {
                    // Update tracker in firebase
                    const trackerRef = doc(db, "tracker", tracker.id);
                    
                    // Update doc in Firestore
                    const auxTracker = Object.assign({}, tracker);
                    delete auxTracker.id;
                    // @ts-ignore
                    await updateDoc(trackerRef, auxTracker);
                }

                // Update history array
                this.updateTrackerInHistory(tracker);

                // Update current tracker in Pinia only if not passed a specific tracker in parameters
                if(!trackerParam) {
                    this.$state.tracker = Object.assign({}, tracker);
                }
                return true; 

            } catch (error) {
                console.error(error)
                return false
            }
        },
        async removePayInHistory(paymentId: string, trackerId: string) {

            // Retrieve all tracker ids
            const trackerIds = this.$state.history.map(e => e.id);
            // Search index in history using trackerId
            const trackerIndex = trackerIds.indexOf(trackerId);

            // If tracker index is found, run the removePayInTracker function
            if (trackerIndex !== -1) {
                return await this.removePayInTracker(paymentId, this.$state.history[trackerIndex]); 
            }
            return false
        },
        // Edit a record
        updateTrackerInHistory(tracker: Tracker, removeElement = false) {
            const trackerIds = this.$state.history.map(e => e.id);
            // Search index in history using trackerId
            const trackerIndex = trackerIds.indexOf(tracker.id);

            // If tracker index is found, update in history array
            if (trackerIndex !== -1 && !removeElement) {
                this.$state.history[trackerIndex] = tracker; 
            } else if(trackerIndex !== -1) {
                // Remove the element directly when it was the last tracker payment
                this.$state.history = this.$state.history.filter(el => el.id !== tracker.id); 
            }
        },
        async editPayment(payment: any, paymentId: string) {
            const db = useFirestore()
            const paymentRef = doc(db, "payment", paymentId);
            const payIndex = this.$state.payments.map(el => el.id).indexOf(paymentId);

            try {
                // Update doc using paymentRef
                await updateDoc(paymentRef, payment);
                this.$state.payments[payIndex] = Object.assign(payment)

                // Update only if not paid yet
                this.editPayInTracker(payment, paymentId);

                return true
            } catch (error) {
                console.error(error)
                return false
            }

        },
        async editPayInTracker(newPayment: Payment, paymentId: string, trackerParam?: Tracker) {
            const db = useFirestore()
            const trackerToEdit = trackerParam ? trackerParam : Object.assign({}, this.$state.tracker); 
            const trackerPayIndex = trackerToEdit.payments.map(el => el.payment_id).indexOf(paymentId);

            try {
                
                if(trackerToEdit.payments[trackerPayIndex] && !trackerToEdit.payments[trackerPayIndex].isPaid) {
                    const trackerPayment = createPaymentTracker(newPayment);
                    
                    // Update in the original object
                    trackerToEdit.payments[trackerPayIndex] = trackerPayment;
    
                    // Update in firebase
                    if (!trackerToEdit.id) {
                        console.error("Error: Tracker id does not exist");
                        return false;
                    }

                    // Create custom object to edit the id and send to Firestore
                    const customTrackerForFirebase = Object.assign({}, trackerToEdit);
                    delete customTrackerForFirebase.id;
                    const trackerRef = doc(db, "tracker", trackerToEdit.id);
                    // @ts-ignore
                    await updateDoc(trackerRef, customTrackerForFirebase);

                    // Update history array
                    this.updateTrackerInHistory(Object.assign({}, trackerToEdit));

                    // Only if there is no tracker param, update in Pinia -> current tracker
                    if(!trackerParam) {
                        this.$state.tracker = trackerToEdit;
                    }

                    // Update History in Pinia
                    const trackerIds = this.$state.history.map(e => e.id);
                    // Search index in history using trackerId
                    const trackerIndex = trackerIds.indexOf(trackerToEdit.id);

                    // If tracker index is found, update in history array
                    if (trackerIndex !== -1) {
                        this.$state.history[trackerIndex] = trackerToEdit;
                    }
                }

                return true;
            } catch (error) {
                console.error(error)
                return false
            }
        },
        async editPayInHistory(payment: Payment, paymentId: string, trackerId: string) {
            // Retrieve all tracker ids
            const trackerIds = this.$state.history.map(e => e.id);
            // Search index in history using trackerId
            const trackerIndex = trackerIds.indexOf(trackerId);

            // If tracker index is found, run the removePayInTracker function
            if (trackerIndex !== -1) {
                return await this.editPayInTracker(payment, paymentId, this.$state.history[trackerIndex]); 
            }
            return false
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
            if (this.$state.history.length && this.isHistoryFetched) {
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

                const currentTracker = {
                    id: doc.id,
                    ...doc.data(),
                } as Tracker;

                // Fix payments format when they don't contains the timePeriod
                currentTracker.payments = currentTracker.payments.map(el => ({...el, timePeriod: (el.timePeriod ? el.timePeriod : "monthly")}))

                trackerHistory.push(currentTracker);
            });

            if (!trackerHistory.length) {
                return this.$state.history = [];
            }

            // Order the payments positions
            for (let index in trackerHistory) {
                trackerHistory[index].payments = orderPayments(trackerHistory[index].payments)
            }

            this.$state.history = trackerHistory;
            this.$state.historyFetched = true;
        }
    }
})


function createTrackerObject(user:Ref<User>, payments: PaymentList):Tracker {

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
        payment_id: payment.id ? payment.id : payment.payment_id, // It could come from payments array or tracker.payments array
        dueDate: updatedDateString,
        isPaid: false,
        title: payment.title,
        description: payment.description,
        amount: payment.amount,
        category: payment.category ? payment.category : 'other',
        timePeriod: payment.timePeriod ? payment.timePeriod : 'monthly',
    }
}

async function removePaymentFromTracker(tracker: Tracker, paymentId: string):Promise<Tracker> {
    // Get paymentIds array
    const paymentIdsInTracker = tracker.payments.map(e => e.payment_id);
    // Search index in payments using paymentId
    const index = paymentIdsInTracker.indexOf(paymentId);

    // Remove from array only if not paid
    if (index !== -1 && !tracker.payments[index].isPaid) {
        tracker.payments.splice(index, 1); 
    }

    // Return cleaned tracker
    return tracker;
}

// Creates a random UUID
function uuidv4() {
    return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
        (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16)
    );
}