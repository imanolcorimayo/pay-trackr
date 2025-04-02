<template>
  <div>
    <NuxtLoadingIndicator color="#a0a4d9" :height="10" :throttle="0" :rtl="false" :continuous="true" />
    <NotificationManager v-if="user" />
    <NuxtLayout>
      <NuxtPage/>
    </NuxtLayout>
  </div>
</template>

<script setup>
useHead({
  titleTemplate: "%s | WiseUtils"
});
</script>