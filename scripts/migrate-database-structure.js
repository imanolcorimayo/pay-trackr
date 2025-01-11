import admin from "firebase-admin";
import dayjs from "dayjs";
import isBetween from "dayjs/plugin/isBetween.js";
dayjs.extend(isBetween);

// Import service account using ES6 modules
import serviceAccount from "./service-credentials.json" assert { type: "json" };
import { Timestamp } from "firebase-admin/firestore";

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

// Get all payments
let allPayment = [];
try {
  const payment = await db.collection("payment").get();

  allPayment = payment.docs.map((pay) => {
    return { ...pay.data(), id: pay.id };
  });
} catch (error) {
  console.error("Error getting documents: ", error);
}

// Do for each on order and fix date
const recurrentObjects = [];
allPayment.forEach(async (payment, index) => {
  console.log(`Processing payment ${index + 1} of ${allPayment.length}`);
  // Show payment details
  console.log(payment);

  // Only work with a valid userId
  const validUserId = "ccmFgIVugBYdPlgabAIwiHZztTA2";
  if (payment.user_id === validUserId) {
    // Create recurrent object that will contain almost the same data
    const recurrent = {
      title: payment.title,
      description: payment.description,
      amount: payment.amount,
      startDate: payment.dueDate,
      dueDateDay: payment.dueDate.split("/")[1],
      endDate: null,
      timePeriod: payment.timePeriod,
      category: payment.category,
      isCreditCard: false,
      creditCardId: null,
      userId: payment.user_id,
      createdAt: payment.createdAt
    };

    // Add this object to the recurrent collection
    try {
      const newDoc = await db.collection("recurrent").add(recurrent);
      console.log(`Document written with ID: ${newDoc.id}`);

      // recurrentObjects.push({ ...recurrent, id: newDoc.id });
    } catch (error) {
      console.error("Error adding document: ", error);
    }
  }

});


// Get all trackers
let allTrackers = [];
try {
  const tracker = await db.collection("tracker").get();

  allTrackers = tracker.docs.map((trackr) => {
    return { ...trackr.data(), id: trackr.id };
  });
} catch (error) {
  console.error("Error getting documents: ", error);
}

// Create payments based on each trackr. We won't store one trackr for each payment now,
// but we will store one payment each time
allTrackers.forEach(async (tracker, index) => {

  console.log(`Processing tracker ${index + 1} of ${allTrackers.length}`);

  tracker.payments.forEach(async (trackerPay, index) => {

    // Find recurrent id based only on the title
    const recurrent = recurrentObjects.find((rec) => rec.title === trackerPay.title);
    const recurrentId = recurrent ? recurrent.id : null;

    // Create payment object that will contain almost the same data
    const payment = {
      title: trackerPay.title,
      description: trackerPay.description,
      amount: trackerPay.amount,
      category: trackerPay.category,
      isPaid: trackerPay.isPaid,
      paidDate:  Timestamp.fromDate(dayjs(trackerPay.dueDate, { format: 'MM/DD/YYYY' }).toDate()),
      recurrentId: recurrentId,
      paymentType: !trackerPay.timePeriod || trackerPay.timePeriod == "monthly" ? "recurrent" : "one-time",
      userId: tracker.user_id,
      createdAt: Timestamp.fromDate(dayjs(trackerPay.dueDate, { format: 'MM/DD/YYYY' }).toDate()),
    };

    console.log(payment);

    // Add this object to the payment collection
    try {
      const newDoc = await db.collection("payment2").add(payment);
      console.log(`Document written with ID: ${newDoc.id}`);
    } catch (error) {
      console.error("Error adding document: ", error);
    }
  });
});