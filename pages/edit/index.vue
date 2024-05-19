<template>
    <div>
        <h2>Select to edit</h2>
        <PaymentsNewPayment/>
        <PaymentsManagePayment ref="editPayment" :paymentId="paymentId" />
        <div class="p-3 sm:px-0" v-if="!isLoading">
            <PaymentCard 
                v-for="(payment, index) in payments" :key="index"
                :amount="payment.amount" 
                :description="payment.description" 
                :title="payment.title" 
                :dueDate="payment.dueDate"
                :id="payment.id"
                edit
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
// ----- Define Vars ------
const isLoading = ref(true)
const paymentId = ref(false)

// Refs
const editPayment = ref(null)

// ----- Define Pinia Vars -----------
const indexStore = useIndexStore();
const { getPayments: payments } = storeToRefs(indexStore);

// ----- Define Methods --------
function showEdit(payId) {
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
