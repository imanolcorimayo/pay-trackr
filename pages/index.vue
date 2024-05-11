<template>
    <div>
        <PaymentsNewPayment ref="newPayment"/>
        <h2>Payments this month</h2>
        <button @click="() => newPayment.showModal()" class="">Add new</button>
        <div class="p-3 px-0 sm:px-3" v-if="!isLoading">
            <PaymentCard 
                v-for="(payment, index) in payments" :key="index"
                :amount="payment.amount" 
                :description="payment.description" 
                :title="payment.title" 
                :dueDate="payment.dueDate"
                :id="payment.payment_id"
                :isPaid="payment.isPaid"
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
// Refs
const newPayment = ref(null)

// ----- Define Methods ---------

// ----- Define Watchers ---------
watch(tracker, (newValue) => {
    payments.value = newValue.payments ? orderPayments(newValue.payments) : [];
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
