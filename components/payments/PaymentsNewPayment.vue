<template>
    <div>
        <PaymentsManagePayment ref="newPayment" @onClose="() => showAddButton = true"/>
        <div class="fixed bottom-0 right-0 w-full" v-if="showAddButton">
            <div class="max-w-[57.143rem] m-auto flex justify-end p-[1.429rem]">
                <div class="
                    flex flex-col gap-[0.143rem] items-center
                    ">
                    <button 
                        @click="() => {showAddButton = false; newPayment.showModal()}" 
                        class="
                            bg-[--primary-bg-color]
                            text-[--secondary-bg-color]
                            flex justify-center items-center rounded-full w-[3.143rem] h-[3.143rem]
                            transition ease-in-out delay-50 hover:-translate-y-1 hover:scale-110 duration-150 
                        ">
                        <MdiPlus class="text-[1.428rem] leading-[1.714rem]"/>
                    </button>
                    <span class="text-[0.714rem] font-semibold capitalize">new pay</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import MdiPlus from '~icons/mdi/plus';

// ------ Define Vars ------
const showAddButton = ref(true);

// Refs
const newPayment = ref(null)
</script>