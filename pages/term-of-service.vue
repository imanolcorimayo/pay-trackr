<template>
  <div class="py-6 w-full">
    <div class="container px-4 md:px-6">
      <div class="prose prose-gray dark:prose-invert max-w-none">
        <h1>Terms of Service</h1>
        <p>
          Welcome to WiseUtils (PayTrackr Section) Terms of Service. By accessing or using the Website, you agree to
          comply with and be bound by the following terms and conditions ("Terms of Service").
          Please read these Terms of Service carefully before accessing or using the Website.
        </p>
        <h2>Acceptance of Terms</h2>
        <p>
          By accessing or using the Website, you agree to be bound by these Terms of Service.
          If you do not agree with any part of these terms, you may not access the Website.
        </p>
        <h2>Use of the Website</h2>
        <p>
          The Website allows users to manage their monthly payments by adding, editing, deleting payments, viewing
          payment history,
          and marking payments as paid or unpaid.
        </p>
        <h2>User Responsibilities</h2>
        <p>
          You are responsible for maintaining the confidentiality of your account and password and for restricting
          access to your computer. You agree to accept responsibility for all activities that occur under your account
          or password.
        </p>
        <p>
          You agree not to use the Website for any unlawful purpose or to violate any applicable laws or regulations.
        </p>
        <h2>Data Management</h2>
        <p>
          The Website utilizes Firebase for data management.
          Your data is subject to
          <a style="color: var(--primary-bg-color);" href="https://firebase.google.com/terms/">Firebase's security rules
            and policies</a>.
          We do not manage any money or financial transactions through the Website.
        </p>
        <h2>Limitation of Liability</h2>
        <p>
          The Website is provided on an "as is" and "as available" basis. 
          We do not warrant that the Website will be uninterrupted, timely, secure, or error-free.
        </p>
        <p>
          In no event shall we be liable for any direct, indirect, incidental, special, consequential, or punitive damages arising out of or related to the use of the Website.
        </p>
        <h2>Changes to Terms of Service</h2>
        <p>
          We reserve the right to modify or update these Terms of Service at any time. Any changes will be effective immediately upon posting on the Website. Your continued use of the Website after the posting of revised Terms of Service constitutes your acceptance of such changes.
        </p>
        <h2>Termination</h2>
        <p>
          We reserve the right to terminate or suspend your account and access to the Website at any time without prior notice or liability for any reason whatsoever.
        </p>
        <h2>Contact Information</h2>
        <p>
          If you have any questions about these Terms of Service, please contact us using the 
          <NuxtLink href="/contact-us">Contact Us</NuxtLink> 
          page.
        </p>
        <h2>Effective Date</h2>
        <p>
          These Terms of Service are effective as of 2024, March 29th.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: "landing"
})

useHead({
    title: 'Terms Of Service - PayTrackr',
    meta: [
        {
            name: 'description',
            content: 'Learn the PayTrackr\'s terms of service.'
        }
    ]
})
</script>