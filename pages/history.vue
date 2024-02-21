<template>
    <div>
        <h2>Payments' History</h2>
        <div>
            <div v-for="(tracker, index) in formattedHistory" :key="index">
                <div class="hover-expand">
                    <button @click="toggleShow(tracker.id)"
                        class="px-0 text-xl no-button my-0 mt-2 flex items-center justify-between w-full">
                        <span class="w-32 flex-initial text-start">{{ tracker.month }} {{ tracker.year }}</span>
                        <div class="w-full border-b-2 border-solid border-white border-opacity-35"></div>
                    </button>
                </div>
                <div class="overflow-hidden">
                    <Transition>
                        <div v-if="show[tracker.id]">
                            <div class="flex py-2 items-center" v-for="(pay, idxPay) in tracker.payments" :key="`pay-${index}-${idxPay}`">
                                <div class="basis-1/6 flex items-center justify-start">
                                    <div 
                                        class="leading-tight mb-1 py-1 flex w-12 me-2 bg-opacity-15 flex-col items-center rounded-lg"
                                        :class="{'bg-green-500': pay.isPaid, 'bg-opacity-70': pay.isPaid, 'bg-white': !pay.isPaid }"
                                    >
                                        <span class="text-lg font-bold leading-none">{{pay.day}}</span>
                                        <span>{{pay.weekDay}}</span>
                                    </div>
                                </div>
                                <div class="flex basis-full items-center border-b pb-2 self-end border-opacity-35 border-white">
                                    <div class="basis-1/4 font-bold" style="min-width: 95px;">{{ formatPrice(pay.amount) }}</div>
                                    <div class="basis-full flex flex-col">
                                        <div class="font-bold text-sm">{{ pay.title }}</div>
                                        <div class="text-sm">{{ pay.description }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// ----- Define Useful Properties ---------
const { $dayjs } = useNuxtApp()

// ----- Define Vars -------------
const show = ref({});
const formattedHistory = ref([])

// ----- Define Pinia Vars
const indexStore = useIndexStore()
// First load history
await indexStore.loadHistory();
// Retrieve values
const { getHistory: history } = storeToRefs(indexStore);
processHistory();

function processHistory() {
    // Flag to set show object with first element opened
    let isFirst = true;
    formattedHistory.value = history.value.map(el => {
        // Set show object
        show.value[el.id] = isFirst;
        isFirst = false;

        const auxObj = {...el};
        const date = $dayjs.unix(el.createdAt.seconds);

        // Format legible date
        auxObj.day =  date.format('D')
        auxObj.month =  date.format('MMM')
        auxObj.weekDay =  date.format('ddd')
        auxObj.year =  date.format('YYYY')

        // Format payments date too
        auxObj.payments = auxObj.payments.map(payment => {
            const auxPayObj = {...payment};
            const payDate = $dayjs(payment.dueDate, { format: 'MM/DD/YYYY' });
            // Format legible date
            auxPayObj.day =  payDate.format('D')
            auxPayObj.month =  payDate.format('MMM')
            auxPayObj.weekDay =  payDate.format('ddd')
            auxPayObj.year =  payDate.format('YYYY')

            return auxPayObj;
        })

        return auxObj;
    })
}

function toggleShow(id) {
    show.value[id] = !show.value[id];
}

useHead({
    title: 'Payments\' History - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Be able to keep tracking all your payments over time'
        }
    ]
})
</script>


<style scoped>
.v-enter-active,
.v-leave-active {
    transition: all .3s ease-out;
}

.v-enter-from,
.v-leave-to {
    transform: translateY(-3rem);
    opacity: 0;
}

.hover-expand:hover {
    transform: scale(1.01);
    transition: all .2s ease-out;
}

</style>