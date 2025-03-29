<template>
  <div class="flex flex-col">
    <!-- Hero Section -->
    <section
      class="flex flex-col justify-between gap-[3rem] md:gap-[4.286rem] max-w-[80rem] m-auto min-h-[90vh] md:min-h-[unset] rounded-[0.571rem] p-[1.429rem]"
    >
      <div class="flex flex-col md:flex-row md:items-center gap-[3rem]">
        <div class="flex flex-col gap-[2.429rem] bg-secondary rounded-[0.571rem] p-[1.429rem]">
          <div class="flex flex-col">
            <h1 class="text-start">Simply track your payments, don't miss anything</h1>
            <span class="block">You can track your monthly and one-time payments for free</span>
          </div>
          <div class="flex justify-start items-center gap-4 max-w-[80%]">
            <NuxtLink
              to="/welcome?redirect=/"
              class="flex items-center justify-center gap-[0.287rem] bg-primary text-black rounded-md p-2"
            >
              <PhSignInBold />
              <span class="text-nowrap">Get Started</span>
            </NuxtLink>
            <span class="text-sm">Join to the great community who loves to organize</span>
          </div>
        </div>
        <div>
          <img src="/img/home_hero.svg" alt="Hero image representative of tracking payments" />
        </div>
      </div>
      <a href="/#features" class="flex flex-col items-center justify-center animate-jump">
        <span class="text-[--primary-color] font-semibold">Learn More</span>
        <PhArrowDownBold class="text-[2.5rem] text-[--primary-color]" />
      </a>
    </section>
    <!-- Features Section -->
    <section id="features" class="bg-[#B9B9B94D] py-[4rem] w-full">
      <div class="flex flex-col gap-[4rem] max-w-[80rem] m-auto px-[1.429rem]">
        <div class="flex flex-col items-center text-center">
          <h2 class="text-[1.714rem] font-semibold border-white border-opacity-35">Key Features</h2>
          <span class="">PayTrackr offers simply tools to help you stay on top of your finances.</span>
        </div>
        <div class="flex flex-col sm:flex-row gap-[3.571rem]">
          <div class="flex flex-col align-start gap-[0.857rem]">
            <div class="bg-secondary p-[0.571rem] rounded-[0.571rem] w-fit">
              <LucideCalendar class="text-[1.429rem]" />
            </div>
            <div class="flex flex-col">
              <h3 class="text-xl font-bold">Track Monthly & One-time Payments</h3>
              <span>Easily manage your recurring and one-time bills, never miss a payment again</span>
            </div>
          </div>
          <div class="flex flex-col align-start gap-[0.857rem]">
            <div class="bg-secondary p-[0.571rem] rounded-[0.571rem] w-fit">
              <SolarPieChartLinear class="text-[1.429rem]" />
            </div>
            <h3 class="text-xl font-bold">Spending Summaries & Charts</h3>
            <span>Get a clear overview of your spending with detailed summaries and visualizations</span>
          </div>
          <div class="flex flex-col align-start gap-[0.857rem]">
            <div class="bg-secondary p-[0.571rem] rounded-[0.571rem] w-fit">
              <MingcuteNotificationFill class="text-[1.429rem]" />
            </div>
            <h3 class="text-xl font-bold">Bill Reminders, Alerts & Data Exports</h3>
            <span
              >On the premium version you will never miss a bill payment again with customizable reminders, alerts &
              data exports</span
            >
          </div>
        </div>
      </div>
    </section>
    <!-- Pricing Section -->
    <section class="flex flex-col gap-[4rem] px-[1.429rem] py-[4rem] max-w-[80rem] m-auto">
      <div class="flex flex-col items-center text-center">
        <h2 class="text-[1.714rem] font-semibold border-white border-opacity-35">Pricing</h2>
        <span class="">Choose the plan that best fits your needs.</span>
      </div>
      <div class="flex flex-col md:flex-row items-center gap-[3.571rem]">
        <div
          class="flex flex-col items-center justify-center gap-[2.143rem] border px-4 py-[1.429rem] rounded-[1rem] w-fit"
        >
          <span class="font-semibold text-lg tracking-wider">Free</span>
          <div class="flex items-end gap-[0.286rem]">
            <span class="text-[1.714rem] leading-[1.714rem] font-bold tracking-wider">$0</span>
            <span class="font-semibold tracking-wide">/ month</span>
          </div>
          <span class="text-center max-w-[18.57rem]"
            >Manage up to 5 bills, view spending summaries, and get basic reminders.</span
          >
          <NuxtLink
            to="/welcome?redirect=/"
            class="flex items-center justify-center gap-[0.287rem] bg-primary text-black rounded-md p-2"
          >
            <PhSignInBold />
            <span class="text-nowrap">Sign-up for Free</span>
          </NuxtLink>
        </div>
        <div
          class="flex flex-col items-center justify-center gap-[2.143rem] border px-4 py-[1.429rem] rounded-[1rem] w-fit"
        >
          <span class="font-semibold text-lg tracking-wider">Premium</span>
          <div class="flex items-end gap-[0.286rem]">
            <span class="text-[1.714rem] leading-[1.714rem] font-bold tracking-wider">$2.5</span>
            <span class="font-semibold tracking-wide">/ month</span>
          </div>
          <span class="text-center max-w-[18.57rem]"
            >Manage unlimited bills, get advanced spending insights, export payments, and receive priority
            support.</span
          >
          <NuxtLink
            to="/welcome?redirect=/"
            class="flex items-center justify-center gap-[0.287rem] bg-primary text-black rounded-md p-2"
          >
            <LetsIconsDimondAlt />
            <span class="text-nowrap">Get Premium</span>
          </NuxtLink>
        </div>
      </div>
    </section>
    <!-- Re-engagement Section -->
    <section id="features" class="bg-[#B9B9B94D] px-[1.429rem] py-[4rem] w-full">
      <div class="flex flex-col items-center gap-[4rem] max-w-[80rem] m-auto">
        <div class="flex flex-col items-center text-center">
          <h2 class="text-[1.714rem] font-semibold border-white border-opacity-35">Take Control of Your Finances</h2>
          <span class="">Sign up for BillTracker and start managing your bills and spending with ease.</span>
        </div>
        <div class="flex gap-[2rem]">
          <NuxtLink
            to="/welcome?redirect=/"
            class="flex items-center justify-center gap-[0.287rem] bg-primary text-black rounded-md p-2"
          >
            <PhSignInBold />
            <span class="text-nowrap">Sign-up for Free</span>
          </NuxtLink>
          <NuxtLink
            to="/welcome?redirect=/"
            class="flex items-center justify-center gap-[0.287rem] bg-secondary text-white rounded-md p-2"
          >
            <IcRoundPlus />
            <span class="text-nowrap">Try Adding A Payment</span>
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>
<script setup>
import PhSignInBold from "~icons/ph/sign-in-bold";
import PhArrowDownBold from "~icons/ph/arrow-down-bold";
import LucideCalendar from "~icons/lucide/calendar";
import SolarPieChartLinear from "~icons/solar/pie-chart-linear";
import MingcuteNotificationFill from "~icons/mingcute/notification-fill";
import LetsIconsDimondAlt from "~icons/lets-icons/dimond-alt";
import IcRoundPlus from "~icons/ic/round-plus";

const user = await getCurrentUser();
const route = useRoute();

// Only redirect if:
// 1. User is authenticated
// 2. This is a direct visit to the landing page (not via link)
// 3. No "showLanding" query param is present
onMounted(() => {
  if (user && route.path === "/" && !route.query.showLanding) {
    // Check if this is the first app load in this session
    const hasVisitedBefore = sessionStorage.getItem("hasVisitedApp");

    if (!hasVisitedBefore) {
      // Mark that we've visited in this session
      sessionStorage.setItem("hasVisitedApp", "true");

      // Redirect to recurrent payments page
      navigateTo("/recurrent");
    }
  }
});

definePageMeta({
  layout: "landing"
});

useHead({
  title: "Effortless Personal Finance: Simplifying Monthly Payments Tracking",
  meta: [
    {
      name: "description",
      content:
        "Streamline your financial journey with our user-friendly platform dedicated to effortlessly tracking your personal monthly payments. Stay organized and in control of your finances with ease. Start managing your monthly payments more efficiently today!"
    }
  ]
});
</script>
