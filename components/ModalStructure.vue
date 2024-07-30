<template>
    <Teleport to="body">
        <Transition>
            <div v-if="isVisible" class="bg-[#00000080] fixed z-50 bottom-0 left-0 w-full h-full flex justify-center items-center max-h-screen overflow-hidden">
                <div ref="innerContainer" class="flex flex-col justify-between gap-[1.25rem] p-2 pt-6 lg:p-8 w-full bottom-0 max-h-[90vh] min-h-[30vh] bg-[--base-color] rounded-t-[0.9rem] lg:w-[60vw] lg:rounded-b-[0.9rem] absolute lg:relative">
                    <IconoirCancel @click="closeModal" class="cursor-pointer text-[1.143rem] absolute top-0 right-0 m-4"/>
                    <div class="flex flex-col justify-center items-center gap-[2.142rem] mb-[-0.5rem]">
                        <slot name="header"></slot>
                    </div>
                    <div class="overflow-auto pt-2 scrollbar-none no-scrollbar">
                        <slot></slot>
                    </div>
                    <div class="flex flex-col justify-end gap-[0.571rem] footer mb-6 lg:mb-[unset] w-full lg:max-w-[15rem] m-auto">
                        <slot name="footer">
                        </slot>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
<script setup>
import IconoirCancel from '~icons/iconoir/cancel';
const emit = defineEmits(["onClose"]);

// ---- Define Vars ---------
const isVisible = ref(false) 
// Refs
const innerContainer = ref(null) 

// If click outside innerContainer, we close the modal
onClickOutside(innerContainer, (ev) => {
    // Get elements classes and check if any class contains "vc-" (vc- is the class of the date picker)
    if(Array.from(ev.target.classList).some(cl => cl.includes("vc-"))) {
        return;
    }
    closeModal()
})

// ----- Define Methods ---------
function showModal() {
    // Add to the body a specific class to avoid being able to scroll
    // Only in client side in case we move to server side some day
    if(process.client) {
        document.body.classList.add("modal-opened");
    }

    isVisible.value = true;
}
function closeModal() {
    // Remove class previously added in show modal
    if(process.client) {
        document.body.classList.remove("modal-opened");
    }

    // Hide modal
    isVisible.value = false;

    // Emit onClose event
    emit("onClose");
}

// ----- Define Expose ---------
defineExpose({showModal, closeModal})

</script>

<style scoped>

.footer :deep(button), .footer :deep(input) {
    margin: 0px;
    width: 100%;
}

</style>