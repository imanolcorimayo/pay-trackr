<template>
    <div class="container">
        <h2>New Payment</h2>
        <form v-if="!sending" @submit.prevent="addPayment()" class="mt-2">
            <label>Title *</label>
            <input placeholder="Add payment title here" v-model="payment.title" required name="title" autocomplete="off" />
            <label>Description</label>
            <textarea v-model="payment.description" name="description" autocomplete="off" class="min-h-20" />
            <label>Amount *</label>
            <input v-model="payment.amount" type="number" step="0.01" min="0" name="amount" placeholder="0.00" required
                autocomplete="off">
            <ClientOnly>
                <label>Next Due Date *</label>
                <input id="valid-until" name="dueDate" placeholder="mm/dd/yyyy" autocomplete="off"
                    v-model="payment.dueDate" required @click="showPicker" />
                <div v-if="pickerVisible" ref="picker" class="relative">
                    <VDatePicker expanded v-model="date" />
                </div>
            </ClientOnly>
            <label>Payment Time Period *</label>
            <select v-model="payment.timePeriod" name="period" id="time-period" class="p-2.5">
                <option disabled value="weekly">Weekly</option>
                <!-- Every two weeks -->
                <option disabled value="bi-weekly">Bi-Weekly</option>
                <!-- 2 times in a month -->
                <option disabled value="semi-monthly">Semi Monthly</option>
                <option value="monthly" selected>Monthly</option>
            </select>
            <input :disabled="disableButton" type="submit" value="Add Payment">
        </form>
        <div v-else class="flex justify-center m-10 p-10">
            <Loader size="10" />
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    middleware: ['auth']
})
// used for the firestore refs
const { $dayjs } = useNuxtApp()
const disableButton = ref(false);
const sending = ref(false);

const indexStore = useIndexStore();

const payment = ref({
    title: '',
    description: '',
    amount: null,
    dueDate: '',
    timePeriod: 'monthly'
})
const pickerVisible = ref(false)
const picker = ref(null)
const date = ref(new Date())

// ----- Define Methods && Events ---------
onClickOutside(picker, event => {
    pickerVisible.value = false
})

async function addPayment() {
    // Block add button and show loader
    disableButton.value = true;
    sending.value = true;

    const validate = validatePayment(payment.value);
    if(typeof validate == 'string') {
        useToast('error', validate)
        disableButton.value = false;
        sending.value = false;
    }

    // save data in firebase
    const result = await indexStore.addPayment(payment.value);
    if (!result) {
        useToast('error', 'Something went wrong, please try again')
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
        timePeriod: 'monthly'
    }

    disableButton.value = false;
    sending.value = false;
    useToast('success', 'Payment saved successfully. Click to go home.', { onClick: "goHome", autoClose: 2000 })
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

useHead({
    title: 'Add New Payment - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Web page to keep tracking your main expenses and keep your life organized'
        }
    ]
})
</script>
