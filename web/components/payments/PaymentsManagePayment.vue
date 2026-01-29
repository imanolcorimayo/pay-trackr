<template>
  <div>
    <Modal ref="modal">
      <template #header>
        <div class="flex items-center">
          <div v-if="isLoading" class="w-3 h-14 rounded-full mr-3 bg-gray-200 animate-pulse"></div>
          <div
            v-else-if="form.categoryId"
            class="w-3 h-14 rounded-full mr-3"
            :style="{ backgroundColor: getCategoryColor(form.categoryId) }"
          ></div>
          <div>
            <h2 class="text-xl font-bold">
              {{ props.isReview ? "Revisar" : (isEdit ? "Editar" : "Crear") }} Pago {{ isRecurrent ? "Recurrente" : "Único" }}
            </h2>
            <p class="text-sm text-gray-500" v-if="isEdit && form.title">{{ form.title }}</p>
            <p class="text-xs text-green-400 flex items-center gap-1 mt-1" v-if="props.isReview">
              <span class="inline-block w-2 h-2 rounded-full bg-green-400"></span>
              Creado via WhatsApp
            </p>
          </div>
        </div>
      </template>

      <template #body>
        <div v-if="isLoading" class="flex justify-center items-center min-h-[200px]">
          <Loader />
        </div>

        <form v-else @submit.prevent="savePayment" class="space-y-6">
          <!-- Quick Templates (only for new one-time payments) -->
          <div v-if="!props.isEdit && !props.isRecurrent && templates.length > 0" class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Agregar Rápido</span>
              <button
                v-if="templates.length > 4"
                type="button"
                @click="templatesExpanded = !templatesExpanded"
                class="text-xs text-primary hover:text-primary/80 transition-colors flex items-center gap-1"
              >
                {{ templatesExpanded ? 'Colapsar' : 'Expandir' }}
                <svg
                  class="w-3 h-3 transition-transform duration-300"
                  :class="{ 'rotate-180': templatesExpanded }"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </div>

            <!-- Templates Container -->
            <div
              class="overflow-hidden transition-all duration-300 ease-out"
              :style="{ maxHeight: templatesExpanded ? '500px' : '42px' }"
            >
              <div
                class="flex flex-wrap gap-2"
                :class="{ 'flex-nowrap overflow-x-auto scrollbar-hide': !templatesExpanded }"
              >
                <button
                  v-for="template in templates"
                  :key="template.id"
                  type="button"
                  @click="selectTemplate(template)"
                  class="flex-shrink-0 px-3 py-1.5 text-sm font-medium rounded-full border-2 transition-all duration-200 hover:scale-105"
                  :style="getTemplateStyle(template.categoryId, currentTemplate?.id === template.id)"
                >
                  {{ template.name }}
                </button>
              </div>
            </div>
          </div>

          <!-- Payment Title & Description -->
          <div class="space-y-2">
            <label for="title" class="block text-sm font-medium text-gray-400">Título del Pago*</label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              :disabled="props.isRecurrent"
              required
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="ej. Suscripción Netflix"
            />
          </div>

          <div class="space-y-2" v-if="!props.isRecurrent">
            <button
              type="button"
              @click="showDescription = !showDescription"
              class="text-sm text-primary hover:text-primary-dark flex items-center gap-1"
            >
              <span>{{ showDescription ? '− Ocultar' : '+ Agregar' }} Descripción</span>
            </button>
            <textarea
              v-if="showDescription"
              id="description"
              v-model="form.description"
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="Agregá detalles sobre este pago"
              rows="2"
            ></textarea>
          </div>

          <!-- Amount & Category -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label for="amount" class="block text-sm font-medium text-gray-400">Monto*</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                <input
                  id="amount"
                  ref="amountInput"
                  v-model="form.amount"
                  type="text"
                  inputmode="decimal"
                  pattern="[0-9]*[.,]?[0-9]*"
                  @input="normalizeAmount"
                  required
                  class="w-full p-2 pl-7 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                  placeholder="0,00"
                />
              </div>
            </div>

            <div class="space-y-2" v-if="!props.isRecurrent">
              <label for="category" class="block text-sm font-medium text-gray-400">Categoría*</label>
              <select
                id="category"
                v-model="form.categoryId"
                required
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- One-time Payment Fields -->
          <div class="space-y-2" v-if="!props.isRecurrent && !props.isEdit">
            <label for="dueDate" class="block text-sm font-medium text-gray-400">Fecha de Pago*</label>
            <input
              id="dueDate"
              v-model="form.dueDate"
              type="date"
              required
              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>

          <!-- Save as Template Option (only on creation) -->
          <div v-if="!props.isRecurrent && !props.isEdit" class="space-y-2">
            <label class="flex items-center space-x-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="saveAsTemplate"
                class="form-checkbox h-5 w-5 text-primary rounded focus:ring-primary"
              />
              <span class="text-sm font-medium text-gray-400">Guardar como plantilla</span>
            </label>
          </div>

          <!-- Edit mode: Show all fields -->
          <div v-if="!props.isRecurrent && props.isEdit" class="space-y-4">
            <div class="space-y-2">
              <label for="dueDate" class="block text-sm font-medium text-gray-400">Fecha de Vencimiento*</label>
              <input
                id="dueDate"
                v-model="form.dueDate"
                type="date"
                required
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <div class="space-y-2">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.isPaid"
                  class="form-checkbox h-5 w-5 text-primary rounded focus:ring-primary"
                />
                <span class="text-sm font-medium text-gray-400">Marcar como pagado</span>
              </label>

              <div v-if="form.isPaid" class="mt-2">
                <label for="paidDate" class="block text-sm font-medium text-gray-400">Fecha de Pago</label>
                <input
                  id="paidDate"
                  v-model="form.paidDate"
                  type="date"
                  class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                />
              </div>
            </div>
          </div>
        </form>
      </template>

      <template #footer>
        <!-- Continue Adding Confirmation -->
        <div v-if="continueAdding" class="flex justify-center w-full gap-3">
          <button
            @click="addAnother"
            class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
          >
            Agregar Otro
          </button>
          <button
            @click="closeModal"
            class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Listo
          </button>
        </div>

        <!-- Normal Footer -->
        <div v-else class="flex justify-between w-full">
          <button
            @click="closeModal"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </button>

          <div class="flex space-x-2">
            <button
              v-if="isEdit && !props.isReview"
              @click="confirmDelete"
              class="px-4 py-2 bg-danger/10 text-danger rounded-lg hover:bg-danger/20 transition-colors"
            >
              Eliminar
            </button>

            <!-- Mark as pending for later review (only for WhatsApp payments that are reviewed) -->
            <button
              v-if="isEdit && form.isWhatsapp && form.status === 'reviewed'"
              @click="markAsPending"
              class="px-4 py-2 bg-warning/10 text-warning rounded-lg hover:bg-warning/20 transition-colors"
              :disabled="isSubmitting"
            >
              Revisar después
            </button>

            <!-- Review Mode: "Todo perfecto" button -->
            <button
              v-if="props.isReview"
              @click="markAsReviewed"
              class="px-4 py-2 bg-success text-white rounded-lg hover:bg-success/90 transition-colors flex items-center gap-2"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting">
                <span
                  class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
                ></span>
              </span>
              <span v-else>✓ Todo perfecto</span>
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
                Guardando...
              </span>
              <span v-else>{{ props.isReview ? "Guardar cambios" : (isEdit ? "Actualizar" : "Crear") }}</span>
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <ConfirmDialogue
      ref="confirmDialog"
      :message="`¿Estás seguro que querés eliminar ${form.title}?`"
      textConfirmButton="Eliminar"
      @confirm="deletePayment"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { storeToRefs } from "pinia";
import { getCurrentUser } from "~/utils/firebase";
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
  },
  isReview: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(["onClose", "onCreated"]);

// ----- Define Stores (must be before computed properties that use them) ---------
const recurrentStore = useRecurrentStore();
const paymentStore = usePaymentStore();
const templateStore = useTemplateStore();
const categoryStore = useCategoryStore();
const { getTemplatesSorted: templates } = storeToRefs(templateStore);
const { getCategories: categories } = storeToRefs(categoryStore);

// ----- Define Refs ---------
const modal = ref(null);
const confirmDialog = ref(null);
const isLoading = ref(false);
const isSubmitting = ref(false);

// Store last used values at component level
const lastUsedCategoryId = ref('');
const lastUsedDueDate = ref('');

// Get default category ID (first category or empty)
const defaultCategoryId = computed(() => {
  if (lastUsedCategoryId.value) return lastUsedCategoryId.value;
  // Find "Otros" category or use first available
  const otrosCategory = categories.value.find(c => c.name === 'Otros');
  return otrosCategory?.id || categories.value[0]?.id || '';
});

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
      categoryId: defaultCategoryId.value,
      dueDate: lastUsedDueDate.value || today,
      isPaid: true, // Always mark as paid on creation
      paidDate: lastUsedDueDate.value || today, // Set paidDate same as dueDate
      paymentType: "one-time",
      isWhatsapp: false,
      status: "reviewed"
    };
  }
});

const form = ref({ ...defaultForm.value });
const showDescription = ref(false);
const continueAdding = ref(false);
const saveAsTemplate = ref(false);
const amountInput = ref(null);
const currentTemplate = ref(null); // Track if opened from template
const templatesExpanded = ref(false);

// ----- Define Methods ---------
async function showModal(paymentId = null, templateData = null) {
  // Store template for "Add Another" functionality
  currentTemplate.value = templateData;

  // Fetch categories if not already loaded
  categoryStore.fetchCategories();

  // Fetch templates if not already loaded (for quick templates in modal)
  if (!props.isRecurrent && !paymentId) {
    templateStore.fetchTemplates();
  }

  if (paymentId) {
    fetchPaymentDetails(paymentId);
  } else if (templateData) {
    // Pre-fill from template
    applyTemplate(templateData);
  } else {
    // Reset form when creating new payment
    form.value = { ...defaultForm.value };
    showDescription.value = false;
    saveAsTemplate.value = false;
  }

  templatesExpanded.value = false;
  modal.value?.open();
}

function applyTemplate(templateData) {
  const { $dayjs } = useNuxtApp();
  const dueDate = lastUsedDueDate.value || $dayjs().format("YYYY-MM-DD");

  form.value = {
    title: templateData.name,
    description: templateData.description || "",
    amount: "",
    categoryId: templateData.categoryId || defaultCategoryId.value,
    dueDate: dueDate,
    isPaid: true,
    paidDate: dueDate,
    paymentType: "one-time",
    isWhatsapp: false,
    status: "reviewed"
  };

  showDescription.value = !!templateData.description;
  saveAsTemplate.value = false;

  // Focus on amount field after modal opens
  nextTick(() => {
    amountInput.value?.focus();
  });
}

function selectTemplate(template) {
  currentTemplate.value = template;
  applyTemplate(template);
  templateStore.incrementUsage(template.id);
}

// AMOUNT CONVERSION: Display uses comma (1234,56), Database uses period (1234.56)

// User input → Display format (comma)
function normalizeAmount(event) {
  let value = event.target.value.replace(/[^0-9.,]/g, '');
  value = value.replace('.', ','); // Period → Comma for display
  const parts = value.split(',');
  if (parts.length > 2) value = parts[0] + ',' + parts.slice(1).join('');
  form.value.amount = value;
}

// Display format → Database format (for saving)
function parseAmount(value) {
  if (typeof value === 'string') {
    return parseFloat(value.replace(',', '.')) || 0; // Comma → Period for DB
  }
  return parseFloat(value) || 0;
}

// Database format → Display format (for editing)
function formatAmountForInput(value) {
  if (!value && value !== 0) return '';
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return '';
  return num.toString().replace('.', ','); // Period → Comma for display
}

// Get category color by ID from store
function getCategoryColor(categoryId) {
  if (!categoryId) return '#808080';
  return categoryStore.getCategoryColor(categoryId);
}

// Get template button style based on categoryId
function getTemplateStyle(categoryId, isActive) {
  const color = getCategoryColor(categoryId);

  if (isActive) {
    return {
      borderColor: color,
      backgroundColor: color,
      color: 'white',
      boxShadow: `0 10px 15px -3px ${color}4D`
    };
  }

  return {
    borderColor: color,
    backgroundColor: `${color}1A`,
    color: color
  };
}

function closeModal() {
  continueAdding.value = false;
  currentTemplate.value = null;
  modal.value?.close();
  emit("onClose");
}

function addAnother() {
  continueAdding.value = false;
  saveAsTemplate.value = false;

  if (currentTemplate.value) {
    // Template mode: re-apply template (keeps title, description, category)
    applyTemplate(currentTemplate.value);
  } else {
    // Normal mode: reset form but keep date and category
    form.value = { ...defaultForm.value };
    showDescription.value = false;
  }
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
          amount: formatAmountForInput(payment.amount)
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
          amount: formatAmountForInput(payment.amount),
          categoryId: payment.categoryId || defaultCategoryId.value,
          dueDate: dueDate,
          isPaid: payment.isPaid || true,
          paidDate: paidDate,
          paymentType: payment.paymentType || "one-time",
          isWhatsapp: payment.isWhatsapp || false,
          status: payment.status || "reviewed"
        };

        // Show description if it has content
        showDescription.value = !!payment.description;
      }
    }
  } catch (error) {
    console.error("Error fetching payment details:", error);
    useToast("error", "Error al cargar los detalles del pago");
  } finally {
    isLoading.value = false;
  }
}

async function savePayment() {
  const user = getCurrentUser();
  if (!user) {
    useToast("error", "Debés iniciar sesión para guardar pagos");
    return;
  }

  if (isSubmitting.value) return;
  isSubmitting.value = true;

  try {
    let result;

    // Handle one-time payment save/update
    const { $dayjs } = useNuxtApp();

    // Save last used values to component state (only on creation, not edit)
    if (!props.isEdit && !props.isRecurrent) {
      lastUsedCategoryId.value = form.value.categoryId;
      lastUsedDueDate.value = form.value.dueDate;
    }

    let paymentData = {
      title: form.value.title,
      description: form.value.description,
      amount: parseAmount(form.value.amount),
      categoryId: form.value.categoryId,
      isPaid: form.value.isPaid,
      paidDate: form.value.isPaid ? Timestamp.fromDate($dayjs(form.value.paidDate).toDate()) : null,
      dueDate: Timestamp.fromDate($dayjs(form.value.dueDate).toDate()),
      recurrentId: null,
      paymentType: "one-time",
      isWhatsapp: form.value.isWhatsapp || false,
      status: form.value.status || 'reviewed'
    };

    if (props.isRecurrent) {
      paymentData = {
        title: form.value.title,
        amount: parseAmount(form.value.amount)
      };
    }

    if (props.isRecurrent && !props.isEdit) {
      useToast("error", "Los pagos recurrentes no se pueden crear desde este formulario");
      isSubmitting.value = false;
      return;
    }

    if (props.isEdit && props.paymentId) {
      // Update existing payment
      result = await paymentStore.updatePayment(props.paymentId, paymentData);

      if (result) {
        useToast("success", props.isReview ? "Pago revisado correctamente" : "Pago actualizado correctamente");
        emit("onCreated");
        closeModal();
      } else {
        useToast("error", paymentStore.error || "Error al actualizar el pago");
      }
    } else {
      // Create new payment
      result = await paymentStore.createPayment({
        ...paymentData,
        userId: user.uid
      });

      if (result && result.success) {
        useToast("success", "Pago creado correctamente");
        emit("onCreated");

        // Save as template if checkbox is checked
        if (saveAsTemplate.value) {
          const templateData = {
            name: form.value.title,
            categoryId: form.value.categoryId,
            ...(form.value.description && { description: form.value.description })
          };

          await templateStore.createTemplate(templateData);
        }

        // Ask if user wants to continue adding payments
        continueAdding.value = true;
      } else {
        useToast("error", paymentStore.error || "Error al crear el pago");
      }
    }
  } catch (error) {
    console.error("Error saving payment:", error);
    useToast("error", "Ocurrió un error inesperado");
  } finally {
    isSubmitting.value = false;
  }
}

function confirmDelete() {
  confirmDialog.value.open();
}

async function deletePayment() {
  isSubmitting.value = true;

  try {
    const result = await paymentStore.deletePayment(props.paymentId);

    if (result) {
      useToast("success", "Pago eliminado correctamente");
      emit("onCreated");
      closeModal();
    } else {
      useToast("error", paymentStore.error || "Error al eliminar el pago");
    }
  } catch (error) {
    console.error("Error deleting payment:", error);
    useToast("error", "Ocurrió un error inesperado");
  } finally {
    isSubmitting.value = false;
  }
}

// Mark WhatsApp payment as reviewed without changes - uses same logic as savePayment
async function markAsReviewed() {
  if (isSubmitting.value || !props.paymentId) return;

  // Just call savePayment with status set to reviewed - form data is already loaded
  form.value.status = 'reviewed';
  await savePayment();
}

// Mark WhatsApp payment as pending for later review
async function markAsPending() {
  if (isSubmitting.value || !props.paymentId) return;

  form.value.status = 'pending';
  await savePayment();
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

/* Hide scrollbar but keep functionality */
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
