<template>
    <div>
        <PaymentsNewPayment ref="newPayment" @onClose="() => showAddButton = true"/>
        <h2>Payments this month</h2>
        <div class="fixed bottom-0 right-0 w-full" v-if="showAddButton">
            <div class="max-w-[57.143rem] m-auto flex justify-end p-[1.429rem]">
                <div class="
                    flex flex-col gap-[0.143rem] items-center
                    ">
                    <button 
                        @click="() => {showAddButton = false;newPayment.showModal()}" 
                        class="
                            flex justify-center items-center rounded-full
                    transition ease-in-out delay-50 hover:-translate-y-1 hover:scale-110 duration-150 w-[3.143rem] h-[3.143rem]
                            
                        ">
                        <MdiPlus class="text-[1.428rem] leading-[1.714rem]"/>
                    </button>
                    <span class="text-[0.714rem] font-semibold capitalize">new pay</span>
                </div>
            </div>
        </div>
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
import MdiPlus from '~icons/mdi/plus';

definePageMeta({
    middleware: ['auth']
})

// This is a composable. It only needs a ref to manage 
// the subscription.
const isLoading = ref(true)
const payments = ref([])
const showAddButton = ref(true);
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
    console.log("SOME")
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
