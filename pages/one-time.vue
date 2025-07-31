<template>
  <div class="one-time-page mb-8">
    <PaymentsDetails ref="paymentDetails" :paymentId="activePaymentId" :isRecurrent="false" @openEdit="showEdit" />
    <PaymentsManagePayment
      ref="editPayment"
      :paymentId="activePaymentId"
      :isEdit="true"
      :isRecurrent="false"
      @onCreated="fetchData"
    />
    <PaymentsNewPayment v-if="!isLoading" :isRecurrent="false" @onCreated="fetchData" />

    <!-- Loading State -->
    <Loader v-if="isLoading" />

    <!-- Content -->
    <div class="flex flex-col gap-4">
      <!-- Header & Summary -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-3">
        <!-- Month Navigation & Title -->
        <div class="flex items-center">
          <button @click="changeMonth(1)" class="btn btn-icon">
            <MdiChevronLeft />
          </button>
          <h2 class="text-xl font-semibold mx-2">{{ currentMonth }} {{ currentYear }}</h2>
          <button
            @click="changeMonth(-1)"
            class="btn btn-icon"
            :disabled="isCurrentMonth"
            :class="{ 'opacity-50 !cursor-not-allowed': isCurrentMonth }"
          >
            <MdiChevronRight />
          </button>
        </div>

        <!-- Summary Cards -->
        <div class="flex flex-col w-full sm:flex-row sm:w-[unset] sm:flex-wrap gap-3">
          <div class="bg-success bg-opacity-10 p-3 rounded-lg flex items-center">
            <MdiCashCheck class="text-success text-2xl mr-2" />
            <div>
              <p class="text-xs font-medium">Paid This Month</p>
              <p class="font-semibold">{{ formatPrice(monthTotals.paid) }}</p>
            </div>
          </div>

          <div class="bg-danger bg-opacity-10 p-3 rounded-lg flex items-center">
            <MdiCashRemove class="text-danger text-2xl mr-2" />
            <div>
              <p class="text-xs font-medium">Unpaid This Month</p>
              <p class="font-semibold">{{ formatPrice(monthTotals.unpaid) }}</p>
            </div>
          </div>

          <div class="bg-accent bg-opacity-10 p-3 rounded-lg flex items-center">
            <MdiCalendarMonth class="text-accent text-2xl mr-2" />
            <div>
              <p class="text-xs font-medium">Total This Month</p>
              <p class="font-semibold">{{ formatPrice(monthTotals.paid + monthTotals.unpaid) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <Filters @onSearch="searchPayments" @onOrder="orderPayments" />

      <!-- Payments List -->
      <div class="px-3">
        <div
          v-if="payments.length === 0"
          class="flex flex-col items-center justify-center py-10 text-center text-gray-500"
        >
          <MdiCashOff class="text-5xl mx-auto mb-3 opacity-30" />
          <p>No one-time payments found for this month</p>
        </div>

        <!-- Payment Cards -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="payment in payments"
            :key="payment.id"
            class="bg-base shadow-sm rounded-lg p-4 border border-gray-700 shadow hover:shadow-lg transition-shadow cursor-pointer"
            @click="showDetails(payment.id)"
          >
            <div class="flex items-center mb-3">
              <div class="flex-1">
                <h3 class="font-medium">{{ payment.title }}</h3>
                <div class="flex items-center mt-1 mb-1">
                  <span
                    class="text-xs px-2 py-0.5 rounded-full capitalize"
                    :class="getCategoryClasses(payment.category)"
                  >
                    {{ payment.category }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 line-clamp-1">{{ payment.description }}</p>
              </div>
              <div
                class="ml-2 h-8 w-8 rounded-full flex items-center justify-center"
                :class="
                  payment.isPaid
                    ? 'bg-success bg-opacity-10'
                    : isDelayed(payment.dueDate)
                    ? 'bg-danger bg-opacity-10'
                    : 'bg-gray-500 bg-opacity-10'
                "
              >
                <MdiCheck v-if="payment.isPaid" class="text-success text-xl" />
                <MdiClockOutline v-else-if="isDelayed(payment.createdAt)" class="text-danger text-xl" />
                <MdiCircleOutline v-else class="text-gray-400 text-xl" />
              </div>
            </div>

            <div class="flex justify-between items-center">
              <div>
                <p class="text-xs text-gray-500">Due on</p>
                <p class="text-sm">{{ formatDate(payment.dueDate || payment.createdAt) }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-gray-500">Amount</p>
                <p class="font-semibold">{{ formatPrice(payment.amount) }}</p>
              </div>
            </div>

            <div class="flex justify-between mt-4">
              <button
                @click.stop="togglePaymentStatus(payment.id, !payment.isPaid)"
                class="text-sm py-1 px-3 rounded-full"
                :class="payment.isPaid ? 'bg-warning/10 text-warning' : 'bg-success/10 text-success'"
              >
                {{ payment.isPaid ? "Mark as Unpaid" : "Mark as Paid" }}
              </button>
              <button @click.stop="showEdit(payment.id)" class="text-gray-500 hover:text-gray-700">
                <MdiPencil />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import MdiCheck from "~icons/mdi/check";
import MdiClockOutline from "~icons/mdi/clock-outline";
import MdiCircleOutline from "~icons/mdi/circle-outline";
import MdiPencil from "~icons/mdi/pencil";
import MdiCashCheck from "~icons/mdi/cash-check";
import MdiCashRemove from "~icons/mdi/cash-remove";
import MdiCashOff from "~icons/mdi/cash-off";
import MdiCalendarMonth from "~icons/mdi/calendar-month";
import MdiChevronLeft from "~icons/mdi/chevron-left";
import MdiChevronRight from "~icons/mdi/chevron-right";

definePageMeta({
  middleware: ["auth"]
});

// ----- Define Useful Properties ---------
const { $dayjs } = useNuxtApp();

// ----- Define Pinia Vars ----------
const paymentStore = usePaymentStore();
const { getPayments, isLoading: storeLoading } = storeToRefs(paymentStore);

// ----- Define Refs ---------
const isLoading = ref(true);
const activePaymentId = ref(null);
const editPayment = ref(null);
const paymentDetails = ref(null);
const payments = ref([]);
const monthsOffset = ref(0);
const currentSortOrder = ref({ name: "isPaid", order: "asc" });
const currentSearchQuery = ref("");

// ----- Define Computed ---------
const currentMonth = computed(() => {
  return $dayjs().subtract(monthsOffset.value, "month").format("MMMM");
});

const currentYear = computed(() => {
  return $dayjs().subtract(monthsOffset.value, "month").format("YYYY");
});

// Determine if we're looking at the current month
const isCurrentMonth = computed(() => monthsOffset.value === 0);

// Calculate totals for the current month
const monthTotals = computed(() => {
  const totals = { paid: 0, unpaid: 0 };

  payments.value.forEach((payment) => {
    if (payment.isPaid) {
      totals.paid += payment.amount;
    } else {
      totals.unpaid += payment.amount;
    }
  });

  return totals;
});

// ----- Define Methods ---------
// Format price to currency
function formatPrice(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2
  }).format(amount || 0);
}

// Format date
function formatDate(timestamp) {
  if (!timestamp) return "";
  return $dayjs(timestamp.toDate()).format("MMM D, YYYY");
}

// Check if a date is in the past
function isDelayed(timestamp) {
  if (!timestamp) return false;
  return $dayjs(timestamp.toDate()).isBefore($dayjs(), "day");
}

// Change month view
function changeMonth(delta) {
  monthsOffset.value += delta;
  fetchData();
}

// Fetch one-time payments for selected month
async function fetchData() {
  isLoading.value = true;

  try {
    // Get start and end of selected month
    const targetMonth = $dayjs().subtract(monthsOffset.value, "month");
    const startOfMonth = targetMonth.startOf("month").toDate();
    const endOfMonth = targetMonth.endOf("month").toDate();

    // Set up filters for one-time payments in selected month
    const filters = {
      startDate: startOfMonth,
      endDate: endOfMonth,
      paymentType: "one-time"
    };

    await paymentStore.fetchPayments(filters);
    payments.value = [...getPayments.value];

    // Apply current sort order
    if (currentSortOrder.value.name) {
      applySortOrder(currentSortOrder.value);
    }
  } catch (error) {
    console.error("Error fetching payments:", error);
    useToast("error", "Failed to load payments");
  } finally {
    isLoading.value = false;
  }
}

// Show payment details
function showDetails(paymentId) {
  activePaymentId.value = paymentId;
  paymentDetails.value?.showModal(paymentId);
}

// Show edit payment form
function showEdit(paymentId) {
  activePaymentId.value = paymentId;
  editPayment.value?.showModal(paymentId);
}

// Toggle payment status (paid/unpaid)
async function togglePaymentStatus(paymentId, isPaid) {
  isLoading.value = true;
  try {
    const result = await paymentStore.togglePaymentStatus(paymentId, isPaid);

    if (result) {
      useToast("success", `Payment marked as ${isPaid ? "paid" : "unpaid"}`);

      // Update local state
      const index = payments.value.findIndex((p) => p.id === paymentId);
      if (index !== -1) {
        payments.value[index].isPaid = isPaid;
        payments.value[index].paidDate = isPaid ? new Date() : null;
      }

      // Preserve current sort order after status change
      if (currentSortOrder.value.name) {
        applySortOrder(currentSortOrder.value);
      }
    } else {
      useToast("error", paymentStore.error || "Failed to update payment status");
    }
  } catch (error) {
    console.error("Error toggling payment:", error);
    useToast("error", "An unexpected error occurred");
  } finally {
    isLoading.value = false;
  }
}

// Search payments
function searchPayments(query) {
  currentSearchQuery.value = query;
  
  if (!query) {
    payments.value = [...getPayments.value];
    if (currentSortOrder.value.name) {
      applySortOrder(currentSortOrder.value);
    }
    return;
  }

  const searchTerm = query.toLowerCase();
  payments.value = getPayments.value.filter((payment) => {
    return (
      payment.title.toLowerCase().includes(searchTerm) ||
      (payment.description && payment.description.toLowerCase().includes(searchTerm)) ||
      payment.category.toLowerCase().includes(searchTerm) ||
      payment.amount.toString().includes(searchTerm)
    );
  });
  
  // Reapply current sort order after search
  if (currentSortOrder.value.name) {
    applySortOrder(currentSortOrder.value);
  }
}

// Order payments by various criteria
function orderPayments(orderCriteria) {
  if (!orderCriteria || !orderCriteria.name) {
    // Reset to default sort
    currentSortOrder.value = { name: "isPaid", order: "asc" };
    applySortOrder(currentSortOrder.value);
    return;
  }

  currentSortOrder.value = orderCriteria;
  applySortOrder(orderCriteria);
}

// Apply sort order with custom logic
function applySortOrder(orderCriteria) {
  const { name, order } = orderCriteria;
  const direction = order === "asc" ? 1 : -1;

  payments.value.sort((a, b) => {
    let comparison = 0;

    switch (name) {
      case "title":
        comparison = a.title.localeCompare(b.title);
        break;
      case "amount":
        comparison = a.amount - b.amount;
        break;
      case "date":
        const aDate = a.dueDate ? a.dueDate.toDate() : a.createdAt.toDate();
        const bDate = b.dueDate ? b.dueDate.toDate() : b.createdAt.toDate();
        comparison = aDate - bDate;
        break;
      case "isPaid":
      case "unpaid_first":
        // Sort by paid status first, then by due date
        if (a.isPaid !== b.isPaid) {
          comparison = a.isPaid ? 1 : -1; // Unpaid items first
        } else {
          const aDate = a.dueDate ? a.dueDate.toDate() : a.createdAt.toDate();
          const bDate = b.dueDate ? b.dueDate.toDate() : b.createdAt.toDate();
          comparison = aDate - bDate;
        }
        break;
      default:
        comparison = a.createdAt.toDate() - b.createdAt.toDate();
    }

    return comparison * direction;
  });
}

// ----- Initialize Data ---------
onMounted(async () => {
  await fetchData();
});

watch(getPayments, () => {
  payments.value = [...getPayments.value];
  // Reapply current sort order when data changes
  if (currentSortOrder.value.name) {
    applySortOrder(currentSortOrder.value);
  }
});

// ----- Define Hooks --------
useHead({
  title: "One-Time Payments - PayTrackr",
  meta: [
    {
      name: "description",
      content: "Track and manage your one-time payments and expenses"
    }
  ]
});
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.btn-icon {
  @apply p-2 rounded-full hover:bg-gray-100 transition-colors;
}
</style>
