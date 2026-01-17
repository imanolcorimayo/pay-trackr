<template>
  <div class="mb-4 p-3">
    <div v-if="actionRunning" class="flex justify-center my-2">
      <Loader />
    </div>
    
    <div class="flex flex-col sm:flex-row w-full justify-between items-center gap-3 px-1">
      <!-- Search Bar -->
      <div class="flex items-center gap-3 w-full sm:w-auto">
        <div class="relative w-full sm:w-64">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <IcSharpSearch class="text-gray-600 text-lg" />
          </div>
          <input 
            type="text" 
            @input="(value) => $emit('onSearch', value.target.value)"
            class="w-full h-10 rounded-md bg-gray-400 border border-gray-400 pl-10 text-black placeholder:text-gray-600 focus:ring-2 focus:ring-primary focus:border-transparent" 
            placeholder="Search payments..."
          >
        </div>
        
        <!-- Filter Button -->
        <Tooltip ref="tooltipFilter">
          <button 
            class="h-10 w-10 flex items-center justify-center bg-base/80 hover:bg-gray-700 rounded-md border border-gray-400 text-white transition-colors"
            @click="toggleTooltip"
            aria-label="Filter options"
          >
            <MdiFilterOutline class="text-xl" />
          </button>
          
          <!-- Filter Dropdown Content -->
          <template #content>
            <div class="bg-base/80 border border-gray-400 rounded-lg shadow-lg overflow-hidden">
              <div
                v-for="filter in filters"
                :key="filter.name"
                @click="selectFilter(filter)"
                :class="[
                  'flex justify-between items-center p-3 cursor-pointer hover:bg-gray-700 transition-colors', 
                  selectedFilter.name === filter.name ? 'bg-gray-700' : '',
                  filter.class
                ]"
              >
                <div class="flex items-center gap-2">
                  <component :is="filter.icon" class="text-primary text-lg" /> 
                  <span class="text-white">{{ filter.label }}</span>
                </div>
                <div v-if="selectedFilter.name === filter.name" class="flex items-center gap-2 text-gray-300">
                  <span v-if="selectedFilter.order === 'asc'">
                    <span class="text-sm">Low to high</span>
                    <MingcuteArrowUpFill class="text-primary ml-1" />
                  </span>
                  <span v-else>
                    <span class="text-sm">High to low</span>
                    <MingcuteArrowUpFill class="rotate-180 text-primary ml-1" />
                  </span>
                </div>
              </div>
            </div>
          </template>
        </Tooltip>
      </div>
      
      <!-- Could add additional elements here if needed -->
    </div>
  </div>
</template>

<script setup>
// Keep existing script code
import TablerCalendarFilled from '~icons/tabler/calendar-filled';
import MaterialSymbolsPaidRounded from '~icons/material-symbols/paid-rounded';
import MdiFilterOutline from '~icons/mdi/filter-outline';
import IcSharpSearch from '~icons/ic/sharp-search';
import MingcuteArrowUpFill from '~icons/mingcute/arrow-up-fill';
import BiAlphabet from '~icons/bi/alphabet';
import MdiSortBoolAscendingVariant from '~icons/mdi/sort-bool-ascending-variant';

const props = defineProps({
  showDates: {
    required: false,
    default: false,
    type: Boolean
  },
  initialSort: {
    required: false,
    default: () => ({ name: '', order: '' }),
    type: Object
  }
});

const emits = defineEmits(['onOrder', 'monthsBack']); 

// ----- Define Useful Properties ---------
const { width } = useWindowSize();

// ----- Define Vars ------
const actionRunning = ref(false);
const selectedFilter = ref({ name: props.initialSort.name || '', order: props.initialSort.order || '' });

// Refs
const tooltipFilter = ref(null);

// ----- Define Methods ------
function toggleTooltip() {
  if (tooltipFilter.value) {
    tooltipFilter.value.toggleTooltip();
  }
}

const filters = [
  { name: 'unpaid_first', label: 'Unpaid First', icon: MdiSortBoolAscendingVariant, class: 'rounded-t-lg' },
  { name: 'date', label: 'Date', icon: TablerCalendarFilled, class: '' },
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
}
</script>