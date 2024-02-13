<template>
  <div>
    <div v-if="!loading" class="container flex justify-center align-center flex-col" style="height:90vh">
      <div class="text-center mb-6">
        <div class="block w-full flex justify-center align-center mb-16">
          <img class="no-flex" src="/img/logo.png" alt="Logo Created With my.logomakr.com">
        </div>
        <h1 class="mb-4">Welcome To Pay Tracker!</h1>
        <span>Join the community for free and manage your finance 😎</span>
      </div>
      <div class="text-center">
        <button class="w-full max-w-80" @click="googleSignIn">
          <img src="/img/google_logo.png" width="25" height="25" class="inline mx-2" alt="Google Logo">
          Google Sign In
        </button>
      </div>
    </div>
    <div v-else class="flex justify-center m-10 p-10">
      <Loader size="10" />
    </div>
    <span class="text-xs sticky" v-if="!loading">Logos Created With LogoMakr.com</span>
  </div>
</template>

<script setup>
import {
  getRedirectResult,
  signInWithRedirect,
  GoogleAuthProvider
} from 'firebase/auth'
const googleAuthProvider = new GoogleAuthProvider()

definePageMeta({
  layout: false
})

const auth = useFirebaseAuth()
const route = useRoute()
const error = ref(false)
const loading = ref(true)
// Use Firebase to login
function googleSignIn() {
  signInWithRedirect(auth, googleAuthProvider).catch((reason) => {
    console.error('Failed signInRedirect', reason)
    error.value = reason
  })
}


// Only on client side
onMounted(async () => {
  getRedirectResult(auth).catch((reason) => {
    alert('Something: ', reason)
    console.error('Failed redirect result', reason)
    error.value = reason
  }).then((value) => {

    console.log("value: ", value)
    // If value is not null -> Navigate to the specific page
    value && navigateTo(route.query && route.query.redirect ? route.query.redirect : '/')
    loading.value = false
  })
})


useHead({
    title: 'Welcome To PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Web page to keep tracking your main expenses and keep your life organized'
        }
    ]
})
</script>