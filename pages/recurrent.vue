<template>
  <div class="recurrent-page">
    <RecurrentsManagePayment ref="editPayment" :paymentId="activeRecurrentId" isEdit />
    <RecurrentsDetails ref="recurrentDetails" :paymentId="activeRecurrentId" @openEdit="showEdit" />
    <RecurrentsNewPayment v-if="!isLoading" @onCreated="fetchData" />

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center items-center min-h-[400px]">
      <Loader />
    </div>

    <!-- Content -->
    <div v-else class="flex flex-col gap-4">
      <!-- Header & Summary -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-3">
        <!-- Month Navigation & Title -->
        <div class="flex items-center">
          <button @click="changeMonthRange(3)" class="btn btn-icon">
            <MdiChevronLeft />
          </button>
          <h2 class="text-xl font-semibold mx-2">
            {{ months[0].key }} - {{ months[months.length - 1].key }} {{ currentYear }}
          </h2>
          <button
            @click="changeMonthRange(-3)"
            class="btn btn-icon"
            :disabled="isCurrentPeriod"
            :class="{ 'opacity-50 !cursor-not-allowed': isCurrentPeriod }"
          >
            <MdiChevronRight />
          </button>
        </div>

        <!-- Summary Cards -->
        <div class="flex flex-col w-full sm:flex-row sm:w-[unset] sm:flex-wrap gap-3">
          <div class="summary-card bg-success/10 p-3 rounded-lg flex items-center">
            <MdiCashCheck class="text-success text-2xl mr-2" />
            <div>
              <p class="text-xs font-medium">Paid This Month</p>
              <p class="font-semibold">{{ formatPrice(currentMonthTotals.paid) }}</p>
            </div>
          </div>

          <div class="summary-card bg-danger/10 p-3 rounded-lg flex items-center">
            <MdiCashRemove class="text-danger text-2xl mr-2" />
            <div>
              <p class="text-xs font-medium">Unpaid This Month</p>
              <p class="font-semibold">{{ formatPrice(currentMonthTotals.unpaid) }}</p>
            </div>
          </div>

          <div class="bg-accent bg-opacity-10 p-3 rounded-lg flex items-center">
            <MdiCalendarMonth class="text-accent text-2xl mr-2" />
            <div>
              <p class="text-xs font-medium">Total This Month</p>
              <p class="font-semibold">{{ formatPrice(currentMonthTotals.paid + currentMonthTotals.unpaid) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <Filters @onSearch="searchPayments" @onOrder="orderRecurrents" />

      <!-- Table View -->
      <div class="hidden md:block overflow-x-auto px-3">
        <table class="w-full table-fixed">
          <thead class="text-center">
            <tr class="border-b h-12">
              <th scope="col" class="text-start font-semibold">Payment</th>
              <th scope="col" class="w-20 font-semibold">Amount</th>
              <th scope="col" class="w-16 font-semibold">Day</th>
              <th v-for="month in months" :key="`${month.key}-${month.year}`" class="font-semibold">
                {{ month.display }}
                <span v-if="month.year !== currentYear" class="text-xs text-gray-500"
                  >'{{ month.year.substring(2) }}</span
                >
              </th>
              <th scope="col" class="w-16"></th>
            </tr>
          </thead>
          <tbody class="text-center">
            <tr
              v-for="payment in recurrents"
              :key="payment.id"
              class="border-b hover:bg-slate-500 cursor-pointer transition-colors"
              @click="showDetails(payment.id)"
            >
              <td class="text-start py-4">
                <div class="flex items-center">
                  <div
                    class="w-2 h-10 rounded-full mr-3 shrink-0 !bg-opacity-90"
                    :class="getCategoryClasses(payment.category.toLowerCase())"
                  ></div>
                  <div class="flex flex-col text-start">
                    <span class="font-medium">{{ payment.title }}</span>
                    <span class="text-xs text-gray-500">{{ payment.description }}</span>
                  </div>
                </div>
              </td>
              <td class="font-medium">{{ formatPrice(payment.amount) }}</td>
              <td>{{ payment.dueDateDay }}</td>

              <!-- Month cells -->
              <td v-for="month in months" :key="`${payment.id}-${month.key}-${month.year}`" class="py-3 relative">
                <div v-if="payment.months[month.key]" class="flex flex-col items-center justify-center">
                  <!-- Toggle Button -->
                  <button
                    @click.stop="togglePaymentStatus(payment.id, month.key)"
                    class="inline-flex items-center justify-center h-8 w-8 rounded-full transition-colors"
                    :class="[
                      payment.months[month.key].isPaid
                        ? 'bg-success/10'
                        : isDelayed(payment.months[month.key].dueDate)
                        ? 'bg-danger/10'
                        : 'bg-gray-100'
                    ]"
                  >
                    <MdiCheck v-if="payment.months[month.key].isPaid" class="text-success text-xl" />
                    <MdiClockOutline
                      v-else-if="isDelayed(payment.months[month.key].dueDate)"
                      class="text-danger text-xl"
                    />
                    <MdiCircleOutline v-else class="text-gray-400 text-xl" />
                  </button>

                  <!-- Amount -->
                  <span class="text-xs mt-1">{{ formatPrice(payment.months[month.key].amount) }}</span>
                </div>
                <button
                  v-else
                  @click.stop="createPaymentForMonth(payment.id, month.key)"
                  class="text-gray-400 hover:text-primary px-2 py-1 rounded transition-colors"
                >
                  <MdiPlusCircleOutline class="text-xl" />
                </button>
              </td>

              <!-- Actions -->
              <td>
                <div class="flex justify-center">
                  <button @click.stop="showEdit(payment.id)" class="btn btn-icon">
                    <MdiPencil class="text-gray-500" />
                  </button>
                </div>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="recurrents.length === 0">
              <td colspan="100%" class="py-10 text-center text-gray-500">
                <MdiCashOff class="text-5xl mx-auto mb-3 opacity-30" />
                <p>No recurrent payments found</p>
                <button @click="showNewPaymentModal" class="btn btn-primary mt-3">Add Payment</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Card View (Mobile) -->
      <div class="md:hidden px-3 space-y-4">
        <div
          v-for="payment in recurrents"
          :key="payment.id"
          class="bg-base rounded-lg border border-gray-700 overflow-hidden"
        >
          <!-- Payment Header -->
          <div class="p-4 border-b border-gray-700 flex items-center gap-3" @click="showDetails(payment.id)">
            <div
              class="w-2 h-10 rounded-full shrink-0 !bg-opacity-90"
              :class="getCategoryClasses(payment.category.toLowerCase())"
            ></div>

            <div class="flex-1 min-w-0">
              <h3 class="font-medium truncate">{{ payment.title }}</h3>
              <p class="text-xs text-gray-500 line-clamp-1">{{ payment.description }}</p>
            </div>

            <div class="text-right shrink-0">
              <span class="font-medium block">{{ formatPrice(payment.amount) }}</span>
              <span class="text-xs text-gray-500">Day: {{ payment.dueDateDay }}</span>
            </div>
          </div>

          <!-- Monthly Status -->
          <div class="p-4 grid grid-cols-3 gap-3">
            <div
              v-for="month in months.slice(-3)"
              :key="`${payment.id}-${month.key}-${month.year}`"
              class="flex flex-col items-center"
            >
              <span class="text-xs text-gray-500 mb-1">{{ month.display }}</span>

              <div v-if="payment.months[month.key]" class="flex flex-col items-center">
                <button
                  @click="togglePaymentStatus(payment.id, month.key)"
                  class="inline-flex items-center justify-center h-10 w-10 rounded-full transition-colors"
                  :class="[
                    payment.months[month.key].isPaid
                      ? 'bg-success/10'
                      : isDelayed(payment.months[month.key].dueDate)
                      ? 'bg-danger/10'
                      : 'bg-gray-100'
                  ]"
                >
                  <MdiCheck v-if="payment.months[month.key].isPaid" class="text-success text-xl" />
                  <MdiClockOutline
                    v-else-if="isDelayed(payment.months[month.key].dueDate)"
                    class="text-danger text-xl"
                  />
                  <MdiCircleOutline v-else class="text-gray-400 text-xl" />
                </button>

                <span class="text-xs mt-1">{{ formatPrice(payment.months[month.key].amount) }}</span>
              </div>

              <button
                v-else
                @click="createPaymentForMonth(payment.id, month.key)"
                class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-gray-100"
              >
                <MdiPlusCircleOutline class="text-gray-500 text-xl" />
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end p-2 border-t border-gray-700">
            <button @click="showEdit(payment.id)" class="p-2 text-gray-500 hover:text-primary">
              <MdiPencil />
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="recurrents.length === 0" class="py-10 text-center text-gray-500">
          <MdiCashOff class="text-5xl mx-auto mb-3 opacity-30" />
          <p>No recurrent payments found</p>
          <button @click="showNewPaymentModal" class="btn btn-primary mt-3">Add Payment</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import MdiChevronLeft from "~icons/mdi/chevron-left";
import MdiChevronRight from "~icons/mdi/chevron-right";
import MdiCheck from "~icons/mdi/check";
import MdiClockOutline from "~icons/mdi/clock-outline";
import MdiCircleOutline from "~icons/mdi/circle-outline";
import MdiPencil from "~icons/mdi/pencil";
import MdiPlusCircleOutline from "~icons/mdi/plus-circle-outline";
import MdiCashCheck from "~icons/mdi/cash-check";
import MdiCashRemove from "~icons/mdi/cash-remove";
import MdiCashOff from "~icons/mdi/cash-off";
import MdiCalendarMonth from "~icons/mdi/calendar-month";

definePageMeta({
  middleware: ["auth"]
});

// ----- Define Useful Properties ---------
const { $dayjs } = useNuxtApp();
const { width } = useWindowSize();

// ----- Define Pinia Vars ----------
const recurrentStore = useRecurrentStore();
const { getProcessedRecurrents, isDataLoaded, isLoading: storeLoading, getMonthlyTotals } = storeToRefs(recurrentStore);

// ----- Define Refs ---------
const isLoading = ref(true);
const activeRecurrentId = ref(null);
const editPayment = ref(null);
const recurrentDetails = ref(null);
const recurrents = ref([]);
const monthsOffset = ref(0);
const currentSortOrder = ref({ name: "unpaid_first", order: "asc" });
const currentSearchQuery = ref("");

// ----- Define Computed ---------
// Based on the screen width, determine how many months to show
const monthsToShow = computed(() => (width.value > 768 ? 6 : 3));

// Generate the array of months to display based on current offset
const months = computed(() => {
  const monthArray = [];
  for (let i = 0; i < monthsToShow.value; i++) {
    const date = $dayjs().subtract(i + monthsOffset.value, "month");
    monthArray.push({
      key: date.format("MMM"),
      display: date.format("MMM"),
      year: date.format("YYYY")
    });
  }
  return monthArray.reverse();
});

const currentMonthTotals = computed(() => {
  const totals = { paid: 0, unpaid: 0 };

  const monthlyTotals = getMonthlyTotals.value;
  // Use current month key (regardless of what period we're viewing)
  const currentMonthKey = $dayjs().format("MMM");

  if (monthlyTotals[currentMonthKey]) {
    totals.paid = monthlyTotals[currentMonthKey].paid;
    totals.unpaid = monthlyTotals[currentMonthKey].unpaid;
  }

  return totals;
});

// Current year for display
const currentYear = computed(() => {
  // For multi-year spans, show range
  const firstMonth = months.value[0];
  const lastMonth = months.value[months.value.length - 1];

  if (firstMonth.year === lastMonth.year) {
    return firstMonth.year;
  }
  return `${firstMonth.year} - ${lastMonth.year}`;
});
// Determine if we're looking at the current period
const isCurrentPeriod = computed(() => monthsOffset.value === 0);

// ----- Define Methods ---------
// Check if a date is in the past
function isDelayed(dueDate) {
  return $dayjs(dueDate, { format: "MM/DD/YYYY" }).isBefore($dayjs(), "day");
}

// Change the range of months displayed
function changeMonthRange(delta) {
  const newOffset = monthsOffset.value + delta;
  monthsOffset.value = newOffset;
  fetchData();
}

// Toggle payment status
async function togglePaymentStatus(recurrentId, month) {
  if (isLoading.value) return;
  isLoading.value = true;

  const payment = recurrents.value.find((p) => p.id === recurrentId);
  if (!payment || !payment.months[month]) {
    isLoading.value = false;
    return;
  }

  const paymentId = payment.months[month].paymentId;
  const currentStatus = payment.months[month].isPaid;

  if (!paymentId) {
    // Create payment
    await createPaymentForMonth(recurrentId, month, true);
    isLoading.value = false;
    return;
  }
  const result = await recurrentStore.togglePaymentStatus(paymentId, !currentStatus);

  isLoading.value = false;
  if (result) {
    useToast("success", `Payment marked as ${!currentStatus ? "paid" : "unpaid"}`);
    // Preserve current sort order after status change
    if (currentSortOrder.value.name) {
      applySortOrder(currentSortOrder.value);
    }
  } else {
    useToast("error", recurrentStore.error || "Failed to update payment status");
  }
}

// Create a payment instance for a month where one doesn't exist
async function createPaymentForMonth(recurrentId, month, isPaid = false) {
  console.log("SOME");

  const result = await recurrentStore.addNewPaymentInstance(recurrentId, month, isPaid);

  if (result) {
    useToast("success", "Payment instance created");
    // Preserve current sort order after creating payment
    if (currentSortOrder.value.name) {
      applySortOrder(currentSortOrder.value);
    }
  } else {
    useToast("error", recurrentStore.error || "Failed to create payment instance");
  }
}

// Show edit payment modal
function showEdit(recurrentId) {
  activeRecurrentId.value = recurrentId;

  console.log("Show edit for", recurrentId);
  console.log("editPayment", editPayment.value);
  editPayment.value.showModal(recurrentId);
}

// Show payment details modal
function showDetails(recurrentId) {
  activeRecurrentId.value = recurrentId;
  console.log("Show details for", recurrentId);
  console.log("recurrentDetails", recurrentDetails.value);
  recurrentDetails.value.showModal(recurrentId);
}

// Search payments
function searchPayments(query) {
  currentSearchQuery.value = query;
  recurrentStore.searchRecurrents(query);
  recurrents.value = [...getProcessedRecurrents.value];
  // Reapply current sort order after search
  if (currentSortOrder.value.name) {
    applySortOrder(currentSortOrder.value);
  }
}

// Order recurrents
function orderRecurrents(orderQuery) {
  if (!orderQuery || !orderQuery.name) {
    // Reset to default sort
    currentSortOrder.value = { name: "unpaid_first", order: "asc" };
    applySortOrder(currentSortOrder.value);
    return;
  }

  currentSortOrder.value = orderQuery;
  applySortOrder(orderQuery);
}

// Apply sort order with custom logic
function applySortOrder(orderQuery) {
  const { name, order } = orderQuery;
  const direction = order === "asc" ? 1 : -1;

  recurrents.value.sort((a, b) => {
    let comparison = 0;

    switch (name) {
      case "unpaid_first":
        // Default sort: unpaid first, then by due date ascending
        const aHasUnpaid = Object.values(a.months).some(month => !month.isPaid);
        const bHasUnpaid = Object.values(b.months).some(month => !month.isPaid);
        
        if (aHasUnpaid !== bHasUnpaid) {
          return aHasUnpaid ? -1 : 1; // Unpaid first
        }
        // If same paid status, sort by due date
        comparison = parseInt(a.dueDateDay) - parseInt(b.dueDateDay);
        break;
        
      case "title":
        comparison = a.title.localeCompare(b.title);
        break;
        
      case "amount":
        comparison = a.amount - b.amount;
        break;
        
      case "date":
        // Sort by due date day
        comparison = parseInt(a.dueDateDay) - parseInt(b.dueDateDay);
        break;
        
      default:
        comparison = parseInt(a.dueDateDay) - parseInt(b.dueDateDay);
    }

    return comparison * direction;
  });
}

// Show new payment modal
function showNewPaymentModal() {
  // Implement this based on your payment creation flow
  // e.g., navigating to a new payment page or showing a modal
}

// Format price/currency
function formatPrice(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2
  }).format(amount);
}

// Fetch all required data
async function fetchData() {
  isLoading.value = true;

  await recurrentStore.fetchRecurrentPayments();
  await recurrentStore.fetchPaymentInstances(monthsOffset.value + monthsToShow.value);

  recurrents.value = [...getProcessedRecurrents.value];
  isLoading.value = false;
}

// ----- Initial Data Load ---------
onMounted(async () => {
  await fetchData();

  // Apply default sorting - unpaid first
  orderRecurrents({ name: "unpaid_first", order: "asc" });
});

// ----- Watchers ---------
watch(getProcessedRecurrents, (newVal) => {
  recurrents.value = [...newVal];
  // Reapply current sort order when data changes
  if (currentSortOrder.value.name) {
    applySortOrder(currentSortOrder.value);
  }
});

// ----- Meta ---------
useHead({
  title: "Recurring Payments - PayTrackr",
  meta: [
    {
      name: "description",
      content: "Track and manage your recurring monthly expenses"
    }
  ]
});
</script>

<style scoped>
.btn-icon {
  @apply p-2 rounded-full hover:bg-gray-100 transition-colors;
}

.summary-card {
  @apply min-w-[150px];
}
</style>
