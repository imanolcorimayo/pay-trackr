<template>
    <div class="relative">
        <slot></slot>
        <div v-if="isOpen" ref="tooltipBody"class="absolute z-[25] mt-2 bg-white border border-gray-200 rounded-lg shadow-lg min-w-[15rem]">
            <slot name="content"></slot>
        </div>
    </div>
</template>

<script setup>

// ----- Define Useful Properties ---------

// ----- Define Vars ------
const isOpen = ref(false);

// Refs
const tooltipBody = ref(null);

onClickOutside(tooltipBody, () => {
    isOpen.value = false;
});

// ----- Define Methods ------
function toggleTooltip() {
    isOpen.value = !isOpen.value;
}


// ----- Define Expose ------
defineExpose({
    toggleTooltip
});


</script>