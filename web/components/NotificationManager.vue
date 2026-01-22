<template>
  <div v-if="showBanner" class="fixed bottom-4 right-4 p-4 bg-gray-800 rounded-lg shadow-lg z-50 max-w-sm">
    <div class="flex items-start">
      <div class="flex-1">
        <h3 class="font-medium text-white">Enable Notifications</h3>
        <p class="text-sm text-gray-300 mt-1">
          Get timely reminders when your payments are due so you never miss a payment.
        </p>
      </div>
      <button @click="dismissBanner" class="text-gray-400 hover:text-white">
        <span class="sr-only">Close</span>
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    <div class="mt-3 flex space-x-3">
      <button 
        @click="enableNotifications" 
        class="flex-1 bg-primary text-white px-3 py-1.5 rounded-md text-sm font-medium"
      >
        Enable
      </button>
      <button 
        @click="dismissBanner" 
        class="flex-1 bg-gray-700 text-white px-3 py-1.5 rounded-md text-sm font-medium"
      >
        Not Now
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const { 
  notificationsSupported, 
  notificationPermission, 
  requestPermission, 
  checkForDuePayments 
} = useNotifications();

const recurrentStore = useRecurrentStore();
const showBanner = ref(false);
let checkInterval = null;

// Only show banner if notifications are supported and not already granted
onMounted(() => {
  // Check if we've asked before
  const hasAskedBefore = localStorage.getItem('notificationPermissionAsked');
  
  if (notificationsSupported.value && 
      notificationPermission.value !== 'granted' && 
      !hasAskedBefore) {
    showBanner.value = true;
  }
  
  // Set up regular checks for due payments if permission is granted
  if (notificationPermission.value === 'granted') {
    setupPaymentChecks();
  }
});

onBeforeUnmount(() => {
  if (checkInterval) {
    clearInterval(checkInterval);
  }
});

async function enableNotifications() {
  const granted = await requestPermission();
  showBanner.value = false;
  localStorage.setItem('notificationPermissionAsked', 'true');
  
  if (granted) {
    setupPaymentChecks();
  }
}

function dismissBanner() {
  showBanner.value = false;
  localStorage.setItem('notificationPermissionAsked', 'true');
}

function setupPaymentChecks() {
  // Immediately check for any payments
  checkForDuePayments(recurrentStore);
  
  // Then check every 12 hours
  checkInterval = setInterval(() => {
    checkForDuePayments(recurrentStore);
  }, 12 * 60 * 60 * 1000);
}
</script>