<template>
    <div v-if="!isLoading" 
        class="
            flex py-2 items-center card-container 
            transition ease-in-out duration-150 hover:-translate-y-1 hover:scale-[1.01]
        ">
        <div class="basis-1/6 flex items-center justify-start">
            <div 
                class="leading-tight mb-1 py-1 flex w-12 me-2 bg-opacity-15 flex-col items-center rounded-lg"
                :class="{
                    'bg-green-500': isPaid && !edit, 'bg-opacity-70': (isPaid || delayed) && !edit ,
                    'bg-rose-500': !isPaid && delayed && !edit, 'bg-white': edit || (!isPaid && !delayed)
                }"
            >
                <span class="text-lg font-bold leading-none">{{day}}</span>
                <span>{{weekDay}}</span>
            </div>
        </div>
        <div class="grid grid-cols-6 gap-y-3 items-center border-b pb-2 self-end border-opacity-35 border-white w-full">
            <div class="col-start-1 col-end-2 font-bold" style="min-width: 95px;">{{ formatPrice(amount) }}</div>
            <div class="col-start-3 col-end-6 md:col-start-3 md:col-end-5 flex flex-col">
                <div class="font-bold text-sm">{{ title }}</div>
                <div class="text-sm">{{ description }}</div>
            </div>
            <div class="col-start-1 md:col-start-6 col-end-6 flex justify-center">
                <div v-if="!edit" class="flex gap-[0.571rem]">
                    <button 
                        v-if="!isPaid" 
                        class="transition ease-in-out duration-150 flex justify-center items-center border-[1.5px] border-opacity-30 border-white 
                            rounded-[0.4rem] h-[2.423rem] w-[2.423rem]
                            hover:bg-green-600" 
                        @click="markAsPaid(true)" 
                    >
                        <IcRoundCheck class="text-[1rem]"/>
                    </button>
                    <button v-else class="
                        transition ease-in-out duration-150 flex justify-center items-center border-[1.5px] border-opacity-30 border-white 
                        rounded-[0.4rem] h-[2.423rem] w-[2.423rem]
                        hover:bg-red-400
                        " @click="markAsPaid(false)">
                        <MdiRemove class="text-[1rem]"/>
                    </button>
                    <button 
                        v-if="trackerId"
                        class="transition ease-in-out duration-150 flex justify-center items-center border-[1.5px] border-opacity-30 border-white 
                            rounded-[0.4rem] h-[2.423rem] w-[2.423rem]
                            hover:bg-red-400" 
                        @click="removePay('history')" 
                    
                    >
                        <PhTrashLight class="text-[1rem]"/>
                    </button>
                    <button 
                        v-else="trackerId" 
                        class="transition ease-in-out duration-150 flex justify-center items-center border-[1.5px] border-opacity-30 border-white 
                            rounded-[0.4rem] h-[2.423rem] w-[2.423rem]
                            hover:bg-red-400" 
                        @click="removePay('tracker')" 
                    >
                        <PhTrashLight class="text-[1rem]"/>
                    </button>
                    <button 
                        v-if="trackerId" 
                        class="transition ease-in-out duration-150 flex justify-center items-center border-[1.5px] border-opacity-30 border-white 
                            rounded-[0.4rem] h-[2.423rem] w-[2.423rem]
                            hover:bg-gray-500" 
                        @click="$emit('editPayment', id, trackerId)">
                        <MaterialSymbolsLightEditSharp class="text-[1rem]"/>
                    </button>
                    <button 
                        v-else="trackerId" 
                        class="transition ease-in-out duration-150 flex justify-center items-center border-[1.5px] border-opacity-30 border-white 
                            rounded-[0.4rem] h-[2.423rem] w-[2.423rem]
                            hover:bg-gray-500" 
                        @click="$emit('editPayment', id)"
                    >
                        <MaterialSymbolsLightEditSharp class="text-[1rem]"/>
                    </button>
                </div>
                <div v-else  class="flex gap-[0.571rem]">
                    <button 
                        @click="$emit('editPayment', id)" 
                        class="transition ease-in-out duration-150 flex justify-center items-center border-[1.5px] border-opacity-30 border-white 
                            rounded-[0.4rem] h-[2.423rem] w-[2.423rem]
                            hover:bg-gray-500" 
                        style="height: auto; font-size: 0.714rem;"
                    >
                        <MaterialSymbolsLightEditSharp class="text-[1rem]"/>
                    </button>
                    <button 
                        class="transition ease-in-out duration-150 flex justify-center items-center border-[1.5px] border-opacity-30 border-white 
                            rounded-[0.4rem] h-[2.423rem] w-[2.423rem]
                            hover:bg-red-400" 
                        @click="removePay('recurrent')">
                        <PhTrashLight class="text-[1rem]"/>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div v-else>
        <Loader />
    </div>
    <ConfirmDialogue ref="confirmDialogue" />
</template>

<script setup>
import MaterialSymbolsLightEditSharp from '~icons/material-symbols-light/edit-sharp';
import IcRoundCheck from '~icons/ic/round-check';
import MdiRemove from '~icons/mdi/remove';
import PhTrashLight from '~icons/ph/trash-light';

const props = defineProps({
    description: {
        required: false,
        default: "",
        type: String
    },
    id: {
        required: true,
        type: String
    },
    title: {
        required: true,
        type: String

    },
    amount: {
        required: true,
        type: Number
    },
    dueDate: {
        required: true,
        type: String
    },
    isPaid: {
        required: false,
        type: Boolean,
        default: false
    },
    edit: {
        required: false,
        type: Boolean,
        default: false
    },
    trackerId: {
        required: false,
        type: String
    }
})

const emit = defineEmits(["editPayment"])

// ----- Define Useful Properties --------
const { $dayjs } = useNuxtApp()
const route = useRoute()

// ------ Define Pinia Variables ----
const indexStore = useIndexStore()

// ------ Define Vars -------
const dueDateObject = $dayjs(props.dueDate, { format: 'MM/DD/YYYY' });
const month = ref(dueDateObject.format('MMM'));
const day = ref(dueDateObject.format('DD'));
const delayed = ref(dueDateObject.isBefore($dayjs(), 'day'))
const weekDay = ref(dueDateObject.format('ddd'))
const isLoading = ref(false)

// Refs
const confirmDialogue = ref(null)

// ----- Define methods ------------
async function markAsPaid(value) {
    // Confirm dialogue
    const confirmed = await confirmDialogue.value.openDialog({ edit: true });

    if (!confirmed) {
        return;
    }

    isLoading.value = true;
    // Manage if it comes form "/history" or home "/"" page
    let result;
    if(props.trackerId && route.fullPath.includes("/history")) {
        result = await indexStore.editIsPaidInHistory(props.id, props.trackerId,value);
    } else {
        result = await indexStore.editIsPaid(props.id, value);
    }

    const toastMessage = {
        type: "success",
        message: "Marked as paid successfully"
    };
    if (!result) {
        toastMessage.type = "error";
        toastMessage.message = "Something went wrong, please try again or contact the support team.";
    }
    useToast(toastMessage.type, toastMessage.message);
    isLoading.value = false;

}

async function removePay(type) {
    // Confirm dialogue
    const confirmed = await confirmDialogue.value.openDialog();

    if (!confirmed) {
        return;
    }

    // We only change the function to execute, so we do a switch
    let removed;
    switch(type) {
        case "recurrent":
            removed = await indexStore.removePayment(props.id);
            break;
        case "tracker":
            removed = await indexStore.removePayInTracker(props.id);
            break;
        case "history":
            // Validate we have trackerId
            if(!props.trackerId) {
                return useToast("error", "Invalid function execution. Contact us to continue.")
            }
            removed = await indexStore.removePayInHistory(props.id, props.trackerId);
            break;
    }



    const toastMessage = {
        type: "success",
        message: "Payment removed successfully"
    };
    if (!removed) {
        toastMessage.type = "error";
        toastMessage.message = "Something went wrong, please try again or contact the support team.";
    }
    useToast(toastMessage.type, toastMessage.message);
}
</script>