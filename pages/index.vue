<template>
    <div >
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

// ----- Define Methods ---------
function orderPayments(items) {
    // Sort the array
    items.sort((a, b) => {
        // Sort by isCompleted (false first)
        if (a.isPaid !== b.isPaid) {
            return a.isPaid ? 1 : -1;
        }

        // If isCompleted is the same, sort by dueDate
        const dueDateA = $dayjs(a.dueDate, { format: 'MM/DD/YYYY' });
        const dueDateB = $dayjs(b.dueDate, { format: 'MM/DD/YYYY' });

        return dueDateA - dueDateB;
    });

    return items
}

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
