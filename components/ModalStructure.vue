<template>
    <div v-if="isVisible" class="bg-[#00000080] absolute top-0 left-0 w-screen h-screen flex justify-center items-center">
        <div ref="innerContainer" class="flex flex-col justify-between gap-[2.25rem] p-2 pt-6 lg:p-8 w-full absolute bottom-0 max-h-[90vh] min-h-[30vh] bg-[--secondary-bg-color] rounded-t-[0.9rem] lg:static lg:w-[60vw] lg:rounded-b-[0.9rem]">
            <div>
                <slot name="header"></slot>
            </div>
            <div>
                <slot></slot>
            </div>
            <div class="flex flex-col justify-end gap-[0.517rem] footer">
                <slot name="footer">
                </slot>
            </div>
        </div>
    </div>

</template>

<script setup>

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

    isVisible.value = false;
}

// ----- Define Expose ---------
defineExpose({showModal, closeModal})

</script>

<style scoped>

.footer :deep(button) {
    margin: 0px;
}

</style>