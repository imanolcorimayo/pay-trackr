<template>
    <div v-if="!isLoading" class="flex flex-col gap-[0.571rem] border-b border-[#DDDDDD]/30 pb-4">
        <div class="flex justify-between">
            <span class="text-[1.143rem] font-medium">{{title}}</span>
            <div class="flex items-center gap-[0.714rem]">
                <span class="text-[1.143rem] font-medium">{{formatPrice(amount)}}</span>
                <div @click="showEdit()" class="p-[0.357rem] bg-white rounded-[0.214rem]">
                    <HugeiconsPencilEdit02 class="text-black"/>
                </div>
            </div>
        </div>
        <span>{{ description }}</span>
        <div class="flex justify-between">
            <span>Category: Other</span>
            <span>Due: {{ dueDate }}</span>
            <div v-if="isPaid" class="flex items-center gap-[0.213rem] text-[--success-color]">
                <IcRoundCheck/>
                <span>Paid</span>
            </div>
            <div v-else class="flex items-center gap-[0.213rem] text-[--danger-color]">
                <IcRoundClose/>
                <span>Unpaid</span>
            </div>
        </div>
    </div>
    <ConfirmDialogue ref="confirmDialogue" />
</template>

<script setup>
import HugeiconsPencilEdit02 from '~icons/hugeicons/pencil-edit-02';
import IcRoundClose from '~icons/ic/round-close';
import IcRoundCheck from '~icons/ic/round-check';

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

// ------ Define Pinia Variables ----
const indexStore = useIndexStore()

// ------ Define Vars -------
const isLoading = ref(false)

// Refs
const confirmDialogue = ref(null)

// ----- Define methods ------------

function showEdit() {
    emit("editPayment", props.id)
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

<style scoped>

:deep(.popper) {
    font-size: 0.778rem;
}
</style>