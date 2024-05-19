<template>
    <ModalStructure @onClose="() => emit('onClose')" ref="mainModal">
        <template #header>
            <p class="text-[1.143rem] font-semibold">New Payment</p>
            <span class="text-[0.857rem] text-stone-400">You can create a regular payment or a one time payment</span>
        </template>
        <template #default>
            <form @submit.prevent="addOrEditPayment()" id="payment-form">
                <div class="flex flex-col gap-[1.714rem] w-full">
                    <div class="w-full flex flex-col relative">
                        <label class="absolute top-[-0.4rem] font-semibold text-[0.857rem] leading-[1rem] bg-[--secondary-bg-color] ml-[0.571rem] px-[0.286rem]">Payment title *</label>
                        <input v-model="payment.title" required name="title" autocomplete="off" />
                    </div>
                    <div class="w-full flex flex-col relative">
                        <label class="absolute top-[-0.4rem] font-semibold text-[0.857rem] leading-[1rem] bg-[--secondary-bg-color] ml-[0.571rem] px-[0.286rem]">Description</label>
                        <textarea v-model="payment.description" name="description" autocomplete="off" class="min-h-[7rem] pt-4" />
                    </div>

                    <div class="grid grid-cols-2 gap-y-[1.714rem] gap-x-[0.571rem] lg:gap-x-[1.714rem]">
                        <div class="w-full flex flex-col relative">
                            <label class="absolute top-[-0.4rem] font-semibold text-[0.857rem] leading-[1rem] bg-[--secondary-bg-color] ml-[0.571rem] px-[0.286rem]">Amount *</label>
                            <input v-model="payment.amount" type="number" step="0.01" min="0" name="amount" placeholder="0.00" required
                                autocomplete="off">
                        </div>
                        <div class="w-full flex flex-col relative">
                            <label class="absolute top-[-0.4rem] font-semibold text-[0.857rem] leading-[1rem] bg-[--secondary-bg-color] ml-[0.571rem] px-[0.286rem]">Category *</label>
                            <input v-model="payment.category" name="category" required autocomplete="off" class="capitalize" />
                        </div>
                        <ClientOnly>
                            <div class="w-full flex flex-col relative">
                                <label class="absolute top-[-0.4rem] font-semibold text-[0.857rem] leading-[1rem] bg-[--secondary-bg-color] ml-[0.571rem] px-[0.286rem]">Next Due Date *</label>
                                <input id="valid-until" name="dueDate" placeholder="mm/dd/yyyy" autocomplete="off"
                                    v-model="payment.dueDate" required @click="showPicker" />
                                <div v-if="pickerVisible" ref="picker" class="absolute w-full bottom-0">
                                    <VDatePicker expanded v-model="date" />
                                </div>
                            </div>
                        </ClientOnly>
                        <div class="w-full flex flex-col relative">
                            <label class="absolute top-[-0.4rem] font-semibold text-[0.857rem] leading-[1rem] bg-[--secondary-bg-color] ml-[0.571rem] px-[0.286rem]">Payment Time Period *</label>
                            <select v-model="payment.timePeriod" name="period" id="time-period" class="p-2.5">
                                <option disabled value="weekly">Weekly</option>
                                <!-- Every two weeks -->
                                <option disabled value="bi-weekly">Bi-Weekly</option>
                                <!-- 2 times in a month -->
                                <option disabled value="semi-monthly">Semi Monthly</option>
                                <option :disabled="!isEdit && isHistoryOnly" value="monthly" selected>Monthly</option>
                                <option value="one-time">One Time Pay</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </template>
        <template #footer>
            <input :disabled="disableButton" form="payment-form" type="submit" :value="(!paymentId ? 'Add Payment' : 'Edit Payment')">
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
    },
    isTrackerOnly: {
        required: false,
        default: false,
        type: Boolean
    },
    isHistoryOnly: {
        required: false,
        default: false,
        type: Boolean
    },
    isEdit: {
        required: false,
        default: false,
        type: Boolean
    },
})
const emit = defineEmits(["onClose"]);

// ------ Define Useful Properties ----------
const { $dayjs } = useNuxtApp()

// ------ Define Pinia Vars --------
const indexStore = useIndexStore();
const { getPayments: payments, getTracker: tracker, getHistory: history } = storeToRefs(indexStore)

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
const disableButton = ref(false);
const sending = ref(false);

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
function updatePaymentObject(payId, trackerId) {

    let filteredPayment;
    // Look for the payment in the specific place
    if(props.isHistoryOnly && history.value.length) {
        // Get tracker
        const trackerIds = history.value.map(e => e.id);
        // Search index in history using trackerId
        const trackerIndex = trackerIds.indexOf(trackerId);

        // Check this to be on the safe side
        if(trackerIndex !== -1 && history.value[trackerIndex] && history.value[trackerIndex].payments?.length) {
            // Find payment in tracker
            filteredPayment = history.value[trackerIndex].payments.filter(el => el.payment_id == payId)
        }

    } else if(props.isTrackerOnly && tracker.value.payments?.length) {
        filteredPayment = tracker.value.payments.filter(el => el.payment_id == payId)
    } else {
        filteredPayment = payments.value.filter(el => el.id == payId)
    }

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
}

// ----- Define Methods ---------
async function addOrEditPayment() {
    // Block add button and show loader
    disableButton.value = true;
    sending.value = true;

    const validate = validatePayment(payment.value);
    if(typeof validate == 'string') {
        useToast('error', validate)
        disableButton.value = false;
        sending.value = false;
        closeModal();
        return;
    }

    // Save data in Firestore. If Payment id exists it's an editing process
    let result;
    if(props.isEdit) {
        // Analyze which function to execute
        if(props.isHistoryOnly) {
            // Edit only in history
            result = await indexStore.editPayInHistory(payment.value, props.paymentId, props.trackerId);
        } else if(props.isTrackerOnly) {
            // Edit only in tracker (this will be reflected on history too)
            result = await indexStore.editPayInTracker(payment.value, props.paymentId);
        } else {
            // Edit recurrent and history and tracker if it's the case (if not marked as paid)
            result = await indexStore.editPayment(payment.value, props.paymentId);
        }
    } else {
        if(props.isHistoryOnly && props.trackerId) {
            result = await indexStore.addPaymentInHistory(payment.value, props.trackerId);
        } else {
            result = await indexStore.addPayment(payment.value);
        }
    }
    if (!result || typeof result == "string") {
        useToast('error', typeof result == "string" ? result : "Something went wrong, please try again")
        // Un-Block add button
        sending.value = false;
        disableButton.value = false;
        return;
    }

    // Reset payment object
    payment.value = {
        title: '',
        description: '',
        amount: null,
        dueDate: '',
        category: 'other',
        timePeriod: (!props.isEdit && props.isHistoryOnly) ? 'one-time' : 'monthly'
    }

    disableButton.value = false;
    sending.value = false;
    useToast('success', 'Payment saved successfully. Click to go home.', { onClick: "goHome", autoClose: 2000 })
    closeModal();
}
// Calendar methods
function showPicker() {
    pickerVisible.value = true;
}

// ----- Define Watchers -------------
watch(date, (newVal) => {
    pickerVisible.value = false;
    payment.value.dueDate = $dayjs(newVal).format('MM/DD/YYYY')
})

// ----- Define Expose ---------
defineExpose({showModal, closeModal})

</script>

<style>

</style>