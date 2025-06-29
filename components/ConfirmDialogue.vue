<template>
  <teleport to="body">
    <div
      v-if="isRevealed"
      id="deleteModal"
      tabindex="-1"
      class="overflow-y-auto overflow-x-hidden flex h-full fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full bg-black/[.5]"
    >
      <div class="relative p-4 w-full max-w-md md:h-auto">
        <!-- Modal content -->
        <div class="relative p-4 text-center bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
          <button
            @click="confirm(false)"
            class="text-gray-400 absolute top-2.5 right-2.5 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
            data-modal-toggle="deleteModal"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              ></path>
            </svg>
            <span class="sr-only">Close modal</span>
          </button>
          <svg
            v-if="!isEdit"
            class="text-gray-400 dark:text-gray-500 w-11 h-11 mb-3.5 mx-auto"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
              clip-rule="evenodd"
            ></path>
          </svg>
          <svg
            v-else
            class="text-gray-400 dark:text-gray-500 w-11 h-11 mb-3.5 mx-auto"
            fill="none"
            height="24"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            viewBox="0 0 24 24"
            width="24"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
          </svg>
          <p class="mb-4 text-gray-500 dark:text-gray-300">{{ message }}</p>
          <div class="flex justify-center items-center space-x-4">
            <button
              @click="confirm(false)"
              class="py-2 px-3 text-sm font-medium text-gray-500 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
            >
              {{ textCancelButton }}
            </button>
            <button
              @click="confirm(true)"
              class="py-2 px-3 text-sm font-medium text-center text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
            >
              {{ textConfirmButton }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { useConfirmDialog } from "@vueuse/core";

const props = defineProps({
  message: {
    required: false,
    type: String,
    default: "Are you sure to continue?"
  },
  textCancelButton: {
    required: false,
    type: String,
    default: "No, cancel"
  },
  textConfirmButton: {
    required: false,
    type: String,
    default: "Yes, I'm sure"
  }
});

const $emit = defineEmits(["confirm"]);

const { isRevealed, reveal, confirm, cancel } = useConfirmDialog();
const isEdit = ref(false);

async function openDialog(props) {
  if (props && props.edit) {
    isEdit.value = props.edit;
  }

  const { data, isCanceled } = await reveal();

  if (data) {
    $emit("confirm");
  }
}

defineExpose({
  open: openDialog
});
</script>
