<template>
    <div v-if="!isLoading" 
        class="
            flex py-2 items-center card-container 
            transition ease-in-out hover:-translate-y-1 hover:scale-[1.01] duration-150
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
        <div class="flex basis-full items-center border-b pb-2 self-end border-opacity-35 border-white">
            <div class="basis-1/4 font-bold" style="min-width: 95px;">{{ formatPrice(amount) }}</div>
            <div class="basis-full flex flex-col">
                <div class="font-bold text-sm">{{ title }}</div>
                <div class="text-sm">{{ description }}</div>
            </div>
            <div class="hover-show">
                <div v-if="!edit" class="m-1">
                    <button v-if="!isPaid" class="w-20 leading-none my-0 h-auto" @click="markAsPaid(true)" style="font-size: 0.714rem;">Mark Paid</button>
                    <button v-else class="w-20 leading-none my-0 h-auto hover:bg-red-300" @click="markAsPaid(false)" style="font-size: 0.714rem;">Unpaid</button>
                    <button v-if="trackerId" class="w-20 leading-none my-0 h-auto bg-red-300" @click="removePay('history')" style="font-size: 0.714rem;">Remove History</button>
                    <button v-else="trackerId" class="w-20 leading-none my-0 h-auto bg-red-300" @click="removePay('tracker')" style="font-size: 0.714rem;">Remove</button>
                </div>
                <div v-else  class="flex flex-col gap-[0.286rem]">
                    <button @click="$emit('editPayment', id)" class="w-16 leading-none" style="height: auto; font-size: 0.714rem;">
                        Edit
                    </button>
                    <button class="w-16 leading-none my-0 h-auto bg-red-300 border-red-300" @click="removePay('recurrent')" style="font-size: 0.714rem;">Remove</button>
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

<style>
.hover-show {
    display: none;
    opacity: 0;
}
.card-container:hover .hover-show {
    display: block;
    opacity: 1;
    transition: all .3 ease;
}
</style>