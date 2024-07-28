<template>
    <section class="w-full py-12 lg:py-24">
        <div class="container px-4 grid gap-12 lg:px-6 max-w-[80rem] px-[1.429rem] m-auto">
            <div class="space-y-3">
                <h1 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl">FAQ</h1>
                <p class="text-gray-500 md:text-xl/relaxed dark:text-gray-400">
                    Got a question? We've got answers. If you have some other questions, feel free to reach out to us
                    using the
                    <NuxtLink to="contact-us" class="underline underline-dotted">Contact Us</NuxtLink> page (For now, you need to sign in to be able to contact us).
                </p>
            </div>
            <div class="space-y-4">
                <div class="border rounded-lg">
                    <details class="border-b">
                        <summary class="p-4 list-disc">Why do I need to create an account?</summary>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800">
                            Like many websites, we require account creation to assign you a specific ID for managing
                            your payments.
                        </div>
                    </details>
                    <details class="border-b">
                        <summary class="p-4 list-disc">How much does the service cost?</summary>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800">
                            As of today ({{ $dayjs().format('YYYY-MM-DD') }}), our service is free of charge. <br>
                            However, we do not rule out the possibility of charging our users in the future, as many
                            services utilized here incur costs for us.
                        </div>
                    </details>
                    <details class="border-b">
                        <summary class="p-4 list-disc">Why aren't paid payments updated when I edit them?</summary>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800">
                            When you mark a payment as paid, it is considered a historical transaction. Therefore,
                            modifying it may lead to confusion.
                            If you wish to make changes, you must first mark the payment as unpaid, edit it, and then,
                            if applicable, mark it as paid again.
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
definePageMeta({
    layout: "landing"
})

const { $dayjs } = useNuxtApp();

useHead({
    title: 'FAQ - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Frequent asked questions for PayTrackr users.'
        }
    ]
})
</script>