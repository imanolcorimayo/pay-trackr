<template>
  <div class="flex flex-col gap-[2.143rem]">
    <div v-if="showDates" class="flex items-center justify-center gap-[1.143rem] w-full">
      <div class="flex justify-center items-center w-[2.357rem] h-[2.357rem] bg-white rounded-[0.214rem]">
        <WeuiArrowFilled class="text-[0.875rem] h-full text-black rotate-180" />
      </div>
      <span class="text-[1.143rem] font-medium">January - March</span>
      <div class="flex justify-center items-center w-[2.357rem] h-[2.357rem] bg-white rounded-[0.214rem]">
        <WeuiArrowFilled class="text-[0.875rem] h-full text-black" />
      </div>
    </div>
    <div class="flex flex-col gap-[1rem] sm:flex-row w-full sm:px-[1.143rem] justify-between items-end sm:items-center">
      <div class="flex gap-[1rem]">
        <div tabindex="0" class="
            flex justify-center items-center gap-[0.214rem] h-[2.357rem] 
            bg-white rounded-[0.428rem] px-[0.214rem]
            focus:ring-[0.214rem] focus:ring-primary
          " @focus="focusInput" @blur="blurInput">
          <IcSharpSearch class="text-black text-[1.190rem]" />
          <input type="text" @input="(value) => $emit('onSearch', value.target.value)"
            class="focus:outline-none text-black" placeholder="Eg. rental">
        </div>
        <Tooltip ref="tooltipFilter" @click="toggleTooltip">
          <div class="flex justify-center items-center w-[2.357rem] h-[2.357rem] bg-white rounded-[0.214rem]">
            <MdiFilterOutline class="text-black text-[1.190rem]" />
          </div>
          <template #content>
            <div class="flex flex-col">
              <div class="flex items-center gap-[0.571rem] font-medium p-2 cursor-pointer text-black hover:bg-gray-200 rounded-t-lg">
                <TablerCalendarFilled class="text-[1.3rem] text-gray-800"/> 
                <span>Date</span>
              </div>
              <div class="flex items-center gap-[0.571rem] font-medium p-2 cursor-pointer text-black hover:bg-gray-200">
                <MaterialSymbolsPaidRounded class="text-[1.3rem] text-gray-800"/> 
                <span>Amount</span>
              </div>
              <div class="flex items-center gap-[0.571rem] font-medium p-2 cursor-pointer text-black hover:bg-gray-200 rounded-b-lg">
                <TablerCalendarFilled class="text-[1.3rem] text-gray-800"/> 
                <span>Title</span>
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

const props = defineProps({
  showDates: {
    required: false,
    default: false,
    type: Boolean
  }
});

// ----- Define Vars ------

// Refs
const tooltipFilter = ref(null);

// ----- Define Methods ------
function toggleTooltip() {
  if (tooltipFilter.value) {
    tooltipFilter.value.toggleTooltip();
  }
}

function focusInput() {
  console.log('Focus input');
}

function blurInput() {
  console.log('Blur input');
}
</script>
