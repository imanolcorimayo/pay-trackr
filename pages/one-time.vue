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
        <!-- Month Title -->
        <div class="flex items-center">
          <h2 class="text-xl font-semibold mx-2">{{ currentMonth }} {{ currentYear }}</h2>
        </div>

        <!-- Summary Cards -->
        <div class="flex flex-wrap gap-3">
          <div class="summary-card bg-success/10 p-3 rounded-lg flex items-center">
            <MdiCashCheck class="text-success text-2xl mr-2" />
            <div>
              <p class="text-xs font-medium">Paid This Month</p>
              <p class="font-semibold">{{ formatPrice(monthTotals.paid) }}</p>
            </div>
          </div>

          <div class="summary-card bg-danger/10 p-3 rounded-lg flex items-center">
            <MdiCashRemove class="text-danger text-2xl mr-2" />
            <div>
              <p class="text-xs font-medium">Unpaid This Month</p>
              <p class="font-semibold">{{ formatPrice(monthTotals.unpaid) }}</p>
            </div>
          </div>

          <div class="summary-card bg-info/10 p-3 rounded-lg flex items-center">
            <MdiCalendarMonth class="text-info text-2xl mr-2" />
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

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="payment in payments"
            :key="payment.id"
            class="bg-gray-800 shadow-sm rounded-lg p-4 border border-gray-100 hover:shadow-md transition-shadow cursor-pointer"
            @click="showDetails(payment.id)"
          >
            <div class="flex items-center mb-3">
              <div class="w-2 h-10 rounded-full mr-3" :class="`bg-${payment.category.toLowerCase()}`"></div>
              <div class="flex-1">
                <h3 class="font-medium">{{ payment.title }}</h3>
                <p class="text-xs text-gray-500 line-clamp-1">{{ payment.description }}</p>
              </div>
              <div
                class="ml-2 h-8 w-8 rounded-full flex items-center justify-center"
                :class="
                  payment.isPaid ? 'bg-success/10' : isDelayed(payment.createdAt) ? 'bg-danger/10' : 'bg-gray-100'
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
                <p class="text-sm">{{ formatDate(payment.createdAt) }}</p>
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

// ----- Define Computed ---------
const currentMonth = computed(() => {
  return $dayjs().format("MMMM");
});

const currentYear = computed(() => {
  return $dayjs().format("YYYY");
});

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

// Fetch one-time payments for current month
async function fetchData() {
  isLoading.value = true;

  try {
    // Get start and end of current month
    const startOfMonth = $dayjs().startOf("month").toDate();
    const endOfMonth = $dayjs().endOf("month").toDate();

    // Set up filters for one-time payments in current month
    const filters = {
      startDate: startOfMonth,
      endDate: endOfMonth,
      paymentType: "one-time"
    };

    await paymentStore.fetchPayments(filters);
    payments.value = [...getPayments.value];

    // Sort payments by due date and payment status
    orderPayments({ name: "date", order: "desc" });
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
  this.isLoading = true;
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

      // Re-sort payments
      orderPayments();
    } else {
      useToast("error", paymentStore.error || "Failed to update payment status");
    }
  } catch (error) {
    console.error("Error toggling payment:", error);
    useToast("error", "An unexpected error occurred");
  } finally {
    this.isLoading = false;
  }
}

// Search payments
function searchPayments(query) {
  if (!query) {
    payments.value = [...getPayments.value];
    orderPayments();
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
}

// Order payments by various criteria
function orderPayments(orderCriteria) {
  if (!orderCriteria) {
    // Default sort: unpaid first, then by due date
    payments.value.sort((a, b) => {
      // First sort by paid status
      if (a.isPaid !== b.isPaid) {
        return a.isPaid ? 1 : -1; // Unpaid items first
      }

      // Then sort by due date (descending)
      return b.createdAt.toDate() - a.createdAt.toDate();
    });
    return;
  }

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
        comparison = a.createdAt.toDate() - b.createdAt.toDate();
        break;
      case "isPaid":
        // Sort by paid status first, then by due date
        if (a.isPaid !== b.isPaid) {
          comparison = a.isPaid ? 1 : -1; // Unpaid items first
        } else {
          comparison = a.createdAt.toDate() - b.createdAt.toDate();
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
</style>
