<template>
    <div>
        <h2>Monthly Totals</h2>
        <canvas id="monthlyTotals"></canvas>
        <h2>Payments distribution for 
        <select @change="event => createMonthSplit(event)">
            <option :value="month.id" v-for="(month, idx) in monthsToSelect" :key="idx">{{ month.date }}</option>
        </select></h2>
        <canvas class="m-auto w-full" style="max-width: 30rem; max-height: 30rem;" id="pieChart"></canvas>
    </div>
</template>

<script setup>
import { Chart } from 'chart.js/auto'

definePageMeta({
    middleware: ['auth']
})
// ---- Define Useful Properties --------
const { $dayjs } = useNuxtApp();

// ----- Define Pinia Vars ----------
const indexStore = useIndexStore()
// First load history
await indexStore.loadHistory();
// Retrieve values
const { getHistory: history } = storeToRefs(indexStore);

// ---- Define Vars -------
const monthsToSelect = ref(history.value.map(el => {
    const date = $dayjs.unix(el.createdAt.seconds);

    // Format legible date
    const month = date.format('MMM')
    const year = date.format('YYYY')

    return { date: `${month} ${year}`, id: el.id}
}))
const pieCanvasChart = ref(false)

// ----- Define Hooks ------
onMounted(() => {
    // Create the monthly resume chart
    createMonthlyResume()

    // Create the month split payment
    createMonthSplit()
})

// ----- Define Methods ------
function createMonthlyResume() {
    const totalPaid = [];
    const totalOwed = [];
    const total = [];

    // Order history first
    const aux = Object.assign([], history.value); // Auxiliary to don't affect history.value
    const labels = aux.reverse().map(monthly => {

        const date = $dayjs.unix(monthly.createdAt.seconds);

        // Format legible date
        const month = date.format('MMM')
        const year = date.format('YYYY')

        // Calculate total paid and total owed
        const paidAmount = monthly.payments.reduce((total, num) => {
            if (num.isPaid) {
                return total + num.amount;
            }
            return total;
        }, 0)
        const owedAmount = monthly.payments.reduce((total, num) => {
            if (!num.isPaid) {
                return total + num.amount;
            }
            return total;
        }, 0)

        totalPaid.push(paidAmount)
        totalOwed.push(owedAmount)
        total.push(owedAmount + paidAmount)


        return `${month} - ${year}`
    });
    const data = {
        labels: labels,
        datasets: [{
            label: 'Total Paid',
            data: totalPaid,
            fill: false,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }, {
            label: 'Total Owed',
            data: totalOwed,
            borderColor: 'rgb(255, 99, 132)',
            fill: false,
            tension: 0.1
        }, {
            label: 'Total',
            data: total,
            borderColor: 'rgb(54, 162, 235)',
            fill: false,
            tension: 0.1
        }]
    };
    const config = {
        type: 'line',
        data: data,
    };

    const ctx = document.getElementById('monthlyTotals');

    new Chart(ctx, config);
}

function createMonthSplit(event = false) {
    // Clean canvas chart first
    if(pieCanvasChart.value) {
        pieCanvasChart.value.destroy();
    }

    let historyValue;
    if(!event) {
        historyValue = history.value[history.value.length - 1]
    } else {
        // Filter value that matches with payment id
        const filteredValue = history.value.filter(el => el.id == event.target.value)

        if(!filteredValue.length) {
            return useToast("error", "Invalid month. Please try again or contact us if the error persists.");
        }
        // Save value
        historyValue = filteredValue[0]
    }

    // Order by amount before creating array of values
    historyValue.payments = historyValue.payments.sort((a, b) => {
        return b.amount - a.amount 
    })

    // If there are more than 7 payments, turn all minor payments to others
    let newPayments = historyValue.payments; 
    if(historyValue.payments.length > 7) {
        newPayments = historyValue.payments.slice(0, 6)

        newPayments.push({
            title: "Other",
            amount: historyValue.payments.reduce((total, elem, curIndex) => {
                if(curIndex < 6) {
                    return total
                }
                return total + elem.amount
            }, 0)
        })
    }

    // Create labels with the fixed object
    const labels = newPayments.map(el => el.title);
    const amounts = newPayments.map(el => el.amount);

    const data = {
        labels,
        datasets: [
            {
                label: 'Dataset 1',
                data: amounts,
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(255, 159, 64)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(54, 162, 235)',
                    'rgb(153, 102, 255)',
                    'rgb(201, 203, 207)'
                ],
            }
        ]
    };
    const config = {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Chart.js Pie Chart'
                }
            }
        },
    };

    const ctx = document.getElementById('pieChart');

    pieCanvasChart.value = new Chart(ctx, config);
}

useHead({
    title: 'Summary - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Gain insights into your spending habits with PayTrackr. Visualize your payment data through interactive charts.'
        }
    ]
})
</script>

<style scoped>
canvas {
    margin-top: 1rem;
    margin-bottom: 2rem;
}
</style>
