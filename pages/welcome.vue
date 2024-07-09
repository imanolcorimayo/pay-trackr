<template>
  <div>
    <div class="container flex justify-center align-center flex-col" style="height:90vh">
      <div class="text-center mb-6">
        <div class="w-full flex justify-center align-center mb-16">
          <img class="no-flex" src="/img/logo.png" alt="Logo Created With my.logomakr.com" width="250" height="195">
        </div>
        <h1 class="mb-4">Welcome To Pay Tracker!</h1>
        <span>Join the community for free and manage your finance 😎</span>
      </div>
      <div class="text-center">
        <button class="w-full max-w-80 btn btn-primary" @click="googleSignIn">
          <img src="/img/google_logo.png" width="25" height="25" class="inline mx-2" alt="Google Logo">
          Google Sign In
        </button>
      </div>
    </div>
    <span class="text-xs sticky block">By signing in you accept the <NuxtLink to="/term-of-service" target="_blank" class="font-semibold">terms of service.</NuxtLink></span>
    <span class="text-xs sticky">Logos Created With LogoMakr.com</span>
  </div>
</template>

<script setup>
import {
  signInWithPopup,
  GoogleAuthProvider
} from 'firebase/auth'
const googleAuthProvider = new GoogleAuthProvider()

definePageMeta({
  layout: false
})

const auth = useFirebaseAuth()
const route = useRoute()
const error = ref(false)
// Use Firebase to login
function googleSignIn() {
  signInWithPopup(auth, googleAuthProvider).
  then((result) => {
    console.log('Success signInRedirect', result)
    navigateTo(route.query && route.query.redirect ? route.query.redirect : '/')
  }).
  catch((reason) => {
    console.error('Failed signInRedirect', reason)
    error.value = reason
  })
}

useHead({
  title: 'Welcome To PayTrackr',
  meta: [
    {
      name: 'description',
      content: 'Web page to keep tracking your main expenses and keep your life organized'
    }
  ],

  link: [
    {
      rel: 'preconnect',
      href: 'https://wiseutils.com/welcome'
    }
  ]
})
</script>