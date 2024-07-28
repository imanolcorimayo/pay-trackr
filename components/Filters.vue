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
        <div 
          tabindex="0"
          class="
            flex justify-center items-center gap-[0.214rem] h-[2.357rem] 
            bg-white rounded-[0.428rem] px-[0.214rem]
            focus:ring-[0.214rem] focus:ring-primary
          "
          @focus="focusInput" @blur="blurInput"
        >
          <IcSharpSearch class="text-black text-[1.190rem]"/>
          <input type="text" @input="(value) => $emit('onSearch', value.target.value)" class="focus:outline-none text-black" placeholder="Eg. rental">
        </div>
        <div class="flex justify-center items-center w-[2.357rem] h-[2.357rem] bg-white rounded-[0.214rem]">
          <MdiFilterOutline class="text-black text-[1.190rem]"/>
        </div>
      </div>
      <PaymentsNewPayment/>
    </div>
  </div>
</template>

<script setup>
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

function focusInput() {
  console.log('Focus input');
}

function blurInput() {
  console.log('Blur input');
}
</script>
