import admin from "firebase-admin";

// Import service account using ES6 modules
import serviceAccount from "./service-development-credentials.json" assert { type: "json" };

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

// List of UIDs to update
const uids = [
  // "1emqFZIMOLXeT1rJZiP98ipwySW2", // Meli's UID
  // "OL4jiIpFQdQHYkf5FjuOfMY0wbn2" // Samby's UID
  // "fhDSheiZmnbMm2kyiWxRkMOku0q1" // Ima's UID
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
