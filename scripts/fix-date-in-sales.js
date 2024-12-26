import admin from "firebase-admin";
import dayjs from "dayjs";
import isBetween from "dayjs/plugin/isBetween.js";
dayjs.extend(isBetween);

// Import service account using ES6 modules
import serviceAccount from "./service-credentials.json" assert { type: "json" };

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

// Get all sales
let allSells = [];
try {
  // Get all sells
  const sells = []; // await db.collection("venta").get();

  allSells = []; /* sells.docs.map((sell) => {
    return { ...sell.data(), id: sell.id };
  }); */
} catch (error) {
  console.error("Error getting documents: ", error);
}

// Do for each on sell and fix date
allSells.forEach(async (sell, index) => {
  // console: Updating sale id ${sell.id}
  console.log(`Updating sale id ${sell.id}`);
  // Console de date
  console.log("Date: ", sell.date);

  const objectToUpdate = {
    date: admin.firestore.Timestamp.fromDate(dayjs(sell.date).toDate())
  };

  console.log("objectToUpdate: ", objectToUpdate);

  // Console the new date in a legible way (toDate)
  console.log("New date: ", objectToUpdate.date.toDate());

  // Update doc
  // await db.collection("venta").doc(sell.id).update(objectToUpdate);
});
