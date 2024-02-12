<template>
    <div >
        <h2>Payments this month</h2>
        <ul v-if="!isLoading">
            <li v-for="(payment, index) in payments" :key="index"
                class="">
                <PaymentCard 
                    :amount="payment.amount" 
                    :description="payment.description" 
                    :title="payment.title" 
                    :dueDate="payment.dueDate"
                    :id="payment.payment_id"
                />
            </li>
        </ul>
        <div v-else class="flex justify-center m-10 p-10">
            <Loader size="10"/>
        </div>
        <h4 v-if="payments.length === 0 && !isLoading">Empty list.</h4>
    </div>
</template>

<script setup>


// This is a composable. It only needs a ref to manage 
// the subscription.
const isLoading = ref(true)
const payments = ref([])


const indexStore = useIndexStore();
const { getTracker: tracker } = storeToRefs(indexStore)
payments.value = tracker && tracker.value.payments ? tracker.value.payments : [];
isLoading.value = false

watch(tracker, (newValue) => {
    console.log(newValue)
    payments.value = newValue.payments ? newValue.payments : [];
})

// ----- Define Methods ---------
useHead({
    title: 'Optimize your finances - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Web page to keep tracking your main expenses and keep your life organized'
        }
    ]
})
</script>
