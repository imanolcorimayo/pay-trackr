<template>
  <div class="flex flex-col gap-[2.143rem]">
    <Loader v-if="actionRunning" class="max-w-[2rem]"/>
    <div v-if="showDates" class="flex items-center justify-center gap-[1.143rem] w-full">
      <button @click="updateHistory('back')" class="flex justify-center items-center w-[2.357rem] h-[2.357rem] bg-white rounded-[0.214rem]">
        <WeuiArrowFilled class="text-[0.875rem] h-full text-black rotate-180" />
      </button>
      <span class="text-[1.143rem] font-medium">{{ lastMonth }} - {{ currentMonth }}</span>
      <button 
        :disabled="monthsBack == 0" @click="updateHistory('forward')" 
        class="flex justify-center items-center w-[2.357rem] h-[2.357rem] bg-white rounded-[0.214rem] disabled:cursor-not-allowed disabled:opacity-40">
        <WeuiArrowFilled class="text-[0.875rem] h-full text-black" />
      </button>
    </div>
    <div class="flex flex-col gap-[1rem] sm:flex-row w-full sm:px-[1.143rem] justify-between items-end sm:items-center">
      <div class="flex gap-[1rem]">
        <div tabindex="0" class="relative h-[2.357rem] rounded-[0.428rem]">
          <IcSharpSearch class="absolute h-[2.357rem] left-[0.214rem] text-black text-[1.190rem] pointer-events-none" />
          <input type="text" @input="(value) => $emit('onSearch', value.target.value)"
            class="h-[2.357rem] rounded-[0.428rem] pl-[1.714rem] bg-white text-black" placeholder="Eg. rental">
        </div>

        <Tooltip ref="tooltipFilter">
          <div class="flex justify-center items-center w-[2.357rem] h-[2.357rem] bg-white rounded-[0.214rem]" @click="toggleTooltip">
            <MdiFilterOutline class="text-black text-[1.190rem]" />
          </div>
          <template #content>
            <div class="flex flex-col">
              <div
                v-for="filter in filters"
                :key="filter.name"
                @click="selectFilter(filter)"
                :class="['flex justify-between items-center p-3 cursor-pointer font-medium', 
                        selectedFilter.name === filter.name ? 'bg-gray-200' : 'hover:bg-gray-200', 
                        'text-black', filter.class]"
              >
                <div class="flex items-center gap-[0.571rem]">
                  <component :is="filter.icon" class="text-[1.3rem] text-gray-800" /> 
                  <span>{{ filter.label }}</span>
                </div>
                <div v-if="selectedFilter.name === filter.name">
                  <div v-if="selectedFilter.order === 'asc'" class="flex items-center gap-[0.286rem]">
                    <span class="text-sm text-gray-600/75">Low to high</span>
                    <MingcuteArrowUpFill class="text-secondary"/>
                  </div>
                  <div v-else class="flex items-center gap-[0.286rem]">
                    <span class="text-sm text-gray-600/75">High to low</span>
                    <MingcuteArrowUpFill class="rotate-180 text-secondary"/>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Tooltip>
      </div>
      <PaymentsNewPayment />
    </div>
  </div>
</template>

<script setup>
import TablerCalendarFilled from '~icons/tabler/calendar-filled';
import MaterialSymbolsPaidRounded from '~icons/material-symbols/paid-rounded';
import WeuiArrowFilled from '~icons/weui/arrow-filled';
import MdiFilterOutline from '~icons/mdi/filter-outline';
import IcSharpSearch from '~icons/ic/sharp-search';
import MingcuteArrowUpFill from '~icons/mingcute/arrow-up-fill';
import BiAlphabet from '~icons/bi/alphabet';

const props = defineProps({
  showDates: {
    required: false,
    default: false,
    type: Boolean
  }
});

const emits = defineEmits(['onOrder', 'monthsBack']); 

// ----- Define Useful Properties ---------
const { width } = useWindowSize();
const { $dayjs } = useNuxtApp();

// ----- Define Pinia Vars ----------
const indexStore = useIndexStore()

// ----- Define Vars ------
const monthsBack = ref(0);
const actionRunning = ref(false);
const selectedFilter = ref({ name: '', order: '' });

// Based on the screen width select 3 months or 6 months
const nMonths = width.value > 768 ? 6 : 3;

// Based on today, get the full name of the current month and the past two months
// Like this const lastMonth = "January" and currentMonth = "March"
const lastMonth = ref($dayjs().subtract(nMonths - 1, 'month').format('MMMM'));
const currentMonth = ref($dayjs().format('MMMM'));


// Refs
const tooltipFilter = ref(null);

// ----- Define Methods ------
function toggleTooltip() {
  if (tooltipFilter.value) {
    tooltipFilter.value.toggleTooltip();
  }
}

const filters = [
  { name: 'date', label: 'Date', icon: TablerCalendarFilled, class: 'rounded-t-lg' },
  { name: 'amount', label: 'Amount', icon: MaterialSymbolsPaidRounded, class: '' },
  { name: 'title', label: 'Title', icon: BiAlphabet, class: 'rounded-b-lg' }
];


function selectFilter(filter) {
  if (selectedFilter.value.name === filter.name && selectedFilter.value.order === 'asc') {
    selectedFilter.value.order = 'desc';
  } else if (selectedFilter.value.name === filter.name) {
    selectedFilter.value.name = '';
    selectedFilter.value.order = '';
  } else {
    selectedFilter.value.name = filter.name;
    selectedFilter.value.order = 'asc';
  }

  // Emit current filter configuration
  emits("onOrder", selectedFilter.value);
};

async function updateHistory(type) {
  // Set action running
  actionRunning.value = true;

  // Add or subtract months based on type
  if (type === 'back') {
    monthsBack.value = monthsBack.value + 1;
  } else if (type === 'forward') {
    monthsBack.value = monthsBack.value - 1;
  }

  // Update history
  await indexStore.loadHistory(monthsBack.value);
  
  // Emit months back event
  emits("monthsBack", monthsBack.value);

  // Update last and current month
  lastMonth.value = $dayjs().subtract(nMonths - 1 + monthsBack.value, 'month').format('MMMM');
  currentMonth.value = $dayjs().subtract(monthsBack.value, 'month').format('MMMM');

  // Set action running
  actionRunning.value = false;
}
</script>
