
<template>
  <div>
      <PaymentsManagePayment ref="editPayment" :paymentId="paymentId" isTrackerOnly isEdit />
      <div class="flex flex-col gap-[0.429rem]">
        <Filters @onSearch="searchPayment"/>
        <div class="p-3 px-0 sm:px-3" v-if="!isLoading">
          <table class="w-full table-fixed">
            <thead class="text-center">
              <tr class="border-b h-[4.571rem]">
                <th scope="col" class="text-start">Payment</th>
                <th scope="col" class="w-[3.714rem]">Next Due</th>
                <th v-for="monthName in months" :key="monthName">{{ monthName }}</th>
              </tr>
            </thead>
            <tbody class="text-center">
              <tr 
                v-for="(payment, paymentId) in searchedPayments" 
                :key="paymentId" 
                class="border-b h-[4.571rem] drop-shadow-md hover:cursor-pointer min-h-[5rem]"
                @click="showEdit(paymentId)"
              >
                <td class="text-start font-semibold">{{ payment.title }}</td>
                <td class="font-semibold">{{ $dayjs(payment.dueDate, { format: 'MM/DD/YYYY' }).format('MMM DD') }}</td>
                <th v-for="(month, monthIndex) in months" :key="`${monthIndex}-${month}`">

                  <span v-if="!payment.months[month]">

                    <div class="flex flex-col items-center justify-center">
                      <PepiconsPopDollarOff class="text-[1.143rem] m-auto"/>
                      <span class="text-[0.714rem] sm:text-[1rem] font-semibold">N/A</span>
                    </div>
                  </span>
                  <div v-else class="flex flex-col items-center justify-center">
                    <RiToggleFill @click="(ev) => markAsPaid(ev, paymentId, payment.months[month].trackerId, false)" class="text-[1.637rem] text-[--success-color]" v-if="payment.months[month].isPaid"/>
                    <RiToggleLine 
                      @click="(ev) => markAsPaid(ev, paymentId, payment.months[month].trackerId, true)" 
                      class="text-[1.637rem]" 
                      v-else
                      :class="{
                        ['text-[--danger-color]']: isDelayed(payment.months[month].dueDate) && !payment.months[month].isPaid
                      }"
                    />
                    <span class="text-[0.714rem] sm:text-[1rem] font-semibold">{{ formatPrice(payment.months[month].amount) }}</span>
                  </div>
                </th>
              </tr>
            </tbody>
          </table>
        </div>
        <h4 v-if="payments.length === 0 && !isLoading">Empty list.</h4>
      </div>
  </div>
</template>

<script setup>
import RiToggleFill from '~icons/ri/toggle-fill';
import RiToggleLine from '~icons/ri/toggle-line';
import PepiconsPopDollarOff from '~icons/pepicons-pop/dollar-off';

definePageMeta({
  middleware: ['auth']
})

// ----- Define Useful Properties ---------
const { $dayjs } = useNuxtApp();
const { width } = useWindowSize();

// ----- Define Pinia Vars ----------
const indexStore = useIndexStore();
const { getHistory: history, getTracker: tracker, isDataFetched } = storeToRefs(indexStore)

// ----- Define Vars ---------
const isLoading = ref(false);
const markingPaid = ref(false);
const payments = ref({});
const isOpen = ref(false);
const searchedPayments = ref([]);

// Based on the screen width select 3 months or 6 months
const nMonths = width.value > 768 ? 6 : 3;

// Based on today, create an array of the current and the past two months
// Like this const months = ref(["May", "Jun", "Jul"])
const months = ref(Array.from({ length: nMonths }, (_, i) => $dayjs().subtract(i, 'month').format('MMM')));

// Invert month's array to show the most recent month first
months.value = months.value.reverse();

// Fetch necessary data to continue
if(!isDataFetched.value) {
  await indexStore.fetchData();
  await indexStore.loadHistory();
}

// ----- Define Vars -------
const paymentId = ref(false)
// Refs
const editPayment = ref(null);

// ----- Define Methods ---------
function isDelayed(dueDate) {
  // Check if its delayed
  const dueDateObject = $dayjs(dueDate, { format: 'MM/DD/YYYY' });
  return dueDateObject.isBefore($dayjs(), 'day');
}
function showEdit(payId) {
  // Save id that will passed to the edit modal component
  paymentId.value = payId; 

  // Open the modal
  editPayment.value.showModal(payId);
}

function searchPayment(query) {

  // Filter payments based on the query
  searchedPayments.value = Object.keys(payments.value).reduce((acc, key) => {

    console.log(payments.value[key]);

    const isInTitle = payments.value[key].title.toLowerCase().includes(query.toLowerCase());
    const isInAmount = payments.value[key].amount.toString().toLowerCase().includes(query.toLowerCase());

    if(isInTitle || isInAmount) {
      acc[key] = payments.value[key];
    }
    return acc;
  }, {})

  searchedPayments.value = payments.value.filter(el => {

      return isInTitle || isInAmount;
  })
}

async function markAsPaid(ev, paymentId, trackerId, value) {
  // To avoid opening modal of details
  ev.stopPropagation();
  
  // If already sending, return
  if(isLoading.value) {
      return;
  }

  console.log(paymentId, trackerId, value);

  // Block add button and show loader
  isLoading.value = true;

  // Edit only in history
  const result = await indexStore.editIsPaidInHistory(paymentId, trackerId, value);


  if (!result || typeof result == "string") {
      useToast('error', typeof result == "string" ? result : "Something went wrong, please try again")
      // Un-Block add button
      isLoading.value = false;
      return;
  }

  // Reset payment object
  // populatePayments(history.value, tracker.value);

  isLoading.value = false;
  useToast('success', 'Payment mark as paid successfully.')
}

function sortPayments(options) {
  payments.value = orderPayments(Object.assign([], history.value.payments), options);
  isOpen.value = false; // Close the popup
}

function populatePayments(history, tracker) {

  console.log("JSON.stringify(history): ", JSON.stringify(history));
  console.log("JSON.stringify(tracker): ", JSON.stringify(tracker))

  // Create object to be used in the table
  history.forEach(el => {
    el.payments.forEach(pay => {

      // Avoid one time payments
      if(pay.payment_id.length === 36) {
        return;
      }

      // Check if payment has already been processed
      if(!payments.value[pay.payment_id]) {
        // Get current payment information
        const currentPay = tracker.payments.filter(payInTracker => payInTracker.payment_id === pay.payment_id)[0];
        if(currentPay) {
          // Payment title && due date
          payments.value[pay.payment_id] = {
            title: currentPay.title,
            dueDate: currentPay.dueDate,
            amount: pay.amount, // This is the current amount
            months: {}
          }
        } else {
          // Payment title && due date for one time payments or deleted payments
          payments.value[pay.payment_id] = {
            title: pay.title,
            dueDate: pay.dueDate,
            amount: pay.amount, // This is the current amount
            months: {}
          }
        }

      }

      const payMonth = $dayjs(pay.dueDate, { format: 'MM/DD/YYYY' }).format('MMM');
      // Create payment amount and is paid object for each month
      payments.value[pay.payment_id].months[payMonth] = {
        amount: pay.amount,
        dueDate: pay.dueDate,
        trackerId: el.id,
        isPaid: pay.isPaid
      }
    })
  })

  // Duplicate value in aux variable so we can filter it
  searchedPayments.value = payments.value;
}

// ----- Define Hooks ---------
onMounted(() => {
  populatePayments(history.value, tracker.value);
})


// ----- Define Watchers ---------
watch([history, tracker], (newValue) => {
  populatePayments(newValue[0], newValue[1]);
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
