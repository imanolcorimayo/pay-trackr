// Firebase Messaging Service Worker
// Handles push notifications when the app is in background or closed

console.log('[firebase-messaging-sw.js] Service Worker loaded');

importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js');

console.log('[firebase-messaging-sw.js] Firebase scripts imported');

// Initialize Firebase (same config as main app)
firebase.initializeApp({
  apiKey: "AIzaSyAIZP3m7cCwu8gv9LiXjJA-00s8m40Kp4s",
  authDomain: "pay-tracker-7a5a6.firebaseapp.com",
  projectId: "pay-tracker-7a5a6",
  storageBucket: "pay-tracker-7a5a6.appspot.com",
  messagingSenderId: "16390920244",
  appId: "1:16390920244:web:adc5a4919d9dd457705261"
});

const messaging = firebase.messaging();

// Log when service worker is activated
self.addEventListener('activate', (event) => {
  console.log('[firebase-messaging-sw.js] Service Worker activated');
});

// Log raw push events (before Firebase processes them)
self.addEventListener('push', (event) => {
  console.log('[firebase-messaging-sw.js] Raw push event received:', event);
  console.log('[firebase-messaging-sw.js] Push data:', event.data?.text());
});

// Handle background messages (just log - FCM auto-displays notification payload)
messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Received background message:', payload);
  // FCM automatically displays messages with 'notification' field
  // Only use this handler for data-only messages that need custom display
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
  console.log('[firebase-messaging-sw.js] Notification clicked:', event);

  event.notification.close();

  if (event.action === 'dismiss') {
    return;
  }

  // Get the URL to open (from notification data or default)
  const urlToOpen = event.notification.data?.url || '/recurrent';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // If app is already open, focus it
      for (const client of clientList) {
        if (client.url.includes(self.location.origin) && 'focus' in client) {
          client.navigate(urlToOpen);
          return client.focus();
        }
      }
      // Otherwise, open a new window
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});
