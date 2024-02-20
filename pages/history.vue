<template>
    <div>
        <h2>Payments' History</h2>
        <div>
            <div v-for="(tracker, index) in formattedHistory" :key="index">
                <div>
                    <button @click="toggleShow"
                        class="px-0 text-xl no-button my-0 mt-2 flex items-center justify-between w-full">
                        <span class="w-32 flex-initial text-start">{{ tracker.month }} {{ tracker.year }}</span>
                        <div class="w-full border-b-2 border-solid border-white border-opacity-35"></div>
                    </button>
                </div>
                <div class="overflow-hidden">
                    <Transition>
                        <div v-if="show">
                            <div class="flex py-2 items-center" v-for="(pay, idxPay) in tracker" :key="`pay-${index}-${idxPay}`">
                                <div class="basis-1/6 flex items-center justify-start">
                                    <div class="leading-tight mb-1 py-1 flex w-12 me-2 bg-white bg-opacity-35 flex-col items-center rounded-full">
                                        <span>Sat</span>
                                        <span class="text-lg font-bold leading-none">05</span>
                                    </div>
                                </div>
                                <div class="flex basis-full items-center border-b pb-2 self-end border-opacity-35 border-white">
                                    <div class="basis-1/4 font-bold">Amount</div>
                                    <div class="basis-full flex flex-col">
                                        <div class="font-bold text-sm">Title</div>
                                        <div class="text-sm">Description</div>
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
const show = ref(true);
const formattedHistory = ref([])

// ----- Define Pinia Vars
const indexStore = useIndexStore()
// First load history
await indexStore.loadHistory();
// Retrieve values
const { getHistory: history } = storeToRefs(indexStore);

processHistory();

console.log(formattedHistory.value)

function processHistory() {
    formattedHistory.value = history.value.map(el => {
        const auxObj = {...el};
        const date = $dayjs.unix(el.createdAt.seconds);

        // Format legible date
        auxObj.day =  date.format('D')
        auxObj.month =  date.format('MMM')
        auxObj.weekDay =  date.format('ddd')
        auxObj.year =  date.format('YYYY')

        return auxObj;
    })
}

function toggleShow() {
    show.value = !show.value;
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
}</style>