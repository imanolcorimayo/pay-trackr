<template>
    <Teleport to="body">
        <Transition>
            <div v-if="isVisible" class="bg-[#00000080] fixed z-50 bottom-0 left-0 w-full h-full flex justify-center items-center max-h-screen overflow-hidden">
                <div ref="innerContainer" class="flex flex-col justify-between gap-[2.25rem] p-2 pt-6 lg:p-8 w-full absolute bottom-0 max-h-[90vh] min-h-[30vh] bg-[--secondary-bg-color] rounded-t-[0.9rem] lg:static lg:w-[60vw] lg:rounded-b-[0.9rem]">
                    <div class="flex justify-between items-start mb-[-0.5rem]">
                        <div>
                            <slot name="header"></slot>
                        </div>
                        <IconoirCancel @click="closeModal" class="cursor-pointer text-[1.143rem]"/>
                    </div>
                    <div class="overflow-auto pt-2">
                        <slot></slot>
                    </div>
                    <div class="flex flex-col justify-end gap-[0.571rem] footer mb-6 lg:mb-[unset] w-full md:max-w-[15rem] m-auto">
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
onClickOutside(innerContainer, closeModal)

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

.v-enter-active,
.v-leave-active {
  transition: opacity 0.2s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}

</style>