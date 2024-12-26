import admin from "firebase-admin";

// Import service account using ES6 modules
import serviceAccount from "./service-credentials.json" assert { type: "json" };

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

// List of UIDs to update
const uids = [
  "pBxssicrskW54fF0ZRSGeMBHFqJ2", // Meli's UID
  "UATNlxhj8oYPSZ3HIsSp5E25uu63", // Samby's UID
  "IhSRSOk9SPYJbj8S0dsdHXB6Mro1" // Ima's UID
];

uids.forEach((uid) => {
  // Assign a custom claim (e.g., role) to a user
  admin
    .auth()
    .setCustomUserClaims(uid, { role: "admin" })
    .then(() => {
      // The new custom claims will propagate to the user's ID token in about an hour.
      // To force a token refresh, prompt the user to sign in again.
      console.log("Custom claims added to the user");
    })
    .catch((error) => {
      console.log("Error setting custom claims:", error);
    });
});
