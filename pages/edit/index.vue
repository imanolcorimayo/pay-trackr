<template>
    <div>
        <h2>Select to edit</h2>
        <ul v-if="!isLoading">
            <li v-for="(payment, index) in payments" :key="index"
                class="">
                <PaymentCard 
                    :amount="payment.amount" 
                    :description="payment.description" 
                    :title="payment.title" 
                    :dueDate="payment.dueDate"
                    :id="payment.id"
                    edit
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
// ----- Define Vars ------
const isLoading = ref(true)

// ----- Define Pinia Vars -----------
const indexStore = useIndexStore();
const { getPayments: payments } = storeToRefs(indexStore);

// ----- Stop loader -----------
// TODO: For now, this is useless
isLoading.value = false

useHead({
    title: 'Edit Payment - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Web page to keep tracking your main expenses and keep your life organized'
        }
    ]
})
</script>
