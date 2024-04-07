<template>
    <footer class="flex flex-col gap-2 sm:flex-row py-6 w-full shrink-0 items-center px-4 md:px-6 border-t">
        <p class="text-xs text-gray-500 dark:text-gray-400">© {{$dayjs().year()}} WiseUtils. All rights reserved.</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Logo Created With LogoMakr.com</p>
        <nav class="sm:ml-auto flex gap-4 sm:gap-6">
            <NuxtLink class="text-xs hover:underline underline-offset-4" to="/term-of-service">
            Terms of Service
            </NuxtLink>
        </nav>
    </footer>
</template>

<script setup>

const { $dayjs } = useNuxtApp();
</script>