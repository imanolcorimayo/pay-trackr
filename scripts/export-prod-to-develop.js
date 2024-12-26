import admin from "firebase-admin";

// Import service account using ES6 modules
import prodServiceAccount from "./service-credentials.json" assert { type: "json" };

// Import development service account using ES6 modules
import devServiceAccount from "./service-development-credentials.json" assert { type: "json" };

admin.initializeApp(
  {
    credential: admin.credential.cert(prodServiceAccount)
  },
  "prod"
);

admin.initializeApp(
  {
    credential: admin.credential.cert(devServiceAccount)
  },
  "dev"
);

// Get apps
const prodApp = admin.app("prod");
const devApp = admin.app("dev");

const collectionList = [
  // "venta",
  // "cliente",
  // "dailyProductRanking",
  // "dailySellTotals",
  // "pedido",
  // "producto",
  // "weeklyProductPriceComparison"
];

for (const collection of collectionList) {
  const prodCollection = prodApp.firestore().collection(collection);
  const devCollection = devApp.firestore().collection(collection);

  prodCollection.get().then((snapshot) => {
    snapshot.forEach((doc) => {
      // Console insert to see the data
      console.log("Inserting the document id: ", doc.id, " in the collection: ", collection);

      devCollection.doc(doc.id).set(doc.data());
    });
  });
}
