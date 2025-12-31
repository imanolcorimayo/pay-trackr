<template>
    <Modal ref="modal">
      <template #header>
        <div class="flex items-center">
          <div v-if="isLoading" class="w-3 h-14 rounded-full mr-3 bg-gray-200 animate-pulse"></div>
          <div v-else-if="payment" class="w-3 h-14 rounded-full mr-3" :class="`bg-${payment.category.toLowerCase()}`"></div>
          <div>
            <h2 class="text-xl font-bold">
              {{ payment ? payment.title : 'Payment Details' }}
              <span v-if="isRecurrent" class="text-sm font-normal text-primary ml-2">Recurring</span>
            </h2>
            <p class="text-sm text-gray-500">{{ payment?.description }}</p>
          </div>
        </div>
      </template>
  
      <template #body>
        <div v-if="isLoading" class="flex justify-center items-center min-h-[200px]">
          <Loader />
        </div>
        
        <div v-else-if="payment" class="space-y-6">
          <!-- Payment Basic Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col">
              <span class="text-sm text-gray-500">Amount</span>
              <span class="text-lg font-semibold">{{ formatPrice(payment.amount) }}</span>
            </div>
            
            <div class="flex flex-col">
              <span class="text-sm text-gray-500">Category</span>
              <span class="text-lg capitalize">{{ payment.category }}</span>
            </div>
            
            <template v-if="isRecurrent">
              <div class="flex flex-col">
                <span class="text-sm text-gray-500">Payment Day</span>
                <span class="text-lg">{{ payment.dueDateDay }}</span>
              </div>
              
              <div class="flex flex-col">
                <span class="text-sm text-gray-500">Frequency</span>
                <span class="text-lg capitalize">{{ payment.timePeriod || 'Monthly' }}</span>
              </div>
              
              <div class="flex flex-col">
                <span class="text-sm text-gray-500">Start Date</span>
                <span class="text-lg">{{ formatDateString(payment.startDate) }}</span>
              </div>
              
              <div class="flex flex-col">
                <span class="text-sm text-gray-500">End Date</span>
                <span class="text-lg">{{ payment.endDate ? formatDateString(payment.endDate) : 'Ongoing' }}</span>
              </div>
            </template>
            
            <template v-else>
              <div class="flex flex-col">
                <span class="text-sm text-gray-500">Due Date</span>
                <span class="text-lg">{{ formatDate(payment.createdAt) }}</span>
              </div>
              
              <div class="flex flex-col">
                <span class="text-sm text-gray-500">Status</span>
                <span class="text-lg">
                  <span 
                    class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="payment.isPaid ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                  >
                    {{ payment.isPaid ? 'Paid' : 'Unpaid' }}
                  </span>
                  <span v-if="payment.isPaid && payment.paidDate" class="text-xs ml-2">
                    on {{ formatDate(payment.paidDate) }}
                  </span>
                </span>
              </div>
            </template>
          </div>
          
          <!-- Recurring Payment History -->
          <div v-if="isRecurrent && paymentInstances.length > 0">
            <h3 class="text-lg font-semibold mb-3">Payment History</h3>
            <div class="overflow-y-auto max-h-[300px]">
              <table class="w-full">
                <thead class="text-left bg-gray-100">
                  <tr>
                    <th class="py-2 px-3 rounded-tl-lg">Date</th>
                    <th class="py-2 px-3">Amount</th>
                    <th class="py-2 px-3">Status</th>
                    <th class="py-2 px-3 rounded-tr-lg">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(instance, index) in paymentInstances" :key="index" class="border-b">
                    <td class="py-2 px-3">{{ formatDate(instance.createdAt) }}</td>
                    <td class="py-2 px-3">{{ formatPrice(instance.amount) }}</td>
                    <td class="py-2 px-3">
                      <span 
                        class="px-2 py-1 rounded-full text-xs font-medium"
                        :class="instance.isPaid ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                      >
                        {{ instance.isPaid ? 'Paid' : 'Unpaid' }}
                      </span>
                    </td>
                    <td class="py-2 px-3">
                      <button 
                        @click="togglePaymentStatus(instance.id, !instance.isPaid)"
                        class="text-primary hover:text-primary-dark text-sm font-medium"
                      >
                        {{ instance.isPaid ? 'Mark as Unpaid' : 'Mark as Paid' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Actions for One-time Payments -->
          <div v-if="!isRecurrent" class="flex flex-col gap-2">
            <h3 class="text-lg font-semibold mb-2">Actions</h3>
            <button 
              @click="togglePaymentStatus(payment.id, !payment.isPaid)"
              class="w-full py-2 flex items-center justify-center rounded-lg transition-colors"
              :class="payment.isPaid ? 'bg-warning/10 text-warning' : 'bg-success/10 text-success'"
            >
              <MdiCheck v-if="!payment.isPaid" class="mr-2" />
              <MdiUndo v-else class="mr-2" />
              {{ payment.isPaid ? 'Mark as Unpaid' : 'Mark as Paid' }}
            </button>
          </div>
        </div>
        
        <div v-else class="text-center py-6 text-gray-500">
          <p>Payment not found</p>
        </div>
      </template>
      
      <template #footer>
        <div class="flex justify-between w-full">
          <button 
            @click="closeModal"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Close
          </button>
          
          <div class="flex space-x-2">
            <button 
              @click="confirmDelete"
              class="px-4 py-2 bg-danger/10 text-danger rounded-lg hover:bg-danger/20 transition-colors"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="flex items-center">
                <span class="inline-block w-4 h-4 border-2 border-danger border-t-transparent rounded-full animate-spin mr-2"></span>
                Deleting...
              </span>
              <span v-else>Delete</span>
            </button>
            
            <button 
              @click="editPayment"
              class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
            >
              Edit
            </button>
          </div>
        </div>
      </template>
    </Modal>
    
    <ConfirmDialogue
      ref="confirmDialog"
      :message="`Are you sure you want to delete ${payment?.title || 'this payment'}?${deletionWarning}`"
      textConfirmButton="Delete"
      @confirm="deletePayment"
    />
  </template>
  
  <script setup>
  import { ref, computed, watch } from 'vue';
  import MdiCheck from '~icons/mdi/check';
  import MdiUndo from '~icons/mdi/undo';
  
  const props = defineProps({
    paymentId: {
      type: String,
      default: null
    },
    isRecurrent: {
      type: Boolean,
      default: false
    }
  });
  
  const emit = defineEmits(['openEdit']);
  
  // ----- Define Refs ---------
  const modal = ref(null);
  const confirmDialog = ref(null);
  const isLoading = ref(false);
  const isSubmitting = ref(false);
  const payment = ref(null);
  const paymentInstances = ref([]);
  
  // Deletion warning message
  const deletionWarning = computed(() => {
    if (props.isRecurrent) {
      return " This will also delete all payment instances for this recurring payment.";
    }
    return "";
  });
  
  // ----- Define Stores ---------
  const recurrentStore = useRecurrentStore();
  const paymentStore = usePaymentStore();
  
  // ----- Define Methods ---------
  function showModal(paymentId) {
    if (paymentId) {
      fetchPaymentDetails(paymentId);
    }
    modal.value?.open();
  }
  
  function closeModal() {
    modal.value?.close();
  }
  
  async function fetchPaymentDetails(paymentId) {
    isLoading.value = true;
    
    try {
      if (props.isRecurrent) {
        // Get recurrent payment
        payment.value = recurrentStore.getRecurrentPayments.find(p => p.id === paymentId);
        
        // Get payment instances
        paymentInstances.value = recurrentStore.getPaymentInstances
          .filter(p => p.recurrentId === paymentId)
          .sort((a, b) => b.createdAt.toDate() - a.createdAt.toDate()); // Most recent first
      } else {
        // Get regular payment
        payment.value = await paymentStore.getPaymentById(paymentId);
        paymentInstances.value = [];
      }
    } catch (error) {
      console.error("Error fetching payment details:", error);
      useToast("error", "Failed to load payment details");
    } finally {
      isLoading.value = false;
    }
  }
  
  function formatPrice(amount) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2
    }).format(amount || 0);
  }
  
  function formatDate(timestamp) {
    if (!timestamp) return 'N/A';
    const { $dayjs } = useNuxtApp();
    return $dayjs(timestamp.toDate ? timestamp.toDate() : timestamp).format('MMM D, YYYY');
  }
  
  function formatDateString(dateString) {
    if (!dateString) return 'N/A';
    const { $dayjs } = useNuxtApp();
    return $dayjs(dateString, { format: 'MM/DD/YYYY' }).format('MMM D, YYYY');
  }
  
  async function togglePaymentStatus(paymentId, isPaid) {
    if (!paymentId) return;
    
    try {
      let result;
      
      if (props.isRecurrent) {
        result = await recurrentStore.togglePaymentStatus(paymentId, isPaid);
        
        if (result) {
          // Update local state for recurrent payment instances
          const instanceIndex = paymentInstances.value.findIndex(p => p.id === paymentId);
          if (instanceIndex !== -1) {
            paymentInstances.value[instanceIndex].isPaid = isPaid;
          }
        }
      } else {
        result = await paymentStore.togglePaymentStatus(paymentId, isPaid);
        
        if (result && payment.value) {
          // Update local state for one-time payment
          payment.value.isPaid = isPaid;
          payment.value.paidDate = isPaid ? new Date() : null;
        }
      }
      
      if (result) {
        useToast("success", `Payment marked as ${isPaid ? 'paid' : 'unpaid'}`);
      } else {
        const store = props.isRecurrent ? recurrentStore : paymentStore;
        useToast("error", store.error || "Failed to update payment status");
      }
    } catch (error) {
      console.error("Error toggling payment status:", error);
      useToast("error", "An unexpected error occurred");
    }
  }
  
  function editPayment() {
    emit('openEdit', props.paymentId);
    closeModal();
  }
  
  function confirmDelete() {
    confirmDialog.value.open();
  }
  
  async function deletePayment() {
    if (!payment.value) return;
    isSubmitting.value = true;
    
    try {
      let result;
      
      if (props.isRecurrent) {
        result = await recurrentStore.deleteRecurrentPayment(payment.value.id);
      } else {
        result = await paymentStore.deletePayment(payment.value.id);
      }
      
      if (result) {
        useToast("success", "Payment deleted successfully");
        closeModal();
      } else {
        const store = props.isRecurrent ? recurrentStore : paymentStore;
        useToast("error", store.error || "Failed to delete payment");
      }
    } catch (error) {
      console.error("Error deleting payment:", error);
      useToast("error", "An unexpected error occurred");
    } finally {
      isSubmitting.value = false;
    }
  }
  
  // ----- Define Watchers ---------
  watch(() => props.paymentId, (newVal) => {
    if (newVal && modal.value?.isOpen) {
      fetchPaymentDetails(newVal);
    }
  });
  
  // Expose methods to parent
  defineExpose({
    showModal,
    closeModal
  });
  </script>