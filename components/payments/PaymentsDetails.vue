<template>
    <ModalStructure @onClose="() => emit('onClose')" ref="mainModal">
        <template #header>
            <img class="max-w-[21.428rem] m-auto" src="/img/details-modal.svg" alt="">
        </template>
        <template #default>
            <div class="flex flex-col gap-[2.143rem]">
                <div class="flex flex-col gap-[1.143rem]">
                    <div class="flex flex-col gap-[0.714rem]">
                        <span class="text-[1.143rem] font-medium">Payment Title</span>
                        <span>{{ payment.title }}</span>
                    </div>
                    <div class="flex flex-col gap-[0.714rem]">
                        <span class="text-[1.143rem] font-medium">Payment Description</span>
                        <span>{{ (payment.description ? payment.description : "N/A") }}</span>
                    </div>
                    <div class="flex justify-between">
                        <div class="flex flex-col gap-[0.714rem]">
                            <span class="text-[1.143rem] font-medium">Payment amount</span>
                            <span>{{ formatPrice(payment.amount) }}</span>
                        </div>
                        <div class="flex flex-col gap-[0.714rem]">
                            <span class="text-[1.143rem] font-medium">Due Date</span>
                            <span>{{ formatPrice(payment.dueDate) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-[0.714rem]">
                        <span class="text-[1.143rem] font-medium">Category</span>
                        <span>{{ (payment.category ? payment.category : "N/A") }}</span>
                    </div>
                    <div class="flex flex-col gap-[0.714rem]">
                        <span class="text-[1.143rem] font-medium">Is Paid</span>
                        <span class="font-medium" :class="{'text-[--success-color]': payment.isPaid,'text-[--danger-color]': !payment.isPaid}">{{ (payment.isPaid ? "Yes" : "No") }}</span>
                    </div>
                </div>
                <hr>
                <div class="flex flex-col gap-[0.714rem]">
                    <span class="text-[1.143rem] font-medium">Payment History</span>
                    <div class="flex justify-between" v-for="(pay, index) in payHistory" :key="index">
                        <span v-if="typeof pay == 'object'" class="flex-1">{{ pay.dueDate }}</span>
                        <div v-if="typeof pay == 'object'" class="flex justify-between flex-1">
                            <span>{{ formatPrice(pay.amount) }}</span>
                            <span class="font-medium" :class="{'text-[--success-color]': pay.isPaid,'text-[--danger-color]': !pay.isPaid}">{{ (pay.isPaid ? "Paid" : "Unpaid") }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <button @click="closeAndOpenEdit" class="bg-white text-black rounded-[0.214rem] p-[0.571rem] font-medium">Edit Payment</button>
        </template>
    </ModalStructure>
</template>

<script setup>
const props = defineProps({
    paymentId: {
        required: false,
        default: false,
    },
    trackerId: {
        required: false,
        default: false,
    }
})
const emit = defineEmits(["onClose", "openEdit"]);

// ------ Define Useful Properties ----------
const { $dayjs } = useNuxtApp()

// ------ Define Pinia Vars --------
const indexStore = useIndexStore();
const { getTracker: tracker, getHistory: history } = storeToRefs(indexStore)

// ----- Define Vars ------
const payment = ref({
    title: '',
    description: '',
    amount: null,
    dueDate: '',
    timePeriod: (!props.isEdit && props.isHistoryOnly) ? 'one-time' : 'monthly',
    category: 'other',
})
const pickerVisible = ref(false);
const picker = ref(null);
const date = ref(new Date());
const payHistory = ref([]);

// Refs
const mainModal = ref(null) 

// Pre process information if needed
// Get specific payment when it's editing
if(props.paymentId) {
    updatePaymentObject(props.paymentId);
}

// ---- Vue Core Events -------
onClickOutside(picker, event => {
    pickerVisible.value = false
})

// ----- Define Methods ---------
function showModal(payId = false, trackerId = false, isEdit = false) {
    // Set default value if no payId has been set
    if(!isEdit && props.isHistoryOnly && !payId) {
        payment.value = {
            title: "",
            description: "",
            amount: null,
            dueDate: "",
            timePeriod: "one-time",
            category: "other",
        };
    }

    // Update the payment object every time it is opened (only if payId exists)
    payId && updatePaymentObject(payId ? payId : props.paymentId, trackerId ? trackerId : props.trackerId);

    // Show modal
    mainModal.value.showModal();
}
function closeModal() {
    mainModal.value.closeModal();
}
function closeAndOpenEdit() {
    emit("openEdit", props.paymentId);
    closeModal();
}
function updatePaymentObject(payId) {

    payHistory.value = []; // Rest object
    const filteredPayment = tracker.value.payments.filter(el => el.payment_id == payId)

    // Check if there is a payment
    if (!filteredPayment || !filteredPayment.length) {
        console.error("No such document!");
        useToast("error", "This payment does not exists.")
        closeModal();
        return;
    } else {

        // Check if it contains category
        if(!filteredPayment[0].category) {
            filteredPayment[0].category = "other"
        }

        payment.value = Object.assign({}, filteredPayment[0]);
    }

    // Update pay history
    payHistory.value = history.value.map(e => {
        // Get payment info in e.payments
        const pay = JSON.parse(JSON.stringify(e.payments.filter(p => {
            return p.payment_id == payId
        })));  

        if(pay[0]) {
            // format dueDate to be MMM DD, YYYY
            pay[0].dueDate = $dayjs(pay[0].dueDate, { format: 'MM/DD/YYYY' }).format('MMM DD, YYYY')
            return pay[0];
        }

        return false;
    })
}
// ----- Define Watchers -------------
watch(date, (newVal) => {
    pickerVisible.value = false;
    payment.value.dueDate = $dayjs(newVal).format('MM/DD/YYYY')
})

// ----- Define Expose ---------
defineExpose({showModal, closeModal})

</script>

<style scoped>

</style>