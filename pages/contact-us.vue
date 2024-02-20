<template>
    <div class="container">
        <form v-if="!sending" @submit.prevent="sendContactUsMessage()" class="mt-2">
            <label>Full Name *</label>
            <input disabled placeholder="Your full name" v-model="contactUs.fullName" required name="title" autocomplete="off" />
            <label>Email *</label>
            <input disabled placeholder="Your email" type="email" v-model="contactUs.email" required name="title" autocomplete="off" />
            <label>Message *</label>
            <textarea required v-model="contactUs.message" name="message" autocomplete="off" class="min-h-20" />
            <input :disabled="disableButton" type="submit" value="Submit">
        </form>
        <div v-else class="flex justify-center m-10 p-10">
            <Loader size="10" />
        </div>
    </div>
</template>

<script setup>
import { collection, addDoc } from 'firebase/firestore';


definePageMeta({
    layout: 'contact-us'
})

const user = await getCurrentUser()
const db = useFirestore();

const contactUs = ref({
    fullName: user.displayName,
    email: user.email,
    message: '',
})
const disableButton = ref(false)
const sending = ref(false)

// ----- Define Methods --------
async function sendContactUsMessage() {
    disableButton.value = true;
    sending.value = true;

    // Simple validate name and full name and the message is not empty
    if(contactUs.value.fullName !== user.displayName) {
        useToast("error", "Full Name is not matching with your account.")
        disableButton.value = false;
        sending.value = false;
        return;
    } else if(contactUs.value.email !== user.email) {
        useToast("error", "Your email is not matching with your account.")
        disableButton.value = false;
        sending.value = false;
        return;
    } else if(contactUs.value.message.length == 0) {
        useToast("error", "Please add some message to the form.")
        disableButton.value = false;
        sending.value = false;
        return;
    }

    try {
        // Send message to firebase                
        // Post tracker object on Firestore
        const newContactUs = await addDoc(collection(db, "contactUs"), contactUs.value);
    
        // Show success message and redirect to home page
        useToast("success", "Thank you! Message sent successfully.", { onClick: "goHome", autoClose: 2000 })
        setTimeout(() => {
            navigateTo("/")
        }, 2000)
    } catch (error) {
        console.error(error)
        useToast("error", "Something went wrong. Please try again later.")
        disableButton.value = false;
        sending.value = false;
    }

}
useHead({
    title: 'Contact Us - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Reach out to provide feedback or any recommendations.'
        }
    ]
})
</script>