<template>
    <div>
        <PaymentsManagePayment ref="editPayment" :paymentId="paymentId" :trackerId="trackerId" isHistoryOnly />
        <h2>Payments' History</h2>
        <div>
            <div v-for="(tracker, index) in formattedHistory" :key="index">
                <div class="hover-expand flex gap-[0.815rem] items-center">
                    <button @click="toggleShow(tracker.id)"
                        class="px-0 text-xl no-button my-0 mt-2 flex items-center justify-between w-full">
                        <span class="w-32 flex-initial text-start">{{ tracker.month }} {{ tracker.year }}</span>
                        <div class="w-full border-b-2 border-solid border-white border-opacity-35"></div>
                    </button>
                    <button @click="newPayInHistory(tracker.id)" class="
                        flex flex-col justify-center items-center text-[0.714rem] font-semibold capitalize text-nowrap h-auto
                    ">
                        Add new pay
                    </button>
                </div>
                <div class="overflow-hidden px-4">
                    <Transition>
                        <div v-if="show[tracker.id]">
                            <PaymentCard 
                                v-for="(pay, idxPay) in tracker.payments" :key="`01-pay-${index}-${idxPay}`"
                                :amount="pay.amount" 
                                :description="pay.description" 
                                :title="pay.title" 
                                :dueDate="pay.dueDate"
                                :id="pay.payment_id"
                                :isPaid="pay.isPaid"
                                :trackerId="tracker.id"
                                @editPayment="showEdit"
                            />
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>

definePageMeta({
    middleware: ['auth']
})
// ----- Define Useful Properties ---------
const { $dayjs } = useNuxtApp();

// ----- Define Vars -------------
const show = ref({});
const formattedHistory = ref([]);
const paymentId = ref(false)
const trackerId = ref(false)
// Refs
const editPayment = ref(null);

// ----- Define Pinia Vars ----------
const indexStore = useIndexStore();
// First load history
console.log("SOMETHING")
await indexStore.loadHistory();
// Retrieve values
const { getHistory: history } = storeToRefs(indexStore);

console.log(history.value)
processHistory();

// ----- Define Methods --------
function processHistory() {
    // Flag to set show object with first element opened
    formattedHistory.value = history.value.map(el => {

        // Set show object
        show.value[el.id] = false;

        const auxObj = {...el};
        const date = $dayjs.unix(el.createdAt.seconds);

        // Format legible date
        auxObj.day =  date.format('D')
        auxObj.month =  date.format('MMM')
        auxObj.weekDay =  date.format('ddd')
        auxObj.year =  date.format('YYYY')

        // Format payments date too
        auxObj.payments = auxObj.payments.map(payment => {
            const auxPayObj = {...payment};
            const payDate = $dayjs(payment.dueDate, { format: 'MM/DD/YYYY' });
            // Format legible date
            auxPayObj.day =  payDate.format('D')
            auxPayObj.month =  payDate.format('MMM')
            auxPayObj.weekDay =  payDate.format('ddd')
            auxPayObj.year =  payDate.format('YYYY')

            return auxPayObj;
        })

        return auxObj;
    })

    show.value[formattedHistory.value[0].id] = true;
}
function toggleShow(id) {
    show.value[id] = !show.value[id];
}
function showEdit(payId, trackrId = false) {
    if(!trackrId) {
        useToast("error", "Something went wrong. Contact us for more details.")
        return;
    }

    // Save id that will passed to the edit modal component
    paymentId.value = payId; 
    trackerId.value = trackrId; 

    // Open the modal
    editPayment.value.showModal();
}
function newPayInHistory(trackerId) {
    console.log("SOME", trackerId)
}

useHead({
    title: 'Payments\' History - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Be able to keep tracking all your payments over time'
        }
    ]
})
</script>


<style scoped>
.v-enter-active,
.v-leave-active {
    transition: all .3s ease-out;
}

.v-enter-from,
.v-leave-to {
    transform: translateY(-3rem);
    opacity: 0;
}

.hover-expand:hover {
    transform: scale(1.01);
    transition: all .2s ease-out;
}

</style>