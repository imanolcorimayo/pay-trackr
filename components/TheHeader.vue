<template>
  <header class="w-full max-w-[80rem] m-auto px-[1.429rem]">
    <nav>
      <div class="w-full max-w-screen flex justify-between items-center mx-auto py-[1.429rem] pt-0">
        <NuxtLink to="/" class="flex items-center space-x-3 rtl:space-x-reverse hover:bg-transparent no-hover">
          <img src="/img/new-logo.png" class="w-24" width="94" height="74" alt="PayTrackr Logo" />
        </NuxtLink>
        <div class="relative" v-if="user">
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
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/'}">Home Page</NuxtLink>
              </li>
              <li @click="switchMenu">
                <NuxtLink to="/recurrent"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/recurrent'}">Recurrent Payments</NuxtLink>
              </li>
              <li @click="switchMenu">
                <NuxtLink to="/one-time"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/one-time'}">One-Time Payments</NuxtLink>
              </li>
              <li @click="switchMenu">
                <NuxtLink to="/summary"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/summary'}">Summary</NuxtLink>
              </li>
              <!-- TODO: Potentially needed in the future -->
              <!-- <li @click="switchMenu">
                <NuxtLink to="/blog"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 dark:hover:text-white" :class="{selected: route.path =='/blog'}">Blog</NuxtLink>
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
        <div class="ms-4" v-else>
          <ul class="flex w-50 sm:w-80 justify-between flex-wrap">
            <li class="border-opacity-35 border-white border-b mx-1 hidden sm:block"><NuxtLink to="/">Home</NuxtLink></li>
            <!-- TODO: Potentially needed in the future -->
            <!-- <li class="border-opacity-35 border-white border-b mx-1"><NuxtLink to="/blog">Blog</NuxtLink></li> -->
            <li class="border-opacity-35 border-white border-b mx-1"><NuxtLink to="/faq">FAQ</NuxtLink></li>
            <li class="border-opacity-35 border-white border-b mx-1"><NuxtLink to="/contact-us">Contact</NuxtLink></li>
            <li class="border-opacity-35 border-white border-b mx-1"><NuxtLink to="/welcome">Sign In</NuxtLink></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>
</template>

<script setup>

// ----- Define Useful Properties ---------
const auth = useFirebaseAuth()
const user = await getCurrentUser();
const route = useRoute();

// ----- Define Pinia Vars ----------
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
    if(!route.fullPath.includes("/")) {
      return await navigateTo("/");
    }
    // If already in landing page, reload
    location.reload();
}
</script>

<style scoped>
.selected {
    background-color: #a0a4d9;
    border: 1px solid #a0a4d9;
    color: #1f2023;
    font-weight: bold;
    outline: none;
}

li {
  cursor: pointer;
}
li:hover {
  color: #a0a4d9;
  font-weight: bold;
}

.no-hover{
    background-color: unset !important;
    border: unset !important;
}
</style>