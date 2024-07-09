<template>
    <div>
        <PaymentsNewPayment/>
        <PaymentsManagePayment ref="editPayment" :paymentId="paymentId" isTrackerOnly isEdit />
        <div class="flex flex-col items-start md:flex-row md:justify-between">
            <h2>Payments this month</h2>
            <div class="flex gap-[0.571rem]">
                <div
                    :class="[
                    'flex items-center border-[1.5px] border-opacity-30 border-white rounded-[0.4rem] h-[2.423rem] transition-all duration-300',
                    isExpanded ? 'w-[20rem] border-2' : 'w-[2.423rem] hover:cursor-pointer hover:border-2'
                    ]"
                    @click="expandInput"
                >
                    <IcTwotoneSearch class="ml-2" />
                    <input
                    type="text"
                    v-model="paymentSearch"
                    v-show="isExpanded"
                    @input="searchPayment"
                    @blur="collapseInput"
                    class="flex-grow px-2 py-1 bg-transparent border-none outline-none text-white"
                    ref="searchInput"
                    />
                </div>
                <div class="relative inline-block text-left">
                    <button ref="orderPopupButton" @click="isOpen = !isOpen" class="flex justify-center items-center border-[1.5px] border-opacity-30 border-white rounded-[0.4rem] h-[2.423rem] w-[2.423rem] focus:border-2" id="options-menu" aria-haspopup="true" :aria-expanded="isOpen.toString()">
                        <BasilSortSolid/>
                    </button>
                    <Transition>
                        <div 
                            v-if="isOpen"
                            ref="orderPopup"
                            class="origin-top-left md:origin-top-right absolute z-10 md:right-0 mt-2 w-[10rem] rounded-md shadow-lg bg-[--secondary-bg-color] ring-2 ring-black ring-opacity-5 focus:outline-none overflow-hidden" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                            <div class="flex flex-col items-start gap-[0.125rem]" role="none">
                                <button class="w-full px-4 py-2 text-[0.875rem] leading-[1rem] text-start text-white hover:bg-gray-500" role="menuitem" @click="sortPayments({type: 'desc', field: 'date'})">Date - Desc</button>
                                <button class="w-full px-4 py-2 text-[0.875rem] leading-[1rem] text-start text-white hover:bg-gray-500" role="menuitem" @click="sortPayments({type: 'asc', field: 'date'})">Date - Asc</button>
                                <button class="w-full px-4 py-2 text-[0.875rem] leading-[1rem] text-start text-white hover:bg-gray-500" role="menuitem" @click="sortPayments({type: 'desc', field: 'amount'})">Amount - Desc</button>
                                <button class="w-full px-4 py-2 text-[0.875rem] leading-[1rem] text-start text-white hover:bg-gray-500" role="menuitem" @click="sortPayments({type: 'asc', field: 'amount'})">Amount - Asc</button>
                            </div>
                        </div>
                    </Transition>
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
import BasilSortSolid from '~icons/basil/sort-solid';
import IcTwotoneSearch from '~icons/ic/twotone-search';

definePageMeta({
    middleware: ['auth']
})

// ----- Define Useful Properties ---------
const { $dayjs } = useNuxtApp();

// ----- Define Pinia Vars ----------
const indexStore = useIndexStore();
const { getTracker: tracker, isDataFetched } = storeToRefs(indexStore)

// ----- Define Vars ---------
const isLoading = ref(true);
const payments = ref([]);
const isExpanded = ref(false);
const isOpen = ref(false);
const paymentSearch = ref("");


// Refs
const orderPopup = ref(null);
const orderPopupButton = ref(null);
const searchInput = ref(null);

// If click outside orderPopup, we close the modal
onClickOutside(orderPopup, () => isOpen.value = false, {
    ignore: [orderPopupButton]
})

// Fetch necessary data to continue
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
function expandInput() {
  isExpanded.value = true;
  // Delay focus to ensure the input is visible before focusing
  setTimeout(() => {
    searchInput.value.focus();
  }, 100);
}

function collapseInput() {
  isExpanded.value = false;
}

function searchPayment() {
    payments.value = tracker.value.payments.filter(el => {
        const isInTitle = el.title.toLowerCase().includes(paymentSearch.value.toLowerCase());
        const isInDescription = el.description.toLowerCase().includes(paymentSearch.value.toLowerCase());
        const isInAmount = el.amount.toString().toLowerCase().includes(paymentSearch.value.toLowerCase());

        return isInTitle || isInDescription || isInAmount;
    })
}

function sortPayments(options) {
    payments.value = orderPayments(Object.assign([], tracker.value.payments), options);
    isOpen.value = false; // Close the popup
}


// ----- Define Watchers ---------
watch(tracker, (newValue) => {
    isLoading.value = true; // This let us reload the full list and avoid rendering problems
    const auxPayments = newValue.payments ? orderPayments(newValue.payments) : [];
    payments.value = Object.assign([], auxPayments)
    isLoading.value = false;
}, { deep: true })

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
