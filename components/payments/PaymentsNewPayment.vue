<template>
    <div>
        <PaymentsManagePayment ref="newPayment" @onClose="() => showAddButton = true"/>
        <div class="w-full m-auto" v-if="showAddButton">
            <button 
                @click="() => {showAddButton = false; newPayment.showModal()}" 
                class="
                    flex justify-center items-center max-w-[25rem] w-full gap-[0.714rem] rounded-[0.714rem]
                    bg-primary text-black m-auto px-[0.857rem] py-[0.714rem]
                "
            >
                <MdiPlus class="font-semibold"/>
                <span class="capitalize font-medium">Add Payment</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import MdiPlus from '~icons/mdi/plus';

// ------ Define Vars ------
const showAddButton = ref(true);

// Refs
const newPayment = ref(null);
</script>