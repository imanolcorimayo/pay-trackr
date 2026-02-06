<template>
  <div class="weekly-summary-page">
    <!-- Page Header -->
    <div class="flex flex-col gap-2 p-3 mb-6">
      <h1 class="text-2xl font-bold">Resumen Semanal</h1>
      <p class="text-sm text-gray-400">Tu resumen de pagos de esta semana y progreso del mes</p>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="isLoading" class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-3 animate-pulse">
      <div class="bg-base rounded-xl border border-gray-600 p-4">
        <div class="h-6 w-40 bg-gray-700 rounded mb-4"></div>
        <div class="grid grid-cols-2 gap-3">
          <div v-for="i in 6" :key="i" class="h-20 bg-gray-700/50 rounded-lg"></div>
        </div>
      </div>
      <div class="bg-base rounded-xl border border-gray-600 p-4">
        <div class="h-6 w-48 bg-gray-700 rounded mb-4"></div>
        <div class="space-y-3">
          <div v-for="i in 4" :key="i" class="h-14 bg-gray-700/50 rounded-lg"></div>
        </div>
      </div>
      <div class="lg:col-span-2 bg-base rounded-xl border border-gray-600 p-4">
        <div class="h-6 w-44 bg-gray-700 rounded mb-4"></div>
        <div class="h-8 bg-gray-700/50 rounded-full"></div>
      </div>
    </div>

    <!-- Content -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-3">
      <!-- Card 1: Esta Semana -->
      <div class="bg-base rounded-xl border border-gray-600 shadow-sm shadow-white/5 p-4">
        <h2 class="text-lg font-semibold mb-4 flex items-center">
          <MdiCalendarWeek class="mr-2 text-primary" />
          Esta Semana
        </h2>

        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col p-3 bg-gray-700/40 rounded-lg">
            <span class="text-xs text-gray-400">Vencimientos</span>
            <span class="text-xl font-bold">{{ stats.dueThisWeekCount }}</span>
            <span class="text-xs text-gray-500">pago(s) esta semana</span>
          </div>

          <div class="flex flex-col p-3 bg-gray-700/40 rounded-lg">
            <span class="text-xs text-gray-400">Monto Semanal</span>
            <span class="text-xl font-bold">{{ formatPrice(stats.dueThisWeekAmount) }}</span>
            <span class="text-xs text-gray-500">total semanal</span>
          </div>

          <div class="flex flex-col p-3 bg-gray-700/40 rounded-lg">
            <span class="text-xs text-gray-400">Pagados (Mes)</span>
            <span class="text-xl font-bold text-success">{{ stats.paidThisMonth }}</span>
            <span class="text-xs text-gray-500">completados</span>
          </div>

          <div class="flex flex-col p-3 bg-gray-700/40 rounded-lg">
            <span class="text-xs text-gray-400">Pendientes (Mes)</span>
            <span class="text-xl font-bold text-warning">{{ stats.unpaidThisMonth }}</span>
            <span class="text-xs text-gray-500">por pagar</span>
          </div>

          <div class="flex flex-col p-3 bg-gray-700/40 rounded-lg">
            <span class="text-xs text-gray-400">Total Pagado</span>
            <span class="text-xl font-bold">{{ formatPrice(stats.totalPaidAmount) }}</span>
            <span class="text-xs text-gray-500">este mes</span>
          </div>

          <div class="flex flex-col p-3 bg-gray-700/40 rounded-lg">
            <span class="text-xs text-gray-400">Gastos Unicos</span>
            <span class="text-xl font-bold">{{ stats.oneTimeCount }}</span>
            <span class="text-xs text-gray-500">{{ formatPrice(stats.oneTimeAmount) }}</span>
          </div>
        </div>
      </div>

      <!-- Card 2: Pagos de esta Semana -->
      <div class="bg-base rounded-xl border border-gray-600 shadow-sm shadow-white/5 p-4">
        <h2 class="text-lg font-semibold mb-4 flex items-center">
          <MdiCreditCardClock class="mr-2 text-primary" />
          Pagos de esta Semana
        </h2>

        <div v-if="dueThisWeek.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-400">
          <MdiCheckCircle class="text-4xl mb-2 text-success" />
          <p>No hay pagos por vencer esta semana</p>
        </div>

        <div v-else class="space-y-3 max-h-[360px] overflow-y-auto">
          <div
            v-for="payment in dueThisWeek"
            :key="payment.id"
            class="flex items-center justify-between p-3 bg-gray-700/40 rounded-lg"
          >
            <div class="flex items-center gap-3">
              <div
                class="w-1.5 h-10 rounded-full"
                :style="{ backgroundColor: getCategoryColor(payment.categoryId) }"
              ></div>
              <div>
                <p class="font-medium text-sm">{{ payment.title }}</p>
                <p class="text-xs text-gray-400">Vence el {{ payment.dueDateDay }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-sm">{{ formatPrice(payment.amount) }}</span>
              <span
                class="inline-flex items-center justify-center text-xs font-medium px-2 py-0.5 rounded-full"
                :class="payment.isPaidThisMonth ? 'bg-success/20 text-success' : 'bg-warning/20 text-warning'"
              >
                {{ payment.isPaidThisMonth ? 'Pagado' : 'Pendiente' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 3: Progreso del Mes -->
      <div class="lg:col-span-2 bg-base rounded-xl border border-gray-600 shadow-sm shadow-white/5 p-4">
        <h2 class="text-lg font-semibold mb-2 flex items-center">
          <MdiProgressCheck class="mr-2 text-primary" />
          Progreso del Mes
        </h2>
        <p class="text-sm text-gray-400 mb-4">
          {{ stats.paidThisMonth }} de {{ stats.paidThisMonth + stats.unpaidThisMonth }} pagos completados
        </p>

        <div
          class="w-full h-6 bg-gray-700 rounded-full overflow-hidden"
          role="progressbar"
          :aria-valuenow="progressPercent"
          aria-valuemin="0"
          aria-valuemax="100"
          :aria-label="`${progressPercent}% de pagos completados`"
        >
          <div
            class="h-full rounded-full transition-all duration-500"
            :class="progressBarColor"
            :style="`width: ${progressPercent}%`"
          ></div>
        </div>
        <div class="flex justify-between mt-2 text-sm">
          <span class="text-gray-400">{{ progressPercent }}% completado</span>
          <span class="text-gray-400">
            {{ formatPrice(stats.totalPaidAmount) }} pagado
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import MdiCalendarWeek from '~icons/mdi/calendar-week';
import MdiCreditCardClock from '~icons/mdi/credit-card-clock';
import MdiCheckCircle from '~icons/mdi/check-circle';
import MdiProgressCheck from '~icons/mdi/progress-check';

definePageMeta({
  middleware: ['auth']
});

const { $dayjs } = useNuxtApp();

const recurrentStore = useRecurrentStore();
const paymentStore = usePaymentStore();
const categoryStore = useCategoryStore();
const { getPaymentInstances } = storeToRefs(recurrentStore);
const { getPayments } = storeToRefs(paymentStore);

const isLoading = ref(true);

// ---- Computed: stats ----
const now = $dayjs();
const todayDay = now.date();
const sevenDaysDay = now.add(7, 'day').date();
const sevenDaysMonth = now.add(7, 'day').month();
const currentMonth = now.month();

// Recurrents due this week
const dueThisWeek = computed(() => {
  const recurrents = recurrentStore.getRecurrentPayments;
  const instances = getPaymentInstances.value;

  // Build a set of recurrentIds that are paid this month
  const paidThisMonth = new Set();
  instances.forEach(inst => {
    if (inst.isPaid && inst.recurrentId) {
      const instDate = inst.dueDate || inst.createdAt;
      if (instDate) {
        const d = $dayjs(instDate.toDate ? instDate.toDate() : instDate);
        if (d.month() === currentMonth && d.year() === now.year()) {
          paidThisMonth.add(inst.recurrentId);
        }
      }
    }
  });

  return recurrents
    .filter(r => {
      // Skip expired
      if (r.endDate) {
        const end = $dayjs(r.endDate);
        if (end.isBefore(now)) return false;
      }

      const dueDay = parseInt(r.dueDateDay);
      if (currentMonth === sevenDaysMonth) {
        return dueDay >= todayDay && dueDay <= sevenDaysDay;
      } else {
        const daysInMonth = now.daysInMonth();
        return (dueDay >= todayDay && dueDay <= daysInMonth) || (dueDay >= 1 && dueDay <= sevenDaysDay);
      }
    })
    .map(r => ({
      ...r,
      isPaidThisMonth: paidThisMonth.has(r.id)
    }))
    .sort((a, b) => parseInt(a.dueDateDay) - parseInt(b.dueDateDay));
});

// Current month payments (one-time)
const currentMonthOneTime = computed(() => {
  return getPayments.value.filter(p => {
    if (p.paymentType === 'recurrent') return false;
    const dateField = p.dueDate || p.createdAt;
    if (!dateField) return false;
    const d = $dayjs(dateField.toDate ? dateField.toDate() : dateField);
    return d.month() === currentMonth && d.year() === now.year();
  });
});

// Current month recurrent instances
const currentMonthInstances = computed(() => {
  return getPaymentInstances.value.filter(inst => {
    const dateField = inst.dueDate || inst.createdAt;
    if (!dateField) return false;
    const d = $dayjs(dateField.toDate ? dateField.toDate() : dateField);
    return d.month() === currentMonth && d.year() === now.year();
  });
});

const stats = computed(() => {
  const paidInstances = currentMonthInstances.value.filter(p => p.isPaid);
  const unpaidInstances = currentMonthInstances.value.filter(p => !p.isPaid);

  // Recurrents without an instance this month count as unpaid
  const instanceRecurrentIds = new Set(currentMonthInstances.value.map(p => p.recurrentId));
  const allRecurrents = recurrentStore.getRecurrentPayments.filter(r => {
    if (r.endDate && $dayjs(r.endDate).isBefore(now)) return false;
    return true;
  });
  const noInstanceCount = allRecurrents.filter(r => !instanceRecurrentIds.has(r.id)).length;

  const totalPaidAmount = paidInstances.reduce((sum, p) => sum + (p.amount || 0), 0);
  const oneTimeAmount = currentMonthOneTime.value.reduce((sum, p) => sum + (p.amount || 0), 0);

  return {
    dueThisWeekCount: dueThisWeek.value.length,
    dueThisWeekAmount: dueThisWeek.value.reduce((sum, r) => sum + (r.amount || 0), 0),
    paidThisMonth: paidInstances.length,
    unpaidThisMonth: unpaidInstances.length + noInstanceCount,
    totalPaidAmount,
    oneTimeCount: currentMonthOneTime.value.length,
    oneTimeAmount
  };
});

const progressPercent = computed(() => {
  const total = stats.value.paidThisMonth + stats.value.unpaidThisMonth;
  if (total === 0) return 0;
  return Math.round((stats.value.paidThisMonth / total) * 100);
});

const progressBarColor = computed(() => {
  if (progressPercent.value > 80) return 'bg-success';
  if (progressPercent.value > 50) return 'bg-warning';
  return 'bg-danger';
});

// ---- Helpers ----
function getCategoryColor(categoryId) {
  if (!categoryId) return '#808080';
  return categoryStore.getCategoryColor(categoryId);
}

// ---- Fetch Data ----
async function fetchData() {
  isLoading.value = true;
  try {
    await categoryStore.fetchCategories();

    await Promise.all([
      paymentStore.fetchPayments({
        startDate: now.startOf('month').toDate(),
        endDate: now.endOf('month').toDate()
      }),
      (async () => {
        await recurrentStore.fetchRecurrentPayments();
        await recurrentStore.fetchPaymentInstances(1);
      })()
    ]);
  } catch (error) {
    console.error('Error fetching weekly summary data:', error);
    useToast('error', 'Error al cargar el resumen semanal');
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => {
  fetchData();
});

useHead({
  title: 'Resumen Semanal - PayTrackr',
  meta: [
    {
      name: 'description',
      content: 'Resumen semanal de tus pagos y progreso mensual en PayTrackr.'
    }
  ]
});
</script>
