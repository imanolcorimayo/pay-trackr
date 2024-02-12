<template>
    <div v-if="!isLoading" class="border-2 border-solid border-white p-0 border-opacity-35 rounded-md mb-4 overflow-hidden">
        <div v-if="isPaid && !edit" class="px-2 py-1 text-xs font-bold text-white shadow-md border-white" style="background-color: #4caf50;">
            Paid
        </div>
        <div v-else-if="delayed && !edit" class="px-2 py-1 text-xs font-bold text-white shadow-md border-white" style="background-color: #af4c4c;">
            Delayed
        </div>
        <div class="flex items-center justify-between max-h-40 overflow-hidden p-2 md:p-5 ">
            <div class="w-1/3 me-4 flex flex-col justify-center items-center">
                <span class="text-center">{{ month }} <strong class="text-2xl">{{ day }}</strong></span>
                <svg style="max-width: 4rem;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <rect x="3" y="6" width="18" height="13" rx="2" stroke="#ffffff" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"></rect>
                        <path d="M3 10H20.5" stroke="#ffffff" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round"></path>
                        <path d="M7 15H9" stroke="#ffffff" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round"></path>
                    </g>
                </svg>
                <span class="text-center"><strong>{{ formatPrice(amount) }}</strong></span>
            </div>
    
            <div class="w-full self-start mt-3">
                <span class="font-bold text-lg">{{ title }}</span>
                <p class="mt-2 text-sm max-h-16 overflow-y-auto scrollbar-hide">{{ description }}</p>
            </div>
            <div v-if="!edit">
                <button v-if="!isPaid" class="w-16 m-2" @click="markAsPaid(true)">Mark Paid</button>
                <button v-else class="w-16 m-2 hover:bg-red-300" @click="markAsPaid(false)">Unpaid</button>
            </div>
            <div v-else>
                <NuxtLink :to="`/edit/${id}`" class="w-16 m-2 as-button block text-center">Edit</NuxtLink>
                <button class="w-16 m-2 bg-red-300" @click="removePay()">Remove</button>
            </div>
        </div>
    </div>
    <div v-else class="border-2 border-solid border-white p-0 border-opacity-35 rounded-md mb-4 overflow-hidden min-h-36 flex justify-center items-center">
        <Loader/>
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
const isLoading = ref(false)

// ----- Define methods ------------
async function markAsPaid(value) {
    // Confirm dialogue
    const confirmed = await confirmDialogue.value.openDialog({edit: true});

    if(!confirmed) {
        return;
    } 

    isLoading.value = true;
    const result = await indexStore.editIsPaid(props.id, value);

    const toastMessage = {
        type: "success",
        message: "Marked as paid successfully"
    };
    if(!result) {
        toastMessage.type = "error";
        toastMessage.message = "Something went wrong, please try again or contact the support team.";
    }
    useToast(toastMessage.type, toastMessage.message);
    isLoading.value = false;

}

async function removePay() {
    // Confirm dialogue
    const confirmed = await confirmDialogue.value.openDialog();

    if(!confirmed) {
        return;
    } 

    const removed = indexStore.removePayment(props.id);
    const toastMessage = {
        type: "success",
        message: "Payment removed successfully"
    };
    if(!removed) {
        toastMessage.type = "error";
        toastMessage.message = "Something went wrong, please try again or contact the support team.";
    }
    useToast(toastMessage.type, toastMessage.message);
}
</script>