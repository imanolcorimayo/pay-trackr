<template>
  <div class="w-full min-h-full">
    <TheHeader />

    <!-- Main navigation tabs (only for authenticated users) -->
    <div v-if="user" class="w-full bg-base border-b border-gray-700 mb-4">
      <div class="max-w-[80rem] m-auto px-0 sm:px-[1.429rem]">
        <nav class="flex overflow-x-auto" aria-label="Main navigation">
          <NuxtLink to="/recurrent" class="nav-tab" :class="{ 'nav-tab-active': route.path === '/recurrent' }">
            <span class="flex items-center gap-2"> Recurrent <span class="hidden sm:inline">Payments</span> </span>
          </NuxtLink>
          <NuxtLink to="/one-time" class="nav-tab" :class="{ 'nav-tab-active': route.path === '/one-time' }">
            <span class="flex items-center gap-2"> One-Time <span class="hidden sm:inline">Payments</span> </span>
          </NuxtLink>
          <NuxtLink to="/summary" class="nav-tab" :class="{ 'nav-tab-active': route.path === '/summary' }">
            Financial Summary
          </NuxtLink>
        </nav>
      </div>
    </div>
    <div class="flex flex-col gap-[3rem] max-w-[80rem] m-auto px-0 sm:px-[1.429rem]">
      <main>
        <slot @totals="updateTotals" />
      </main>
    </div>
    <TheFooter />
  </div>
</template>

<script setup>
const user = await getCurrentUser();
const route = useRoute();
// ----- Define Pinia Vars -----------
// ----- Define Computed ---------
</script>

<style scoped>
.selected {
  background-color: var(--secondary-color);
  color: white;
  font-weight: 600;
}

.nav-tab {
  @apply py-4 px-4 text-gray-300 border-b-2 border-transparent font-medium text-sm whitespace-nowrap;
  transition: color 0.2s, border-color 0.2s;
}

.nav-tab:hover {
  @apply text-white border-gray-500;
}

.nav-tab-active {
  @apply text-primary border-primary font-semibold;
}

/* Ensure tabs stack properly on very small screens */
@media (max-width: 380px) {
  nav {
    @apply flex-wrap justify-center;
  }
  
  .nav-tab {
    @apply text-center;
  }
}
</style>
