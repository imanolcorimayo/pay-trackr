<template>
  <div>
    <Modal ref="modal">
      <template #header>
        <div class="flex items-center">
          <div v-if="isLoading" class="w-3 h-14 rounded-full mr-3 bg-gray-200 animate-pulse"></div>
          <div
            v-else-if="form.category"
            class="w-3 h-14 rounded-full mr-3"
            :class="getCategoryClass(form.category)"
          ></div>
          <div>
            <h2 class="text-xl font-bold">
              {{ isEdit ? "Edit" : "Create" }} {{ isRecurrent ? "Recurring" : "One-time" }} Payment
            </h2>
            <p class="text-sm text-gray-500" v-if="isEdit && form.title">{{ form.title }}</p>
          </div>
        </div>
      </template>

      <template #body>
        <div v-if="isLoading" class="flex justify-center items-center min-h-[200px]">
          <Loader />
        </div>

        <form v-else @submit.prevent="savePayment" class="space-y-6">
          <!-- Payment Title & Description -->
          <div class="space-y-2">
            <label for="title" class="block text-sm font-medium text-gray-400">Payment Title*</label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              :disabled="props.isRecurrent"
              required
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="e.g. Netflix Subscription"
            />
          </div>

          <div class="space-y-2" v-if="!props.isRecurrent">
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
              <label for="amount" class="block text-sm font-medium text-gray-400">Amount*</label>
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

            <div class="space-y-2" v-if="!props.isRecurrent">
              <label for="category" class="block text-sm font-medium text-gray-400">Category*</label>
              <select
                id="category"
                v-model="form.category"
                required
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="housing">Housing & Rent</option>
                <option value="utilities">Utilities</option>
                <option value="food">Groceries</option>
                <option value="dining">Dining Out</option>
                <option value="transport">Transport</option>
                <option value="entertainment">Entertainment</option>
                <option value="health">Health</option>
                <option value="pet">Pet</option>
                <option value="clothes">Clothes</option>
                <option value="traveling">Traveling</option>
                <option value="education">Education</option>
                <option value="subscriptions">Subscriptions</option>
                <option value="taxes">Taxes & Government</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <!-- One-time Payment Fields -->
          <div class="space-y-2" v-if="!props.isRecurrent">
            <label for="dueDate" class="block text-sm font-medium text-gray-400">Due Date*</label>
            <input
              id="dueDate"
              v-model="form.dueDate"
              type="date"
              required
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>

          <div class="space-y-2" v-if="!props.isRecurrent">
            <label class="flex items-center space-x-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="form.isPaid"
                class="form-checkbox h-5 w-5 text-primary rounded focus:ring-primary"
              />
              <span class="text-sm font-medium text-gray-400">Mark as paid</span>
            </label>

            <div v-if="form.isPaid" class="mt-2">
              <label for="paidDate" class="block text-sm font-medium text-gray-400">Date Paid</label>
              <input
                id="paidDate"
                v-model="form.paidDate"
                type="date"
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>
          </div>
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

          <div class="flex space-x-2">
            <button
              v-if="isEdit"
              @click="confirmDelete"
              class="px-4 py-2 bg-danger/10 text-danger rounded-lg hover:bg-danger/20 transition-colors"
            >
              Delete
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
              <span v-else>{{ isEdit ? "Update" : "Create" }}</span>
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <ConfirmDialogue
      ref="confirmDialog"
      title="Delete Payment"
      :message="`Are you sure you want to delete ${form.title}?`"
      confirmLabel="Delete"
      @confirm="deletePayment"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
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
  },
  isRecurrent: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(["onClose", "onCreated"]);

// ----- Define Refs ---------
const modal = ref(null);
const confirmDialog = ref(null);
const isLoading = ref(false);
const isSubmitting = ref(false);

// Default form state
const defaultForm = computed(() => {
  const { $dayjs } = useNuxtApp();
  const today = $dayjs().format("YYYY-MM-DD");

  if (props.isRecurrent) {
    return {
      title: "",
      amount: ""
    };
  } else {
    return {
      title: "",
      description: "",
      amount: "",
      category: "other",
      dueDate: today,
      isPaid: true, // It's usually paid when creating a new payment
      paidDate: today,
      paymentType: "one-time"
    };
  }
});

const form = ref({ ...defaultForm.value });

// ----- Define Stores ---------
const recurrentStore = useRecurrentStore();
const paymentStore = usePaymentStore();
const user = useCurrentUser();

// ----- Define Methods ---------
function showModal(paymentId = null) {
  if (paymentId) {
    fetchPaymentDetails(paymentId);
  } else {
    // Reset form when creating new payment
    form.value = { ...defaultForm.value };
  }

  modal.value?.open();
}

function closeModal() {
  modal.value?.close();
  emit("onClose");
}

// In the fetchPaymentDetails function
async function fetchPaymentDetails(paymentId) {
  isLoading.value = true;

  try {
    if (props.isRecurrent) {
      // Get recurrent payment
      const payment = recurrentStore.getPaymentInstances.find((p) => p.id === paymentId);

      if (payment) {
        form.value = {
          title: payment.title || "",
          amount: payment.amount || ""
        };
      }
    } else {
      // Get regular payment
      const payment = await paymentStore.getPaymentById(paymentId);

      if (payment) {
        const { $dayjs } = useNuxtApp();

        // Use dueDate if available, otherwise fall back to createdAt
        const dueDate = payment.dueDate
          ? $dayjs(payment.dueDate.toDate()).format("YYYY-MM-DD")
          : payment.createdAt
          ? $dayjs(payment.createdAt.toDate()).format("YYYY-MM-DD")
          : "";

        const paidDate = payment.paidDate
          ? $dayjs(payment.paidDate.toDate()).format("YYYY-MM-DD")
          : $dayjs().format("YYYY-MM-DD");

        form.value = {
          title: payment.title || "",
          description: payment.description || "",
          amount: payment.amount || "",
          category: payment.category || "other",
          dueDate: dueDate,
          isPaid: payment.isPaid || true,
          paidDate: paidDate,
          paymentType: payment.paymentType || "one-time"
        };
      }
    }
  } catch (error) {
    console.error("Error fetching payment details:", error);
    useToast("error", "Failed to load payment details");
  } finally {
    isLoading.value = false;
  }
}

function getCategoryClass(category) {
  switch(category.toLowerCase()) {
    case 'housing':
      return 'bg-[#4682B4]'; // steel blue
    case 'utilities':
      return 'bg-[#0072DF]'; // accent blue
    case 'food':
      return 'bg-[#1D9A38]'; // success green 
    case 'dining':
      return 'bg-[#FF6347]'; // tomato red
    case 'transport':
      return 'bg-[#E6AE2C]'; // warning yellow
    case 'entertainment':
      return 'bg-[#6158FF]'; // secondary purple
    case 'health':
      return 'bg-[#E84A8A]'; // danger pink
    case 'pet':
      return 'bg-[#3CAEA3]'; // teal for pets
    case 'clothes':
      return 'bg-[#800020]'; // burgundy
    case 'traveling':
      return 'bg-[#FF8C00]'; // dark orange
    case 'education':
      return 'bg-[#9370DB]'; // medium purple
    case 'subscriptions':
      return 'bg-[#20B2AA]'; // light sea green
    case 'taxes':
      return 'bg-[#8B4513]'; // brown
    case 'other':
    default:
      return 'bg-[#808080]'; // gray for other/default
  }
}

async function savePayment() {
  if (!user.value) {
    useToast("error", "You must be logged in to save payments");
    return;
  }

  if (isSubmitting.value) return;
  isSubmitting.value = true;

  try {
    let result;

    // Handle one-time payment save/update
    const { $dayjs } = useNuxtApp();

    let paymentData = {
      title: form.value.title,
      description: form.value.description,
      amount: parseFloat(form.value.amount),
      category: form.value.category,
      isPaid: form.value.isPaid,
      paidDate: form.value.isPaid ? Timestamp.fromDate($dayjs(form.value.paidDate).toDate()) : null,
      dueDate: Timestamp.fromDate($dayjs(form.value.dueDate).toDate()), // Set dueDate from form
      recurrentId: null,
      paymentType: "one-time"
    };

    if (props.isRecurrent) {
      paymentData = {
        title: form.value.title,
        amount: parseFloat(form.value.amount)
      };
    }

    if (props.isRecurrent && !props.isEdit) {
      useToast("error", "Recurrent payments cannot be created from this form");
      isSubmitting.value = false;
      return;
    }

    if (props.isEdit && props.paymentId) {
      // Update existing payment
      result = await paymentStore.updatePayment(props.paymentId, paymentData);

      if (result) {
        useToast("success", "Payment updated successfully");
        emit("onCreated");
        closeModal();
      } else {
        useToast("error", paymentStore.error || "Failed to update payment");
      }
    } else {
      // Create new payment
      result = await paymentStore.createPayment({
        ...paymentData,
        userId: user.value.uid
      });

      if (result && result.success) {
        useToast("success", "Payment created successfully");
        emit("onCreated");
        closeModal();
      } else {
        useToast("error", paymentStore.error || "Failed to create payment");
      }
    }
  } catch (error) {
    console.error("Error saving payment:", error);
    useToast("error", "An unexpected error occurred");
  } finally {
    isSubmitting.value = false;
  }
}

function confirmDelete() {
  console.log("confirmDelete", confirmDialog.value);
  confirmDialog.value.open();
}

async function deletePayment() {
  isSubmitting.value = true;

  try {
    const result = await paymentStore.deletePayment(props.paymentId);

    if (result) {
      useToast("success", "Payment deleted successfully");
      emit("onCreated");
      closeModal();
    } else {
      useToast("error", paymentStore.error || "Failed to delete payment");
    }
  } catch (error) {
    console.error("Error deleting payment:", error);
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

<style scoped>
.form-checkbox {
  @apply text-primary border-gray-300 rounded;
}
</style>
