<template>
  <div class="summary-page">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-3 mb-6">
      <h1 class="text-2xl font-bold">Financial Summary</h1>
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500">Date Range:</span>
        <select
          v-model="monthsToDisplay"
          @change="async () => await updateCharts()"
          class="px-3 py-1.5 rounded-lg border border-gray-300 bg-gray-800 focus:ring-2 focus:ring-primary focus:border-transparent"
        >
          <option value="3">Last 3 months</option>
          <option value="6">Last 6 months</option>
          <option value="12">Last 12 months</option>
          <option value="24">Last 2 years</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <Loader v-if="isLoading" />

    <!-- Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-3">
      <!-- Monthly Spending Trends -->
      <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-100 p-4">
        <h2 class="text-lg font-semibold mb-2 flex items-center">
          <MdiChartLine class="mr-2 text-primary" />
          Monthly Spending Trends
        </h2>
        <p class="text-sm text-gray-500 mb-4">Compare your monthly recurring and one-time expenses</p>
        <div class="relative h-[350px]">
          <canvas id="monthlyTrendsChart"></canvas>
        </div>
      </div>

      <!-- Spending Distribution -->
      <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-100 p-4">
        <h2 class="text-lg font-semibold mb-2 flex items-center">
          <MdiChartPie class="mr-2 text-primary" />
          Spending Distribution
        </h2>
        <div class="flex justify-between items-center mb-4">
          <p class="text-sm text-gray-500">See how your money is distributed across categories</p>
          <select
            v-model="selectedMonth"
            @change="updateCategoryPieChart"
            class="px-2 py-1 text-sm rounded-lg border border-gray-300 bg-gray-800 focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option v-for="month in availableMonths" :key="month.value" :value="month.value">
              {{ month.label }}
            </option>
          </select>
        </div>
        <div v-if="!categoryDataExists" class="flex flex-col items-center justify-center h-[350px] text-gray-400">
          <MdiChartDonut class="text-5xl mb-3" />
          <p>No payment data for this month</p>
        </div>
        <div v-else class="relative h-[350px]">
          <canvas id="categoryPieChart"></canvas>
        </div>
      </div>

      <!-- Key Statistics -->
      <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-100 p-4">
        <h2 class="text-lg font-semibold mb-2 flex items-center">
          <MdiChartMultiple class="mr-2 text-primary" />
          Key Statistics
        </h2>
        <p class="text-sm text-gray-500 mb-4">Your financial insights at a glance</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Average Monthly Spend -->
          <div class="flex flex-col p-3 bg-gray-50 rounded-lg">
            <span class="text-xs text-gray-500">Average Monthly Spend</span>
            <span class="text-xl font-bold text-gray-800">{{ formatPrice(stats.averageMonthlySpend) }}</span>
            <div class="text-sm mt-1">
              <span :class="stats.averageChangePercent >= 0 ? 'text-danger' : 'text-success'">
                {{ stats.averageChangePercent >= 0 ? "↑" : "↓" }} {{ Math.abs(stats.averageChangePercent).toFixed(1) }}%
              </span>
              <span class="text-gray-500 ml-1">vs previous {{ monthsToDisplay }} months</span>
            </div>
          </div>

          <!-- Recurring vs One-time -->
          <div class="flex flex-col p-3 bg-gray-50 rounded-lg">
            <span class="text-xs text-gray-500">Recurring vs One-time</span>
            <div class="flex items-center gap-2">
              <span class="text-xl font-bold text-gray-800">{{ stats.recurringPercentage }}%</span>
              <span class="text-sm text-gray-500">recurring</span>
            </div>
            <div class="w-full h-2 bg-gray-200 rounded-full mt-2 overflow-hidden">
              <div class="h-full bg-primary rounded-full" :style="`width: ${stats.recurringPercentage}%`"></div>
            </div>
          </div>

          <!-- Top Expense Category -->
          <div class="flex flex-col p-3 bg-gray-50 rounded-lg">
            <span class="text-xs text-gray-500">Top Expense Category</span>
            <span class="capitalize text-xl font-bold text-gray-800">{{ stats.topCategory }}</span>
            <span class="text-sm text-gray-500">{{ stats.topCategoryPercentage }}% of total spending</span>
          </div>

          <!-- Payment Completion Rate -->
          <div class="flex flex-col p-3 bg-gray-50 rounded-lg">
            <span class="text-xs text-gray-500">Payment Completion Rate</span>
            <span class="text-xl font-bold text-gray-800">{{ stats.paymentCompletionRate }}%</span>
            <div class="w-full h-2 bg-gray-200 rounded-full mt-2 overflow-hidden">
              <div
                class="h-full rounded-full"
                :class="
                  stats.paymentCompletionRate > 80
                    ? 'bg-success'
                    : stats.paymentCompletionRate > 50
                    ? 'bg-warning'
                    : 'bg-danger'
                "
                :style="`width: ${stats.paymentCompletionRate}%`"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Breakdown -->
      <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-100 p-4">
        <h2 class="text-lg font-semibold mb-2 flex items-center">
          <MdiCreditCardOutline class="mr-2 text-primary" />
          Payment Breakdown
        </h2>
        <p class="text-sm text-gray-500 mb-4">See your biggest expenses for {{ selectedMonthName }}</p>

        <div v-if="!topPayments.length" class="flex flex-col items-center justify-center h-[350px] text-gray-400">
          <MdiCreditCardOff class="text-5xl mb-3" />
          <p>No payment data for this month</p>
        </div>
        <div v-else>
          <div class="overflow-hidden">
            <div v-for="(payment, index) in topPayments" :key="payment.id" class="mb-3">
              <div class="flex justify-between items-center mb-1">
                <div class="flex items-center">
                  <div class="w-2 h-10 rounded-full mr-3" :class="`bg-${payment.category.toLowerCase()}`"></div>
                  <div>
                    <p class="font-medium">{{ payment.title }}</p>
                    <p class="text-xs text-gray-500">{{ payment.isRecurring ? "Recurring" : "One-time" }}</p>
                  </div>
                </div>
                <p class="font-semibold">{{ formatPrice(payment.amount) }}</p>
              </div>
              <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                  class="h-full bg-primary rounded-full"
                  :style="`width: ${(payment.amount / topPayments[0].amount) * 100}%`"
                ></div>
              </div>
            </div>
          </div>

          <div class="mt-4 text-center" v-if="hasMorePayments">
            <button @click="showAllPayments = !showAllPayments" class="text-primary text-sm hover:underline">
              {{ showAllPayments ? "Show less" : "Show all payments" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Chart, registerables } from "chart.js";
import MdiChartLine from "~icons/mdi/chart-line";
import MdiChartPie from "~icons/mdi/chart-pie";
import MdiChartDonut from "~icons/mdi/chart-donut";
import MdiChartMultiple from "~icons/mdi/chart-multiple";
import MdiCreditCardOutline from "~icons/mdi/credit-card-outline";
import MdiCreditCardOff from "~icons/mdi/credit-card-off";

// Register Chart.js components
Chart.register(...registerables);

definePageMeta({
  middleware: ["auth"]
});

// ----- Define Useful Properties ---------
const { $dayjs } = useNuxtApp();

// ----- Define Pinia Vars ----------
const paymentStore = usePaymentStore();
const recurrentStore = useRecurrentStore();
const { getPayments } = storeToRefs(paymentStore);

// ----- Define Refs ---------
const isLoading = ref(true);
const monthsToDisplay = ref(6);
const selectedMonth = ref("");
const selectedMonthName = ref("");
const availableMonths = ref([]);
const categoryDataExists = ref(true);
var monthlyTrendsChart = null;
var categoryPieChart = null;
const showAllPayments = ref(false);
const topPayments = ref([]);
const allMonthlyPayments = ref([]);
const hasMorePayments = ref(false);

// Statistics
const stats = ref({
  averageMonthlySpend: 0,
  averageChangePercent: 0,
  recurringPercentage: 0,
  topCategory: "none",
  topCategoryPercentage: 0,
  paymentCompletionRate: 0
});

// ----- Define Computed ---------
const combinedPayments = computed(() => {
  // Process all one-time payments
  const oneTimePayments = getPayments.value.map((payment) => ({
    ...payment,
    isRecurring: payment.paymentType === "recurrent"
  }));

  // For type recurrent, update the category based on the recurrent payments
  oneTimePayments.forEach((payment) => {
    if (payment.isRecurring) {
      const recurrentPayment = recurrentStore.getRecurrentPayments.find((rec) => rec.id === payment.recurrentId);
      if (recurrentPayment) {
        payment.category = recurrentPayment.category;
      }
    }
  });

  // Combine and return all payments
  return [...oneTimePayments];
});

// ----- Define Methods ---------
function formatPrice(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 2
  }).format(amount || 0);
}

// Get monthly data points for the charts
function getMonthlyData() {
  const months = [];
  const recurringData = [];
  const oneTimeData = [];
  const totalData = [];

  // Generate an array of months for the chart
  for (let i = 0; i < monthsToDisplay.value; i++) {
    const monthDate = $dayjs().subtract(i, "month");
    months.unshift(monthDate.format("MMM YYYY"));

    // Initialize totals for this month
    recurringData.unshift(0);
    oneTimeData.unshift(0);
    totalData.unshift(0);
  }

  // Calculate the start date for our data range
  const startDate = $dayjs()
    .subtract(monthsToDisplay.value - 1, "month")
    .startOf("month");

  // Process all payments
  combinedPayments.value.forEach((payment) => {
    if (!payment.createdAt) return;

    const paymentDate = $dayjs(payment.createdAt.toDate());

    // Only include payments within our date range
    if (paymentDate.isBefore(startDate)) return;

    // Find the index in our months array
    const monthIndex = months.findIndex((month) => {
      return month === paymentDate.format("MMM YYYY");
    });

    if (monthIndex !== -1) {
      if (payment.isRecurring) {
        recurringData[monthIndex] += payment.amount;
      } else {
        oneTimeData[monthIndex] += payment.amount;
      }
      totalData[monthIndex] += payment.amount;
    }
  });

  return { months, recurringData, oneTimeData, totalData };
}

// Generate the monthly trends chart
async function createMonthlyTrendsChart() {
  const { months, recurringData, oneTimeData, totalData } = getMonthlyData();
  const ctx = document.getElementById("monthlyTrendsChart");

  if (!ctx) return;

  // Prepare chart data
  const chartData = {
    labels: months,
    datasets: [
      {
        label: "Total",
        data: totalData,
        borderColor: "rgb(54, 162, 235)",
        backgroundColor: "rgba(54, 162, 235, 0.1)",
        fill: true,
        tension: 0.3,
        pointBackgroundColor: "rgb(54, 162, 235)",
        pointRadius: 4,
        borderWidth: 3,
        order: 3
      },
      {
        label: "Recurring",
        data: recurringData,
        borderColor: "rgb(75, 192, 192)",
        backgroundColor: "rgba(75, 192, 192, 0.1)",
        fill: true,
        tension: 0.3,
        pointBackgroundColor: "rgb(75, 192, 192)",
        pointRadius: 4,
        borderWidth: 3,
        order: 1
      },
      {
        label: "One-time",
        data: oneTimeData,
        borderColor: "rgb(255, 99, 132)",
        backgroundColor: "rgba(255, 99, 132, 0.1)",
        fill: true,
        tension: 0.3,
        pointBackgroundColor: "rgb(255, 99, 132)",
        pointRadius: 4,
        borderWidth: 3,
        order: 2
      }
    ]
  };

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: "index",
      intersect: false
    },
    plugins: {
      legend: {
        position: "top",
        labels: {
          color: "#d1d5db", // gray-300 for dark theme
          boxWidth: 12,
          usePointStyle: true,
          pointStyle: "circle"
        }
      },
      tooltip: {
        usePointStyle: true,
        callbacks: {
          label: function (context) {
            const label = context.dataset.label || "";
            const value = formatPrice(context.raw);
            return `${label}: ${value}`;
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          color: "#9ca3af", // gray-400 for dark theme
          callback: function (value) {
            return formatPrice(value);
          }
        },
        grid: {
          color: "rgba(107, 114, 128, 0.2)" // gray-500 with opacity
        }
      },
      x: {
        ticks: {
          color: "#9ca3af" // gray-400 for dark theme
        },
        grid: {
          display: false
        }
      }
    }
  };

  try {
    // If chart already exists, update the data
    if (monthlyTrendsChart) {
      monthlyTrendsChart.data = chartData;
      monthlyTrendsChart.options = chartOptions;
      monthlyTrendsChart.update();
    } else {
      // Create a new chart
      monthlyTrendsChart = new Chart(ctx, {
        type: "line",
        data: chartData,
        options: chartOptions
      });
    }
  } catch (error) {
    console.error("Error updating monthly trends chart:", error);

    // If we encounter an error, try to fully recreate the chart
    try {
      if (monthlyTrendsChart) {
        monthlyTrendsChart.destroy();
      }

      // Wait for the next tick to ensure the canvas is properly cleared
      await nextTick();

      // Create a new chart instance
      monthlyTrendsChart = new Chart(ctx, {
        type: "line",
        data: chartData,
        options: chartOptions
      });
    } catch (fallbackError) {
      console.error("Failed to recreate chart after error:", fallbackError);
      useToast("error", "Failed to render charts");
    }
  }
}

// Generate the category distribution pie chart
async function updateCategoryPieChart() {
  const selectedMonthDate = $dayjs(selectedMonth.value, "YYYY-MM");
  selectedMonthName.value = selectedMonthDate.format("MMMM YYYY");

  // Filter payments for the selected month
  const monthPayments = combinedPayments.value.filter((payment) => {
    if (!payment.createdAt) return false;
    const paymentDate = $dayjs(payment.createdAt.toDate());
    return paymentDate.month() === selectedMonthDate.month() && paymentDate.year() === selectedMonthDate.year();
  });

  allMonthlyPayments.value = [...monthPayments].sort((a, b) => b.amount - a.amount);

  // Get top 5 payments for the breakdown section
  topPayments.value = allMonthlyPayments.value.slice(0, 5);
  hasMorePayments.value = allMonthlyPayments.value.length > 5;

  // Check if we have data
  if (monthPayments.length === 0) {
    categoryDataExists.value = false;
    return;
  }

  categoryDataExists.value = true;

  // Group payments by category
  const categorySums = {};

  monthPayments.forEach((payment) => {
    const category = payment.category || "other";
    if (!categorySums[category]) {
      categorySums[category] = 0;
    }
    categorySums[category] += payment.amount;
  });

  // Convert to arrays for the chart
  const categories = Object.keys(categorySums);
  const amounts = Object.values(categorySums);

  // Color mapping for categories
  const categoryColors = {
    housing: "rgb(70, 130, 180)", // steel blue
    utilities: "rgb(0, 114, 223)", // accent blue
    food: "rgb(29, 154, 56)", // success green
    dining: "rgb(255, 99, 71)", // tomato red
    transport: "rgb(230, 174, 44)", // warning yellow
    entertainment: "rgb(97, 88, 255)", // secondary purple
    health: "rgb(232, 74, 138)", // danger pink
    pet: "rgb(60, 174, 163)", // teal for pets
    clothes: "rgb(128, 0, 32)", // burgundy
    traveling: "rgb(255, 140, 0)", // dark orange
    education: "rgb(147, 112, 219)", // medium purple
    subscriptions: "rgb(32, 178, 170)", // light sea green
    taxes: "rgb(139, 69, 19)", // brown
    other: "rgb(128, 128, 128)" // gray for other/default
  };

  const backgroundColors = categories.map((category) => categoryColors[category] || "rgb(201, 203, 207)");

  const ctx = document.getElementById("categoryPieChart");
  if (!ctx) return;

  // Prepare chart data
  const chartData = {
    labels: categories.map((c) => c.charAt(0).toUpperCase() + c.slice(1)),
    datasets: [
      {
        data: amounts,
        backgroundColor: backgroundColors,
        borderWidth: 1
      }
    ]
  };

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: "right",
        labels: {
          color: "#d1d5db", // gray-300 for dark theme
          boxWidth: 12,
          usePointStyle: true,
          pointStyle: "circle"
        }
      },
      tooltip: {
        callbacks: {
          label: function (context) {
            const value = formatPrice(context.raw);
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = Math.round((context.raw / total) * 100);
            return `${value} (${percentage}%)`;
          }
        }
      }
    }
  };

  try {
    // If chart already exists, update the data
    if (categoryPieChart) {
      categoryPieChart.data = chartData;
      categoryPieChart.options = chartOptions;
      categoryPieChart.update();
    } else {
      // Create a new chart
      categoryPieChart = new Chart(ctx, {
        type: "doughnut",
        data: chartData,
        options: chartOptions
      });
    }
  } catch (error) {
    console.error("Error updating category pie chart:", error);

    // If we encounter an error, try to fully recreate the chart
    try {
      if (categoryPieChart) {
        categoryPieChart.destroy();
      }

      // Wait for the next tick to ensure the canvas is properly cleared
      await nextTick();

      // Create a new chart instance
      categoryPieChart = new Chart(ctx, {
        type: "doughnut",
        data: chartData,
        options: chartOptions
      });
    } catch (fallbackError) {
      console.error("Failed to recreate chart after error:", fallbackError);
      useToast("error", "Failed to render category chart");
    }
  }
}

// Calculate statistics
function calculateStatistics() {
  const { months, recurringData, oneTimeData, totalData } = getMonthlyData();

  // Calculate average monthly spend
  stats.value.averageMonthlySpend = totalData.reduce((sum, amount) => sum + amount, 0) / totalData.length;

  // Calculate percentage change
  const currentPeriodTotal = totalData
    .slice(-Math.min(totalData.length, monthsToDisplay.value))
    .reduce((sum, amount) => sum + amount, 0);
  const previousPeriodTotal = totalData
    .slice(0, Math.min(totalData.length, monthsToDisplay.value))
    .reduce((sum, amount) => sum + amount, 0);

  if (previousPeriodTotal > 0) {
    stats.value.averageChangePercent = ((currentPeriodTotal - previousPeriodTotal) / previousPeriodTotal) * 100;
  } else {
    stats.value.averageChangePercent = 0;
  }

  // Calculate recurring percentage
  const totalRecurring = recurringData.reduce((sum, amount) => sum + amount, 0);
  const grandTotal = totalRecurring + oneTimeData.reduce((sum, amount) => sum + amount, 0);

  if (grandTotal > 0) {
    stats.value.recurringPercentage = Math.round((totalRecurring / grandTotal) * 100);
  } else {
    stats.value.recurringPercentage = 0;
  }

  // Find top category
  const categorySums = {};

  combinedPayments.value.forEach((payment) => {
    const category = payment.category || "other";
    if (!categorySums[category]) {
      categorySums[category] = 0;
    }
    categorySums[category] += payment.amount;
  });

  let topCategoryName = "none";
  let topCategoryAmount = 0;

  Object.entries(categorySums).forEach(([category, amount]) => {
    if (amount > topCategoryAmount) {
      topCategoryName = category;
      topCategoryAmount = amount;
    }
  });

  stats.value.topCategory = topCategoryName;

  if (grandTotal > 0) {
    stats.value.topCategoryPercentage = Math.round((topCategoryAmount / grandTotal) * 100);
  } else {
    stats.value.topCategoryPercentage = 0;
  }

  // Calculate payment completion rate
  const totalPayments = combinedPayments.value.length;
  const completedPayments = combinedPayments.value.filter((payment) => payment.isPaid).length;

  if (totalPayments > 0) {
    stats.value.paymentCompletionRate = Math.round((completedPayments / totalPayments) * 100);
  } else {
    stats.value.paymentCompletionRate = 0;
  }
}

// Prepare available months for selection
function prepareAvailableMonths() {
  const months = [];

  // Create an array of months up to the current month
  for (let i = 0; i < 24; i++) {
    const monthDate = $dayjs().subtract(i, "month");
    months.push({
      label: monthDate.format("MMMM YYYY"),
      value: monthDate.format("YYYY-MM")
    });
  }

  availableMonths.value = months;

  // Set default selected month to current month
  selectedMonth.value = $dayjs().format("YYYY-MM");
}

// Update all charts
async function updateCharts() {
  await nextTick();
  updateCategoryPieChart();
  calculateStatistics();
  await createMonthlyTrendsChart();
}

// ----- Fetch Data ---------
async function fetchData() {
  isLoading.value = true;
  try {
    // Set up filters for payments
    const startDate = $dayjs().subtract(12, "month").startOf("month").toDate();
    await paymentStore.fetchPayments({ startDate });
    await recurrentStore.fetchRecurrentPayments();

    // Prepare months dropdown
    prepareAvailableMonths();

    // Initialize charts
    await updateCharts();
  } catch (error) {
    console.error("Error fetching data:", error);
    useToast("error", "Failed to load summary data");
  } finally {
    isLoading.value = false;
  }
}

// ----- Initialize Data ---------
onMounted(async () => {
  await fetchData();
});

// ----- Define Watchers ---------
watch(monthsToDisplay, async () => {
  await updateCharts();
});

// ----- Set Page Meta ---------
useHead({
  title: "Financial Summary - PayTrackr",
  meta: [
    {
      name: "description",
      content: "Get a comprehensive overview of your finances with PayTrackr's detailed summary and analytics."
    }
  ]
});
</script>
