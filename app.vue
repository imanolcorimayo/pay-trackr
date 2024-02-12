<template>
  <div>
    <NuxtLoadingIndicator :color="'#545AFA'" :height="10" :throttle="0" :rtl="false" :continuous="true" />
    <NuxtLayout>
      <NuxtPage/>
    </NuxtLayout>
  </div>
</template>

<script setup>
useHead({
  titleTemplate: "%s | UtilsCom"
});
</script>