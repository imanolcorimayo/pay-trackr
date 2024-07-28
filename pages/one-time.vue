<template>
    <div>
        <Filters @onSearch="searchPayment" />
        <PaymentsManagePayment ref="editPayment" :paymentId="paymentId" isEdit />
        <div class="mt-[2rem] flex flex-col gap-[1.714rem]" v-if="!isLoading">
            <PaymentCard 
                v-for="(payment, index) in oneTimePayments" :key="index"
                :amount="payment.amount" 
                :description="payment.description" 
                :category="payment.category" 
                :title="payment.title" 
                :dueDate="payment.dueDate"
                :isPaid="payment.isPaid"
                :id="payment.payment_id"
                edit
                @editPayment="showEdit"
            />
        </div>
        <div v-else class="flex justify-center m-10 p-10">
            <Loader size="10"/>
        </div>
        <h4 v-if="oneTimePayments.length === 0 && !isLoading">Empty list.</h4>
    </div>
</template>

<script setup>
definePageMeta({
    middleware: ['auth']
})
// ----- Define Vars ------
const isLoading = ref(true)
const paymentId = ref(false)

// Refs
const editPayment = ref(null)

// ----- Define Pinia Vars -----------
const indexStore = useIndexStore();
const { getTracker: tracker, isDataFetched } = storeToRefs(indexStore);

// Fetch necessary data to continue
if (!isDataFetched.value) {
  await indexStore.fetchData();
  await indexStore.loadHistory();
}

// ----- Define Computed --------
const oneTimePayments = computed(() => {
    return orderPayments(tracker.value.payments.filter(payment => payment.timePeriod === 'one-time'))
})

// ----- Define Methods --------
function showEdit(payId) {

    console.log('Show edit', payId)

    // Save payId that will passed to the edit modal component
    paymentId.value = payId; 

    // Open the modal
    editPayment.value.showModal(payId);
}

// ----- Stop loader -----------
// TODO: For now, this is useless
isLoading.value = false

useHead({
    title: 'Payment Details - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Web page to keep tracking your main expenses and keep your life organized'
        }
    ]
})
</script>
