import admin from "firebase-admin";

// Import service account using ES6 modules
import serviceAccount from "./service-credentials.json" assert { type: "json" };

const BUSINESS_ID = "vd9jk97yrTJCKyZoyMDj";

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

const allCollections = ["venta", "pedido", "cliente", "producto", "dailyProductRanking", "dailySellTotals"];

// Update all data snapshots
allCollections.forEach(async (collection) => {
  console.log(`------------------ Updating data snapshot for collection ${collection} ------------------`);

  const snapshot = await db.collection(collection).get();

  snapshot.docs.map(async (doc) => {
    console.log(`Updating ${collection} id ${doc.id}`);
    await db.collection(collection).doc(doc.id).update({
      businessId: BUSINESS_ID
    });
  });
});
