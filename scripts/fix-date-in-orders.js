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
let allOrders = [];
try {
  // Get all orders
  const orders = await db.collection("pedido").get();

  allOrders = orders.docs.map((order) => {
    return { ...order.data(), id: order.id };
  });
} catch (error) {
  console.error("Error getting documents: ", error);
}

// Do for each on order and fix date
allOrders.forEach(async (order, index) => {
  // console: Updating sale id ${order.id}
  console.log(`Updating sale id ${order.id}`);
  // Console de date
  console.log("Date: ", order.shippingDate);

  const objectToUpdate = {
    shippingDate: admin.firestore.Timestamp.fromDate(dayjs(order.shippingDate).toDate())
  };

  console.log("objectToUpdate: ", objectToUpdate);

  // Console the new date in a legible way (toDate)
  console.log("New date: ", objectToUpdate.shippingDate.toDate());

  // Update doc
  await db.collection("pedido").doc(order.id).update(objectToUpdate);
});
