<template>
  <div class="w-full min-h-full">
    <TheHeader />
    <div class="flex flex-col gap-[3rem] max-w-[80rem] m-auto px-[1.429rem]">
      <main>
        <slot @totals="updateTotals" />
      </main>
    </div>
    <TheFooter />
  </div>
</template>

<script setup>
// ----- Define Pinia Vars -----------
// ----- Define Computed ---------
</script>


<style scoped>
.selected {
  background-color: var(--secondary-color);
  color: white;
  font-weight: 600;
}
</style>