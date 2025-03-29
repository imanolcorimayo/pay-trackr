<template>
  <Modal ref="modal">
    <template #header>
      <h2 class="text-xl font-bold">{{ isEdit ? "Edit" : "Add" }} Recurring Payment</h2>
    </template>

    <template #body>
      <div v-if="isLoading" class="flex justify-center items-center min-h-[200px]">
        <Loader />
      </div>

      <form v-else @submit.prevent="savePayment" class="space-y-6">
        <!-- Payment Title & Description -->
        <div class="space-y-2">
          <label for="title" class="block text-sm font-medium text-gray-400">Payment Title</label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            required
            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="e.g. Netflix Subscription"
          />
        </div>

        <div class="space-y-2">
          <label for="description" class="block text-sm font-medium text-gray-400">Description (Optional)</label>
          <textarea
            id="description"
            v-model="form.description"
            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="Add some details about this payment"
            rows="2"
          ></textarea>
        </div>

        <!-- Amount & Category -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label for="amount" class="block text-sm font-medium text-gray-400">Amount</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
              <input
                id="amount"
                v-model="form.amount"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full p-2 pl-7 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="0.00"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label for="category" class="block text-sm font-medium text-gray-400">Category</label>
            <select
              id="category"
              v-model="form.category"
              required
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            >
              <option value="utilities">Utilities</option>
              <option value="food">Food</option>
              <option value="transport">Transport</option>
              <option value="entertainment">Entertainment</option>
              <option value="health">Health</option>
              <option value="pet">Pet</option>
              <option value="clothes">Clothes</option>
              <option value="traveling">Traveling</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <!-- Due Date & Period -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label for="dueDateDay" class="block text-sm font-medium text-gray-400">Due Date Day</label>
            <input
              id="dueDateDay"
              v-model="form.dueDateDay"
              type="number"
              min="1"
              max="31"
              required
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="Day of month (1-31)"
            />
            <p v-if="parseInt(form.dueDateDay) > 28" class="text-xs text-warning">
              Some months have fewer days. Payment may be due on the last day of those months.
            </p>
          </div>

          <div class="space-y-2">
            <label for="timePeriod" class="block text-sm font-medium text-gray-400">Payment Period</label>
            <select
              id="timePeriod"
              v-model="form.timePeriod"
              required
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            >
              <option value="monthly">Monthly</option>
              <option value="biweekly">Bi-weekly</option>
              <option value="weekly">Weekly</option>
              <option value="yearly">Yearly</option>
              <option value="quarterly">Quarterly</option>
            </select>
          </div>
        </div>

        <!-- Start & End Dates -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label for="startDate" class="block text-sm font-medium text-gray-400">Start Date</label>
            <input
              id="startDate"
              v-model="form.startDate"
              type="date"
              required
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>

          <div class="space-y-2">
            <label for="endDate" class="block text-sm font-medium text-gray-400">End Date (Optional)</label>
            <input
              id="endDate"
              v-model="form.endDate"
              type="date"
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
        </div>

        <!-- Submit button is in footer -->
      </form>
    </template>

    <template #footer>
      <div class="flex justify-between w-full">
        <button
          @click="closeModal"
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          Cancel
        </button>

        <button
          @click="savePayment"
          class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
          :disabled="isSubmitting"
        >
          <span v-if="isSubmitting">
            <span
              class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"
            ></span>
            Saving...
          </span>
          <span v-else>{{ isEdit ? "Update" : "Create" }} Payment</span>
        </button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useCurrentUser } from "vuefire";
import { serverTimestamp, Timestamp } from "firebase/firestore";

const props = defineProps({
  paymentId: {
    type: String,
    default: null
  },
  isEdit: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(["onClose"]);

// ----- Define Refs ---------
const modal = ref(null);
const isLoading = ref(false);
const isSubmitting = ref(false);

// Default form state
const defaultForm = {
  title: "",
  description: "",
  amount: "",
  startDate: "",
  dueDateDay: "",
  endDate: "",
  timePeriod: "monthly",
  category: "other",
  isCreditCard: false,
  creditCardId: null
};

const form = ref({ ...defaultForm });

// ----- Define Store ---------
const recurrentStore = useRecurrentStore();
const user = useCurrentUser();

// ----- Define Methods ---------
function showModal() {
  if (props.isEdit && props.paymentId) {
    fetchPaymentDetails(props.paymentId);
  } else {
    // Set default date to today
    const { $dayjs } = useNuxtApp();
    form.value.startDate = $dayjs().format("YYYY-MM-DD");
  }

  modal.value?.open();
}

function closeModal() {
  // Reset form when closing
  if (!props.isEdit) {
    form.value = { ...defaultForm };
  }
  modal.value?.close();
  emit("onClose");
}

async function fetchPaymentDetails(paymentId) {
  isLoading.value = true;

  // Get payment from store
  const payment = recurrentStore.getRecurrentPayments.find((p) => p.id === paymentId);

  if (payment) {
    // Format dates for form inputs
    const { $dayjs } = useNuxtApp();
    const startDate = payment.startDate ? $dayjs(payment.startDate, { format: "MM/DD/YYYY" }).format("YYYY-MM-DD") : "";

    const endDate = payment.endDate ? $dayjs(payment.endDate, { format: "MM/DD/YYYY" }).format("YYYY-MM-DD") : "";

    form.value = {
      title: payment.title || "",
      description: payment.description || "",
      amount: payment.amount || "",
      startDate: startDate,
      dueDateDay: payment.dueDateDay || "",
      endDate: endDate,
      timePeriod: payment.timePeriod || "monthly",
      category: payment.category || "other",
      isCreditCard: payment.isCreditCard || false,
      creditCardId: payment.creditCardId || null
    };
  }

  isLoading.value = false;
}

async function savePayment() {
  if (!user.value) {
    useToast("error", "You must be logged in to save payments");
    return;
  }

  isSubmitting.value = true;

  // Convert form data to payment object
  const paymentData = {
    title: form.value.title,
    description: form.value.description,
    amount: parseFloat(form.value.amount),
    startDate: form.value.startDate,
    dueDateDay: form.value.dueDateDay.toString(),
    endDate: form.value.endDate || null,
    timePeriod: form.value.timePeriod,
    category: form.value.category,
    isCreditCard: form.value.isCreditCard,
    creditCardId: form.value.creditCardId
  };

  try {
    let result;

    if (props.isEdit && props.paymentId) {
      // Update existing payment
      result = await recurrentStore.updateRecurrentPayment(props.paymentId, paymentData);

      if (result) {
        useToast("success", "Payment updated successfully");
        closeModal();
      } else {
        useToast("error", recurrentStore.error || "Failed to update payment");
      }
    } else {
      // Create new payment
      // This is a placeholder - you'll need to implement the createRecurrentPayment method in your store
      result = await recurrentStore.createRecurrentPayment({
        ...paymentData,
        userId: user.value.uid,
        createdAt: serverTimestamp()
      });

      if (result) {
        useToast("success", "Payment created successfully");
        closeModal();
      } else {
        useToast("error", recurrentStore.error || "Failed to create payment");
      }
    }
  } catch (error) {
    console.error("Error saving payment:", error);
    useToast("error", "An unexpected error occurred");
  } finally {
    isSubmitting.value = false;
  }
}

// ----- Define Watchers ---------
watch(
  () => props.paymentId,
  (newVal) => {
    if (newVal && props.isEdit && modal.value?.isOpen) {
      fetchPaymentDetails(newVal);
    }
  }
);

// Expose methods to parent
defineExpose({
  showModal,
  closeModal
});
</script>
