<template>
    <div v-if="!isLoading" class="flex py-2 items-center card-container">
        <div class="basis-1/6 flex items-center justify-start">
            <div 
                class="leading-tight mb-1 py-1 flex w-12 me-2 bg-opacity-15 flex-col items-center rounded-lg bg-white"
                :class="{
                    'bg-green-500': isPaid && !edit, 'bg-opacity-70': (isPaid || delayed) && !edit ,
                    'bg-rose-500': !isPaid && delayed && !edit
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
                    <button v-if="!isPaid" class="w-20 leading-none my-0 h-auto" @click="markAsPaid(true)">Mark Paid</button>
                    <button v-else class="w-20 leading-none my-0 h-auto hover:bg-red-300" @click="markAsPaid(false)">Unpaid</button>
                </div>
                <div v-else  class="m-1">
                    <NuxtLink :to="`/edit/${id}`" class="w-16 leading-none my-0 inline-block as-button text-center" style="height: auto;">
                        Edit
                    </NuxtLink>
                    <button class="w-16 leading-none my-0 h-auto bg-red-300 border-red-300" @click="removePay()">Remove</button>
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
    }
})

const indexStore = useIndexStore()
const { $dayjs } = useNuxtApp()
const confirmDialogue = ref(null)

const dueDateObject = $dayjs(props.dueDate, { format: 'MM/DD/YYYY' });
const month = ref(dueDateObject.format('MMM'));
const day = ref(dueDateObject.format('DD'));
const delayed = ref(dueDateObject.isBefore($dayjs(), 'day'))
const weekDay = ref(dueDateObject.format('ddd'))
const isLoading = ref(false)

// ----- Define methods ------------
async function markAsPaid(value) {
    // Confirm dialogue
    const confirmed = await confirmDialogue.value.openDialog({ edit: true });

    if (!confirmed) {
        return;
    }

    isLoading.value = true;
    const result = await indexStore.editIsPaid(props.id, value);

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

async function removePay() {
    // Confirm dialogue
    const confirmed = await confirmDialogue.value.openDialog();

    if (!confirmed) {
        return;
    }

    const removed = indexStore.removePayment(props.id);
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