<template>
    <div>
      <PaymentsManagePayment 
        ref="newPayment" 
        :isRecurrent="isRecurrent"
        @onClose="() => showAddButton = true" 
        @onCreated="emitCreated"
      />
      
      <div class="w-full m-auto" v-if="showAddButton">
        <button 
          @click="() => {showAddButton = false; newPayment.showModal()}" 
          class="
            flex justify-center items-center max-w-[25rem] w-full gap-[0.714rem] rounded-[0.714rem]
            bg-primary text-white m-auto px-[0.857rem] py-[0.714rem]
          "
        >
          <MdiPlus class="font-semibold"/>
          <span class="capitalize font-medium">Add {{ isRecurrent ? 'Recurring' : 'One-time' }} Payment</span>
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  import MdiPlus from '~icons/mdi/plus';
  import { ref } from 'vue';
  
  const props = defineProps({
    isRecurrent: {
      type: Boolean,
      default: false
    }
  });
  
  // ------ Define Vars ------
  const showAddButton = ref(true);
  
  // Emit events
  const emit = defineEmits(['onCreated']);
  
  // Refs
  const newPayment = ref(null);
  
  // Methods
  function emitCreated() {
    emit('onCreated');
    showAddButton.value = true;
  }
  </script>