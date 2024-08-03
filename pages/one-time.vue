<template>
    <div>
        <Filters @onSearch="searchPayment" @onOrder="orderOneTime"/>
        <PaymentsManagePayment ref="editPayment" :paymentId="paymentId" isEdit />
        <div class="mt-[2rem] flex flex-col gap-[1.714rem]" v-if="!isLoading">
            <PaymentCard v-for="(payment, index) in searchedPayments" :key="index" :amount="payment.amount"
                :description="payment.description" :category="payment.category" :title="payment.title"
                :dueDate="payment.dueDate" :isPaid="payment.isPaid" :id="payment.payment_id" edit
                @editPayment="showEdit" />
        </div>
        <div v-else class="flex justify-center m-10 p-10">
            <Loader size="10" />
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
const searchedPayments = ref([]);

// Fetch necessary data to continue
if (!isDataFetched.value) {
    await indexStore.fetchData();
    await indexStore.loadHistory();
}

// ----- Define Computed --------
const oneTimePayments = computed(() => {
    return orderPayments(tracker.value.payments.filter(payment => payment.timePeriod === 'one-time'))
})
searchedPayments.value = oneTimePayments.value; 

// ----- Define Methods --------
function showEdit(payId) {
    // Save payId that will passed to the edit modal component
    paymentId.value = payId;

    // Open the modal
    editPayment.value.showModal(payId);
}

function orderOneTime(orderQuery) {

    // Order one-time payments
    if (orderQuery && orderQuery.name) {
        searchedPayments.value = orderPayments(searchedPayments.value, { filed: orderQuery.name, type: orderQuery.order });
        return;
    }

    searchedPayments.value = orderPayments(searchedPayments.value);
}
function searchPayment(query) {
    // Filter payments based on the query
    searchedPayments.value = oneTimePayments.value.filter(payment => {

        const isInTitle = payment.title.toLowerCase().includes(query.toLowerCase());
        const isInAmount = payment.amount.toString().toLowerCase().includes(query.toLowerCase());
        const isInDescription = payment.description.toString().toLowerCase().includes(query.toLowerCase());
        const isInCategory = payment.category.toString().toLowerCase().includes(query.toLowerCase());
        const isInDate = payment.dueDate.toString().toLowerCase().includes(query.toLowerCase());

        return isInTitle || isInAmount || isInDescription || isInCategory || isInDate
    });
}

// ----- Stop loader -----------
isLoading.value = false

// ----- Define Watchers --------
watch(oneTimePayments, (newVal) => {
    searchedPayments.value = newVal;
})

// ----- Define Hooks --------
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
