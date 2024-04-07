<template>
    <div class="text-center mb-6 relative">
        <div class="md:p-4 m-auto px-0" style="margin-top: 4rem;">
            <h1 class="mb-8">Your Financial Freedom Starts Here</h1>
            <span class="subtitle block mb-8">Take control of your money with our beautifully designed platform. Track
                your spending, set budgets, and reach your financial goals.</span>
            <NuxtLink to="/welcome?redirect=/"
                class="m-2 btn inline-block w-28 hover:bg-blue-700 rounded-md font-semibold"
                style="background-color: var(--primary-bg-color); color: var(--secondary-bg-color)">Get Started
            </NuxtLink>
            <NuxtLink to="/faq"
                class="landing-button m-2 inline-block w-28 bg-transparent text-white font-medium border hover:border-transparent rounded-md">
                Learn More</NuxtLink>
        </div>
        <section>
            <div class="p-4 m-auto" style="margin-top: 8rem;">
                <h2 class="border-white border-opacity-35 ">How it works</h2>
                <span class="subtitle block mb-4">Our platform is designed to be easy to use, even for those who are new to
                    personal finance management.</span>
    
                <div class="mx-auto grid max-w-5xl items-center gap-10 lg:grid-cols-3 lg:gap-20">
                    <div class="flex flex-col items-center justify-start space-y-2 h-full">
                        <img src="/img/payments.png" width="300" height="160" alt="Image"
                            class="my-4 mx-auto aspect-video overflow-hidden rounded-xl object-cover object-center drop-shadow-lg" />
                        <div class="space-y-2">
                            <h3 class="text-xl font-bold">Track Spending</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Easily add your periodical expenses and see where your money is going.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center justify-start space-y-2 h-full">
                        <img src="/img/history.png" width="300" height="160" alt="Image"
                            class="my-4 mx-auto aspect-video overflow-hidden rounded-xl object-cover object-center drop-shadow-lg" />
                        <div class="space-y-2">
                            <h3 class="text-xl font-bold">Keep History</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Edit, delete and add custom budgets while your history is preserved.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center justify-start space-y-2 h-full">
                        <img src="/img/summary.png" width="300" height="160" alt="Image"
                            class="my-4 mx-auto aspect-video overflow-hidden rounded-xl object-cover object-center drop-shadow-lg" />
                        <div class="space-y-2">
                            <h3 class="text-xl font-bold">Look Resumes</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Watch your progress as you get closer to your goals.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section>
            <div class="container md:px-6" style="margin-top: 6rem; margin-bottom: 6rem;">
                <div class="grid gap-10 md:gap-16 lg:grid-cols-2">
                    <div class="flex flex-col space-y-4">
                        <div class="inline-block rounded-lg bg-gray-100 px-3 py-1 text-sm dark:bg-gray-800">Simplicity
                        </div>
                        <div class="h-full flex justify-center items-center">
                            <h2
                                class="lg:leading-tighter text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl xl:text-[3.4rem] 2xl:text-[3.75rem]">
                                Managing finances should be exciting, not complex.
                            </h2>
                        </div>
                        <NuxtLink 
                            class="m-auto m-2 btn inline-block w-28 hover:bg-blue-700 rounded-md font-semibold"
                            style="background-color: var(--primary-bg-color); color: var(--secondary-bg-color)"
                            to="/welcome?redirect=/"
                        >
                            Get Started
                        </NuxtLink>
                    </div>
                    <div class="flex flex-col space-y-4">
                        <div class="inline-block rounded-lg bg-gray-100 px-3 py-1 text-sm dark:bg-gray-800">Security
                        </div>
                        <p class="mx-auto max-w-[700px] text-gray-500 md:text-xl/relaxed dark:text-gray-400">
                            Our fully managed infrastructure is built with security in mind. With a global edge network service, we ensure your information is fortified against potential threats.
                        </p>
                        <NuxtLink class="m-auto landing-button m-2 w-28 bg-transparent text-white font-medium border hover:border-transparent rounded-md"
                            to="/faq" >
                            Learn more
                        </NuxtLink>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
<script setup>
definePageMeta({
    layout: "landing"
})

useHead({
    title: 'Effortless Personal Finance: Simplifying Monthly Payments Tracking',
    meta: [
        {
            name: 'description',
            content: 'Streamline your financial journey with our user-friendly platform dedicated to effortlessly tracking your personal monthly payments. Stay organized and in control of your finances with ease. Start managing your monthly payments more efficiently today!'
        }
    ]
})
</script>

<style scoped>
.landing-button:hover {
    background-color: #a0a4d9;
    border: 1px solid #a0a4d9;
    font-weight: bold;
    color: #1f2023;
}

.landing-button {
    transition: 0.2s;
}

h1 {
    font-size: 40px;
}

h2 {
    font-size: 36px;
}

.subtitle {
    font-size: 20px;
    color: rgb(197, 197, 197);
}

@media (max-width: 767px) {

    h1,
    h2 {
        font-size: 30px;
    }

    .subtitle {
        font-size: 16px;
    }
}
</style>