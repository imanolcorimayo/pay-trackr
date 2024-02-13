<template>
  <header class="w-full">
    <nav class="">
      <div class="w-full max-w-screen flex justify-between items-center mx-auto p-4">
        <NuxtLink to="/" class="flex items-center space-x-3 rtl:space-x-reverse">
          <img src="/img/logo.png" class="w-24" alt="PayTrackr Logo" />
        </NuxtLink>
        <div class="relative">
          <button class="text-sm p-0 rounded-full no-button" @click="switchMenu">
            <img class="w-14 h-14 rounded-full" :src="user?.photoURL" :alt="`${user?.displayName}'s photo`" width="90" height="90">
          </button>
          <!-- Dropdown menu -->
          <div
            class="z-50 my-4 text-base list-none divide-y rounded-lg shadow bg-gray-800 absolute top-12 right-0"
            v-if="showMenu" ref="dropdownMenu"
            >
            <div class="px-4 py-3">
              <span class="block text-sm text-gray-900 dark:text-white min-w-60">{{ user?.displayName }}</span>
              <span class="block text-sm  text-gray-500 truncate dark:text-gray-400">{{ user?.email }}</span>
            </div>
            <ul class="py-2">
              <li @click="switchMenu">
                <NuxtLink to="/"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/'}">Payments</NuxtLink>
              </li>
              <li @click="switchMenu">
                <NuxtLink to="/new-payment"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/new-payment'}">New Payment</NuxtLink>
              </li>
              <li @click="switchMenu">
                <NuxtLink to="/edit"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/edit'}">Edit Payments</NuxtLink>
              </li>
              <!-- <li @click="switchMenu">
                <NuxtLink to="/history"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/history'}">History</NuxtLink>
              </li> -->
              <li @click="switchMenu">
                <NuxtLink to="/contact-us"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/contact-us'}">Contact Us</NuxtLink>
              </li>
              <li @click="switchMenu">
                <button @click="signOut" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white no-button">Sign Out</button>
              </li>
            </ul>
          </div>
        </div>
    </div>
  </nav>
</header></template>

<script setup>

const auth = useFirebaseAuth()
const user = await getCurrentUser();
const route = useRoute();
// ---- Define Vars --------
const showMenu = ref(false)
const dropdownMenu = ref(null)
onClickOutside(dropdownMenu, event => switchMenu())
// ---- Define Methods --------
function switchMenu() {
  showMenu.value = !showMenu.value
}

async function signOut() {
    // Sign out from firebase
    await auth.signOut();

    // Redirect to welcome page
    await navigateTo('/welcome');
}

</script>

<style scoped>
a:hover, .selected {
    background-color: #a0a4d9;
    border: 1px solid #a0a4d9;
    color: #1f2023;
    font-weight: bold;
    outline: none;
}
</style>