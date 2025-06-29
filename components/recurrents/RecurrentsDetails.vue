<template>
  <Modal ref="modal">
    <template #header>
      <div class="flex items-center">
        <div v-if="payment" class="w-3 h-14 rounded-full mr-3" :class="`bg-${payment.category.toLowerCase()}`"></div>
        <div>
          <h2 class="text-xl font-bold">{{ payment ? payment.title : 'Payment Details' }}</h2>
          <span class="text-xs text-gray-500">{{ payment?.id }}</span> 
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
          
          <div class="flex flex-col">
            <span class="text-sm text-gray-500">Payment Day</span>
            <span class="text-lg">{{ payment.dueDateDay }}</span>
          </div>
          
          <div class="flex flex-col">
            <span class="text-sm text-gray-500">Schedule</span>
            <span class="text-lg capitalize">{{ payment.timePeriod || 'Monthly' }}</span>
          </div>
        </div>
        
        <!-- Payment History -->
        <div>
          <h3 class="text-lg font-semibold mb-3">Recent Payment History</h3>
          <div class="overflow-y-auto max-h-[300px]">
            <table class="w-full">
              <thead class="text-left bg-gray-100">
                <tr>
                  <th class="py-2 px-3 rounded-tl-lg">Month</th>
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
                    <div class="flex space-x-2">
                      <button 
                        @click="togglePaymentStatus(instance.id, !instance.isPaid)"
                        class="text-primary hover:text-primary-dark text-sm font-medium"
                      >
                        {{ instance.isPaid ? 'Mark as Unpaid' : 'Mark as Paid' }}
                      </button>
                      <button
                        @click="editPaymentInstance(instance.id)"
                        class="text-gray-500 hover:text-gray-700 text-sm font-medium ml-2"
                      >
                        Edit
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="paymentInstances.length === 0">
                  <td colspan="4" class="py-4 text-center text-gray-500">No payment history found</td>
                </tr>
              </tbody>
            </table>
          </div>
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
            @click="deletePayment"
            class="px-4 py-2 bg-danger/10 text-danger rounded-lg hover:bg-danger/20 transition-colors"
          >
            Delete
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
  
  <!-- Instance editor modal -->
  <PaymentsManagePayment
    ref="instanceEditor"
    :paymentId="activeInstanceId"
    :isEdit="true"
    :isRecurrent="true"
    @onCreated="refreshInstances"
  />
  
  <!-- Confirmation dialog -->
  <ConfirmDialogue
    ref="confirmDialog"
    title="Delete Payment"
    :message="`Are you sure you want to delete ${payment?.title || ''}? This will also delete all payment instances.`"
    confirmLabel="Delete"
    @confirm="confirmDeletePayment"
  />
</template>

<script setup>

const props = defineProps({
  paymentId: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['openEdit']);

// ----- Define Refs ---------
const modal = ref(null);
const confirmDialog = ref(null);
const instanceEditor = ref(null);
const isLoading = ref(false);
const isSubmitting = ref(false);
const payment = ref(null);
const paymentInstances = ref([]);
const activeInstanceId = ref(null);

// ----- Define Store ---------
const recurrentStore = useRecurrentStore();
const paymentStore = usePaymentStore();
const { getRecurrentPayments, getPaymentInstances } = storeToRefs(recurrentStore);

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
  
  // Find the payment in store
  payment.value = getRecurrentPayments.value.find(p => p.id === paymentId);
  
  // Find all instances of this payment
  paymentInstances.value = getPaymentInstances.value
    .filter(p => p.recurrentId === paymentId)
    .sort((a, b) => b.createdAt.toDate() - a.createdAt.toDate()); // Most recent first
  
  isLoading.value = false;
}

function formatPrice(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(amount || 0);
}

function formatDate(timestamp) {
  if (!timestamp) return '';
  const { $dayjs } = useNuxtApp();
  return $dayjs(timestamp.toDate()).format('MMM D, YYYY');
}

async function togglePaymentStatus(paymentId, isPaid) {
  const result = await recurrentStore.togglePaymentStatus(paymentId, isPaid);
  
  if (result) {
    useToast('success', `Payment marked as ${isPaid ? 'paid' : 'unpaid'}`);
    // Update local state
    const instanceIndex = paymentInstances.value.findIndex(p => p.id === paymentId);
    if (instanceIndex !== -1) {
      paymentInstances.value[instanceIndex].isPaid = isPaid;
    }
  } else {
    useToast('error', recurrentStore.error || 'Failed to update payment status');
  }
}

function editPaymentInstance(instanceId) {
  activeInstanceId.value = instanceId;
  instanceEditor.value?.showModal(instanceId);
}

async function refreshInstances() {
  if (props.paymentId) {
    await recurrentStore.fetchPaymentInstances();
    fetchPaymentDetails(props.paymentId);
  }
}

function editPayment() {
  emit('openEdit', props.paymentId);
  closeModal();
}

function deletePayment() {
  if (!payment.value) return;
  confirmDialog.value.open();
}

async function confirmDeletePayment() {
  if (!payment.value) return;
  isSubmitting.value = true;
  
  try {
    const result = await recurrentStore.deleteRecurrentPayment(payment.value.id);
    
    if (result) {
      useToast('success', 'Payment deleted successfully');
      closeModal();
    } else {
      useToast('error', recurrentStore.error || 'Failed to delete payment');
    }
  } catch (error) {
    console.error('Error deleting payment:', error);
    useToast('error', 'An unexpected error occurred');
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
  showModal
});
</script>