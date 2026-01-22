<template>
  <div>
    <VitePwaManifest />
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

const { $pwa } = useNuxtApp();
const user = getCurrentUser();

onMounted(() => {
  // PWA installation status available via $pwa?.isPWAInstalled
});
</script>