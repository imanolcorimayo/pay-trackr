import admin from "firebase-admin";
import fs from "fs";
import dayjs from "dayjs";
import isBetween from "dayjs/plugin/isBetween.js";
dayjs.extend(isBetween);

// Import service account using ES6 modules
import serviceAccount from "./service-credentials.json" assert { type: "json" };
import sellsJsonData from "./sells.json" assert { type: "json" };
import productsJsonData from "./products.json" assert { type: "json" };
import { start } from "repl";

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

// Base function to add the record to firestore
async function addDayRanking(record) {
  try {
    // Create a ref to the new collection "dailySells"
    // Handle recurrent payments
    const dailyProdRanking = await db.collection("dailyProductRanking").add(record);

    console.log("dailyProdRanking.id: ", dailyProdRanking.id);
  } catch (error) {
    console.error("Error adding document: ", error);
  }
}

// Get all sells to calculate the totals
async function getDailySells() {
  // Check if sells.json file exists and return if contains data
  if (sellsJsonData.length > 0) {
    console.log("Sells data already exists");
    return sellsJsonData;
  }

  try {
    // Get all sells
    const sells = await db.collection("venta").get();

    const allSells = sells.docs.map((sell) => {
      return { ...sell.data(), id: sell.id };
    });

    // Save sells in a JSON file to avoid calling the API every time
    fs.writeFileSync("sells.json", JSON.stringify(allSells));

    return allSells;
  } catch (error) {
    console.error("Error getting documents: ", error);
  }
}

// Create daily product ranking
function createProductsRanking(sells) {
  // Full data
  const fullData = [];

  // Create unique list of days
  const days = [...new Set(sells.map((sell) => sell.date))];

  // Sort days
  days.sort((a, b) => dayjs(a).unix() - dayjs(b).unix());

  // Iterate by each day
  days.forEach((day) => {
    // Filter sells in week
    const sellsOnSpecificDay = sells.filter((s) => s.date == day);

    // Clean products table
    const productsTable = [];
    sellsOnSpecificDay.forEach((s) => {
      // The problem is here: Add to products table
      const productsTableAux = productsTable.map((product) => product.id);
      const productIndex = productsTableAux.indexOf(s.product.id);

      const totalSelling = s.sellingPrice * s.quantity;
      const totalBuying = s.buyingPrice * s.quantity;
      const earningsPerProduct = totalSelling - totalBuying;
      const earningPercentage = (earningsPerProduct * 100) / (s.buyingPrice * s.quantity);

      // If product does not exist, add it
      if (productIndex == -1) {
        productsTable.push({
          productId: s.product.id,
          name: s.product.name,
          totalEarnings: earningsPerProduct,
          totalSelling,
          totalBuying,
          totalQuantity: parseFloat(s.quantity),
          earningPercentage: earningPercentage
        });
      } else {
        productsTable[productIndex].totalEarnings += earningsPerProduct;
        productsTable[productIndex].totalSelling += totalSelling;
        productsTable[productIndex].totalBuying += totalBuying;
        productsTable[productIndex].totalQuantity += parseFloat(s.quantity);
        productsTable[productIndex].earningPercentage =
          (productsTable[productIndex].totalEarnings * 100) / productsTable[productIndex].totalBuying;
      }
    });

    // Sort products table
    productsTable.sort((a, b) => b.totalEarnings - a.totalEarnings);

    const record = productsTable.map((product, index) => {
      return {
        ranking: index + 1,
        ...product
      };
    });

    fullData.push({ day, ranking: record });
  });

  return fullData;
}

// Get all sells
const sells = await getDailySells();

// Create daily product ranking
const rankings = createProductsRanking(sells);

// Add daily product ranking to Firestore
rankings.forEach(async (ranking) => {
  // Create object to be inserted
  const record = {
    date: admin.firestore.Timestamp.fromDate(dayjs(ranking.day).toDate()),
    products: ranking.ranking,
    createdAt: admin.firestore.FieldValue.serverTimestamp()
  };

  console.log("Adding record");
  await addDayRanking(record);
  console.log("Record added");
});
