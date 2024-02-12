<template>
    <div class="container">
        <header class="w-full flex flex-row justify-between">
            <ClientOnly>
                <div class="m-2 text-center">
                    <img :src="user?.photoURL" :alt="`${user?.displayName}'s photo`" width="60" height="60" class="rounded-full mx-2 inline">
                    <span>{{ user?.displayName }}</span>
                </div>
            </ClientOnly>
            <button @click="signOut" class="m-2 min-w-14">
                Log out
            </button>
        </header>
        <div class="text-center mb-6">
            <h1>Pay Tracker App</h1>
            <span>Monitor your monthly payments and keep your life organized 😊</span>
        </div>
        <h2 class="mb-2">Actions</h2>
        <div class="flex flex-row gap-2 overflow-y-auto no-scrollbar">
            <NuxtLink class="px-2 py-1 rounded-full text-center min-w-28" :class="{selected: route.path == '/'}" to="/">Home</NuxtLink>
            <NuxtLink class="px-2 py-1 rounded-full text-center min-w-28" :class="{selected: route.path == '/new-payment'}" to="/new-payment">Add Pay</NuxtLink>
            <NuxtLink class="px-2 py-1 rounded-full text-center min-w-28" :class="{selected: route.path == '/edit'}" to="/edit">Edit Pay</NuxtLink>
        </div>
        <slot />
    </div>
</template>

<script setup>

const user = await getCurrentUser();
const auth = useFirebaseAuth()
const route = useRoute()

// ----- Define Pinia Vars -----------
const indexStore = useIndexStore();
// TODO: Look for a way to speed up this in case the user has already fetched the data
await indexStore.fetchData();

async function signOut() {
    // Sign out from firebase
    await auth.signOut();

    // Redirect to welcome page
    await navigateTo('/welcome');
}
</script>


<style scoped>
a {
    border: 2px solid rgba(255, 255, 255, 0.35);
    padding: 5px 12px;
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