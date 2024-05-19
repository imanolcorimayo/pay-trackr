<template>
    <div>
        <PaymentsNewPayment/>
        <PaymentsManagePayment ref="editPayment" :paymentId="paymentId" isTrackerOnly isEdit />
        <h2>Payments this month</h2>
        <div class="p-3 px-0 sm:px-3" v-if="!isLoading">
            <PaymentCard 
                v-for="(payment, index) in payments" :key="index"
                :amount="payment.amount" 
                :description="payment.description" 
                :title="payment.title" 
                :dueDate="payment.dueDate"
                :id="payment.payment_id"
                :isPaid="payment.isPaid"
                @editPayment="showEdit"
            />
        </div>
        <div v-else class="flex justify-center m-10 p-10">
            <Loader size="10"/>
        </div>
        <h4 v-if="payments.length === 0 && !isLoading">Empty list.</h4>
    </div>
</template>

<script setup>
definePageMeta({
    middleware: ['auth']
})

// This is a composable. It only needs a ref to manage 
// the subscription.
const isLoading = ref(true)
const payments = ref([])
const { $dayjs } = useNuxtApp();


const indexStore = useIndexStore();
const { getTracker: tracker, isDataFetched } = storeToRefs(indexStore)
if(!isDataFetched.value) {
    await indexStore.fetchData();
}

payments.value = tracker && tracker.value.payments ? orderPayments(tracker.value.payments) : [];
isLoading.value = false

// ----- Define Vars -------
const paymentId = ref(false)
// Refs
const editPayment = ref(null);

// ----- Define Methods ---------
function showEdit(payId) {
    // Save id that will passed to the edit modal component
    paymentId.value = payId; 

    // Open the modal
    editPayment.value.showModal(payId);
}

// ----- Define Watchers ---------
watch(tracker, (newValue) => {
    isLoading.value = true; // This let us reload the full list and avoid rendering problems
    const auxPayments = newValue.payments ? orderPayments(newValue.payments) : [];
    payments.value = Object.assign([], auxPayments)
    isLoading.value = false;
}, {deep: true})

// ----- Define Methods ---------
useHead({
    title: 'Optimize your finances - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Web page to keep tracking of your main expenses and keep your life organized'
        }
    ]
})
</script>
