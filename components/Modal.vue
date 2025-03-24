<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="modal-backdrop" @click="handleBackdropClick">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <slot name="header">
              <h3 class="text-lg font-medium">Modal Title</h3>
            </slot>
            <button class="modal-close" @click="close">
              <span class="sr-only">Close</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="modal-body">
            <slot name="body">
              <p>Modal content goes here</p>
            </slot>
          </div>
          
          <div class="modal-footer">
            <slot name="footer">
              <button class="btn-primary" @click="close">Close</button>
            </slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  closeOnBackdrop: {
    type: Boolean,
    default: true
  }
});

const isOpen = ref(false);

function open() {
  isOpen.value = true;
  document.body.classList.add('modal-open');
}

function close() {
  isOpen.value = false;
  document.body.classList.remove('modal-open');
}

function handleBackdropClick() {
  if (props.closeOnBackdrop) {
    close();
  }
}

// Expose methods
defineExpose({
  open,
  close,
  isOpen
});
</script>