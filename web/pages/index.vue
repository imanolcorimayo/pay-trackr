<template>
  <div class="flex flex-col">
    <!-- Hero Section -->
    <section
      class="flex flex-col justify-between gap-12 md:gap-16 max-w-7xl m-auto min-h-[90vh] md:min-h-[unset] rounded-lg p-6"
    >
      <div class="flex flex-col md:flex-row md:items-center gap-12">
        <div class="flex flex-col gap-10 bg-secondary rounded-lg p-6">
          <div class="flex flex-col">
            <h1 class="text-start">Seguí tus pagos de forma simple, no te pierdas de nada</h1>
            <span class="block">Podés seguir tus pagos mensuales y únicos gratis</span>
          </div>
          <div class="flex justify-start items-center gap-4 max-w-[80%]">
            <NuxtLink
              to="/welcome?redirect=/"
              class="flex items-center justify-center gap-1 bg-primary text-black rounded-md p-2"
            >
              <PhSignInBold />
              <span class="text-nowrap">Comenzar</span>
            </NuxtLink>
            <span class="text-sm">Unite a la comunidad que ama organizarse</span>
          </div>
        </div>
        <div>
          <img src="/img/home_hero.svg" alt="Hero image representative of tracking payments" />
        </div>
      </div>
      <a href="/#features" class="flex flex-col items-center justify-center animate-jump">
        <span class="text-primary font-semibold">Conocer Más</span>
        <PhArrowDownBold class="text-4xl text-primary" />
      </a>
    </section>
    <!-- Features Section -->
    <section id="features" class="bg-white/5 py-16 w-full">
      <div class="flex flex-col gap-16 max-w-7xl m-auto px-6">
        <div class="flex flex-col items-center text-center">
          <h2 class="text-2xl font-semibold border-white border-opacity-35">Características</h2>
          <span class="">PayTrackr ofrece herramientas simples para ayudarte a controlar tus finanzas.</span>
        </div>
        <div class="flex flex-col sm:flex-row gap-14">
          <div class="flex flex-col align-start gap-3.5">
            <div class="bg-secondary p-2.5 rounded-lg w-fit">
              <LucideCalendar class="text-xl" />
            </div>
            <div class="flex flex-col">
              <h3 class="text-xl font-bold">Seguí Pagos Mensuales y Únicos</h3>
              <span>Gestioná fácilmente tus pagos recurrentes y únicos, nunca más te pierdas un pago</span>
            </div>
          </div>
          <div class="flex flex-col align-start gap-3.5">
            <div class="bg-secondary p-2.5 rounded-lg w-fit">
              <SolarPieChartLinear class="text-xl" />
            </div>
            <h3 class="text-xl font-bold">Resúmenes y Gráficos de Gastos</h3>
            <span>Obtené una visión clara de tus gastos con resúmenes detallados y visualizaciones</span>
          </div>
          <div class="flex flex-col align-start gap-3.5">
            <div class="bg-secondary p-2.5 rounded-lg w-fit">
              <MingcuteNotificationFill class="text-xl" />
            </div>
            <h3 class="text-xl font-bold">Recordatorios, Alertas y Exportación de Datos</h3>
            <span
              >En la versión premium nunca más te vas a perder un pago con recordatorios personalizables, alertas y
              exportación de datos</span
            >
          </div>
        </div>
      </div>
    </section>
    <!-- Pricing Section -->
    <section class="flex flex-col gap-16 px-6 py-16 max-w-7xl m-auto">
      <div class="flex flex-col items-center text-center">
        <h2 class="text-2xl font-semibold border-white border-opacity-35">Precios</h2>
        <span class="">Elegí el plan que mejor se adapte a tus necesidades.</span>
      </div>
      <div class="flex flex-col md:flex-row items-center gap-14">
        <div
          class="flex flex-col items-center justify-center gap-8 border px-4 py-6 rounded-2xl w-fit"
        >
          <span class="font-semibold text-lg tracking-wider">Gratis</span>
          <div class="flex items-end gap-1">
            <span class="text-2xl leading-7 font-bold tracking-wider">$0</span>
            <span class="font-semibold tracking-wide">/ mes</span>
          </div>
          <span class="text-center max-w-xs"
            >Gestioná hasta 20 pagos recurrentes y únicos, mirá resúmenes de gastos y visualizaciones básicas.</span
          >
          <NuxtLink
            to="/welcome?redirect=/"
            class="flex items-center justify-center gap-1 bg-primary text-black rounded-md p-2"
          >
            <PhSignInBold />
            <span class="text-nowrap">Registrarse Gratis</span>
          </NuxtLink>
        </div>
        <div
          class="flex flex-col items-center justify-center gap-8 border px-4 py-6 rounded-2xl w-fit"
        >
          <span class="font-semibold text-lg tracking-wider">Premium</span>
          <div class="flex items-end gap-1">
            <span class="text-2xl leading-7 font-bold tracking-wider">$10.000</span>
            <span class="font-semibold tracking-wide">/ mes</span>
          </div>
          <span class="text-center max-w-xs"
            >Pagos ilimitados, análisis avanzados, exportación de datos, recordatorios personalizados y soporte
            prioritario.</span
          >
          <NuxtLink
            to="/welcome?redirect=/"
            class="flex items-center justify-center gap-1 bg-primary text-black rounded-md p-2"
          >
            <LetsIconsDimondAlt />
            <span class="text-nowrap">Obtener Premium</span>
          </NuxtLink>
        </div>
      </div>
    </section>
    <!-- Re-engagement Section -->
    <section id="features" class="bg-white/5 px-6 py-16 w-full">
      <div class="flex flex-col items-center gap-16 max-w-7xl m-auto">
        <div class="flex flex-col items-center text-center">
          <h2 class="text-2xl font-semibold border-white border-opacity-35">Tomá el Control de tus Finanzas</h2>
          <span class="">Registrate en PayTrackr y empezá a gestionar tus pagos y gastos fácilmente.</span>
        </div>
        <div class="flex gap-8">
          <NuxtLink
            to="/welcome?redirect=/"
            class="flex items-center justify-center gap-1 bg-primary text-black rounded-md p-2"
          >
            <PhSignInBold />
            <span class="text-nowrap">Registrarse Gratis</span>
          </NuxtLink>
          <NuxtLink
            to="/welcome?redirect=/"
            class="flex items-center justify-center gap-1 bg-secondary text-white rounded-md p-2"
          >
            <IcRoundPlus />
            <span class="text-nowrap">Probá Agregar un Pago</span>
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
import { getCurrentUserAsync } from '~/utils/firebase';

const user = await getCurrentUserAsync();
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
  title: "Finanzas Personales Sin Esfuerzo: Simplificá el Seguimiento de tus Pagos",
  meta: [
    {
      name: "description",
      content:
        "Simplificá tu gestión financiera con nuestra plataforma amigable dedicada a seguir tus pagos mensuales personales. Mantenete organizado y en control de tus finanzas fácilmente. ¡Empezá a gestionar tus pagos mensuales de forma más eficiente hoy!"
    }
  ]
});
</script>
