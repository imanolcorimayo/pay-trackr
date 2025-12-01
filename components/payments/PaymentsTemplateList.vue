<template>
  <div v-if="templates.length > 0" class="px-3 mb-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-sm font-medium text-gray-400">Quick Templates</h3>
      <span class="text-xs text-gray-500">{{ templates.length }}</span>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
      <button
        v-for="template in sortedTemplates"
        :key="template.id"
        @click="useTemplate(template)"
        @contextmenu.prevent="confirmDeleteTemplate(template)"
        class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all hover:scale-105"
        :class="getCategoryButtonClass(template.category)"
      >
        <div class="flex items-center gap-2">
          <span>{{ template.name }}</span>
          <span v-if="template.usageCount > 0" class="text-xs opacity-70">
            {{ template.usageCount }}
          </span>
        </div>
      </button>
    </div>

    <ConfirmDialogue
      ref="confirmDialog"
      title="Delete Template"
      :message="`Are you sure you want to delete '${templateToDelete?.name}'?`"
      confirmLabel="Delete"
      @confirm="deleteTemplate"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { storeToRefs } from "pinia";

const emit = defineEmits(["useTemplate"]);

// ----- Define Stores ---------
const templateStore = useTemplateStore();
const { getTemplatesSorted: templates } = storeToRefs(templateStore);

// ----- Define Refs ---------
const confirmDialog = ref(null);
const templateToDelete = ref(null);

// ----- Define Computed ---------
const sortedTemplates = computed(() => templates.value.slice(0, 5)); // Show max 5 templates

// ----- Define Methods ---------
function useTemplate(template) {
  emit("useTemplate", template);
  templateStore.incrementUsage(template.id);
}

function confirmDeleteTemplate(template) {
  templateToDelete.value = template;
  confirmDialog.value?.open();
}

async function deleteTemplate() {
  if (!templateToDelete.value) return;

  const result = await templateStore.deleteTemplate(templateToDelete.value.id);

  if (result) {
    useToast("success", "Template deleted successfully");
  } else {
    useToast("error", "Failed to delete template");
  }

  templateToDelete.value = null;
}

function getCategoryButtonClass(category) {
  const baseClasses = "border-2";

  switch(category.toLowerCase()) {
    case 'housing':
      return `${baseClasses} bg-[#4682B4]/10 border-[#4682B4] text-[#4682B4] hover:bg-[#4682B4]/20`;
    case 'utilities':
      return `${baseClasses} bg-[#0072DF]/10 border-[#0072DF] text-[#0072DF] hover:bg-[#0072DF]/20`;
    case 'food':
      return `${baseClasses} bg-[#1D9A38]/10 border-[#1D9A38] text-[#1D9A38] hover:bg-[#1D9A38]/20`;
    case 'dining':
      return `${baseClasses} bg-[#FF6347]/10 border-[#FF6347] text-[#FF6347] hover:bg-[#FF6347]/20`;
    case 'transport':
      return `${baseClasses} bg-[#E6AE2C]/10 border-[#E6AE2C] text-[#E6AE2C] hover:bg-[#E6AE2C]/20`;
    case 'entertainment':
      return `${baseClasses} bg-[#6158FF]/10 border-[#6158FF] text-[#6158FF] hover:bg-[#6158FF]/20`;
    case 'health':
      return `${baseClasses} bg-[#E84A8A]/10 border-[#E84A8A] text-[#E84A8A] hover:bg-[#E84A8A]/20`;
    case 'pet':
      return `${baseClasses} bg-[#3CAEA3]/10 border-[#3CAEA3] text-[#3CAEA3] hover:bg-[#3CAEA3]/20`;
    case 'clothes':
      return `${baseClasses} bg-[#800020]/10 border-[#800020] text-[#800020] hover:bg-[#800020]/20`;
    case 'traveling':
      return `${baseClasses} bg-[#FF8C00]/10 border-[#FF8C00] text-[#FF8C00] hover:bg-[#FF8C00]/20`;
    case 'education':
      return `${baseClasses} bg-[#9370DB]/10 border-[#9370DB] text-[#9370DB] hover:bg-[#9370DB]/20`;
    case 'subscriptions':
      return `${baseClasses} bg-[#20B2AA]/10 border-[#20B2AA] text-[#20B2AA] hover:bg-[#20B2AA]/20`;
    case 'taxes':
      return `${baseClasses} bg-[#8B4513]/10 border-[#8B4513] text-[#8B4513] hover:bg-[#8B4513]/20`;
    case 'other':
    default:
      return `${baseClasses} bg-[#808080]/10 border-[#808080] text-[#808080] hover:bg-[#808080]/20`;
  }
}

// ----- Initialize Data ---------
onMounted(async () => {
  await templateStore.fetchTemplates();
});
</script>

<style scoped>
/* Hide scrollbar but keep functionality */
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
