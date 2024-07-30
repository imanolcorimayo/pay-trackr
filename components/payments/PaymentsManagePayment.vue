<template>
    <ModalStructure @onClose="() => emit('onClose')" ref="mainModal">
        <template #header>
            <img class="max-w-[10rem] md:max-w-[21.428rem] m-auto" src="/img/edit-modal.svg" alt="">
            <div class="flex flex-col">
                <p class="text-[1.143rem] font-semibold m-auto text-center" v-if="!isEdit">New Payment</p>
                <p class="text-[1.143rem] font-semibold m-auto text-center" v-else>Edit Payment</p>
                <span v-if="isEdit" class="">This edit will affect only the current month. Previous months are not allowed to edition</span>
            </div>
        </template>
        <template #default>
            <form @submit.prevent="submit()" id="payment-form">
                <div class="flex flex-col gap-[1.714rem] w-full">
                    <div class="w-full flex flex-col gap-[0.213rem]">
                        <label class="font-medium">Payment title *</label>
                        <input class="form-input" v-model="payment.title" required name="title" autocomplete="off" />
                    </div>
                    <div class="w-full flex flex-col gap-[0.213rem]">
                        <label class="font-medium">Description</label>
                        <textarea v-model="payment.description" name="description" autocomplete="off" class="min-h-[7rem] pt-4 form-input" />
                    </div>
                    <div class="grid grid-cols-2 gap-y-[1.714rem] gap-x-[0.571rem] lg:gap-x-[1.714rem]">
                        <div class="w-full flex flex-col gap-[0.213rem]">
                            <label class="font-medium">Amount *</label>
                            <input class="form-input" v-model="payment.amount" type="number" step="0.01" min="0" name="amount" placeholder="0.00" required
                                autocomplete="off">
                        </div>
                        <ClientOnly>
                            <div class="w-full flex flex-col gap-[0.213rem] relative">
                                <label class="font-medium">Next Due Date *</label>
                                <input class="form-input" id="valid-until" readonly name="dueDate" placeholder="mm/dd/yyyy" autocomplete="off"
                                    v-model="payment.dueDate" required @click="showPicker" />
                                <div v-if="pickerVisible && width > 768" ref="picker" class="absolute w-full bottom-0">
                                    <VDatePicker :minDate="minDate" :maxDate="maxDate" isDark class="picker" expanded v-model="date" />
                                </div>
                                <teleport v-else-if="pickerVisible && width <= 768" to="body">
                                    <div ref="secondPicker" id="mobilePicker" class="absolute w-full bottom-0 z-[100]">
                                        <VDatePicker :minDate="minDate" :maxDate="maxDate" isDark class="picker" expanded v-model="date" />
                                    </div>
                                </teleport>
                            </div>
                        </ClientOnly>
                    </div>
                    <div class="w-full flex gap-[0.426rem]">
                        <input type="hidden" name="period" id="time-period" v-model="payment.timePeriod" />
                        <button 
                            type="button"
                            @click="payment.timePeriod = 'monthly'" 
                            :class="{'bg-secondary': payment.timePeriod == 'monthly', 'bg-white text-black': payment.timePeriod !== 'monthly'}" 
                            class="w-full flex-1 p-[0.571rem] rounded-[0.214rem]"
                        >Recurrent</button>
                        <button 
                            type="button"
                            @click="payment.timePeriod = 'one-time'" 
                            :class="{'bg-secondary': payment.timePeriod == 'one-time', 'bg-white text-black': payment.timePeriod !== 'one-time'}" 
                            class="w-full flex-1 p-[0.571rem] rounded-[0.214rem]"
                        >One-time</button>
                    </div>
                    <div class="w-full flex flex-col gap-[0.213rem]">
                        <label class="font-medium">Category *</label>
                        <input v-model="payment.category" name="category" required autocomplete="off" class="capitalize form-input" />
                    </div>
                    <div class="w-full flex items-center gap-4">
                        <label class="font-medium">Paid</label>
                        <RiToggleFill @click="payment.isPaid = false" class="cursor-pointer text-[1.637rem] text-[--success-color]" v-if="payment.isPaid"/>
                        <RiToggleLine @click="payment.isPaid = true" class="cursor-pointer text-[1.637rem] text-[--danger-color]" v-else/>
                        <input v-model="payment.isPaid" name="isPaid" required type="hidden" />
                    </div>
                </div>
            </form>
        </template>
        <template #footer>
            <div v-if="sending" class="btn btn-primary flex justify-center items-center"><Loader class="max-w-[2rem]"/></div>
            <input v-else class="btn btn-primary" :disabled="disableButton" form="payment-form" type="submit" :value="(!paymentId ? 'Add Payment' : 'Edit Payment')">
        </template>
    </ModalStructure>
</template>

<script setup>
import RiToggleFill from '~icons/ri/toggle-fill';
import RiToggleLine from '~icons/ri/toggle-line';

const props = defineProps({
    paymentId: {
        required: false,
        default: false,
    },
    trackerId: {
        required: false,
        default: false,
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
const { width } = useWindowSize();

// ------ Define Pinia Vars --------
const indexStore = useIndexStore();
const { getPayments: payments, getTracker: tracker, getHistory: history } = storeToRefs(indexStore)

// ----- Define Vars ------
const payment = ref({
    title: '',
    description: '',
    amount: null,
    dueDate: '',
    isPaid: false,
    timePeriod: (!props.isEdit && props.isHistoryOnly) ? 'one-time' : 'monthly',
    category: 'other',
})
const pickerVisible = ref(false);
const date = ref(new Date());
// Add min date the beginning of the current month
const minDate = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
// Add max date the end of the current month
const maxDate = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0);
const disableButton = ref(false);
const sending = ref(false);

// Refs
const mainModal = ref(null);
const picker = ref(null);
const secondPicker = ref(null);


// Pre process information if needed
// Get specific payment when it's editing
if(props.paymentId) {
    updatePaymentObject(props.paymentId);
}

// ---- Vue Core Events -------
onClickOutside(picker, ev => {
    // Get elements classes and check if any class contains "vc-" (vc- is the class of the date picker)
    if(Array.from(ev.target.classList).some(cl => cl.includes("vc-"))) {
        return;
    }
    pickerVisible.value = false
});
onClickOutside(secondPicker, ev => {
    // Get elements classes and check if any class contains "vc-" (vc- is the class of the date picker)
    if(Array.from(ev.target.classList).some(cl => cl.includes("vc-"))) {
        return;
    }
    pickerVisible.value = false
});

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

    } else if (tracker.value.payments?.length) {
        filteredPayment = tracker.value.payments.filter(el => el.payment_id == payId)
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
async function submit() {
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
    payment.value.dueDate = newVal ? $dayjs(newVal).format('MM/DD/YYYY') : "";
})

// ----- Define Expose ---------
defineExpose({showModal, closeModal})

</script>

<style scoped>

</style>