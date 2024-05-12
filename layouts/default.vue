<template>
    <div class="container">
        <TheHeader />
        <div v-if="user" class="text-center mb-6">
            <h1>Pay Tracker App</h1>
            <span>Monitor your monthly payments and keep your life organized 😊</span>
        </div>
        <h2 class="mb-2" v-if="user">Quick Actions</h2>
        <div v-if="user" class="flex flex-row gap-2 overflow-y-auto no-scrollbar">
            <NuxtLink class="px-2 py-1 rounded-full text-center min-w-20 text-sm" :class="{selected: route.path == '/'}" to="/">Payments</NuxtLink>
            <NuxtLink class="px-2 py-1 rounded-full text-center min-w-20 text-sm" :class="{selected: route.path == '/edit'}" to="/edit">Recurrent</NuxtLink>
            <NuxtLink class="px-2 py-1 rounded-full text-center min-w-20 text-sm" :class="{selected: route.path == '/history'}" to="/history">History</NuxtLink>
            <NuxtLink class="px-2 py-1 rounded-full text-center min-w-20 text-sm" :class="{selected: route.path == '/summary'}" to="/summary">Summary</NuxtLink>
        </div>
        <main>
            <slot />
        </main>
        <TheFooter />
    </div>
</template>

<script setup>
const user = useCurrentUser();
const route = useRoute()

// ----- Define Pinia Vars -----------
const indexStore = useIndexStore();
// TODO: Look for a way to speed up this in case the user has already fetched the data
if(user) {
    // await indexStore.fetchData();
}
</script>


<style scoped>
a {
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-radius: 1rem;
    padding: 5px 0px;
    margin-bottom: 12px;
}
a:hover, .selected {
    background-color: #a0a4d9;
    border: 1px solid #a0a4d9;
    color: #1f2023;
    font-weight: bold;
    outline: none;
}
</style>