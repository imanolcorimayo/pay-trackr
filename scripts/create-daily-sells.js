import admin from "firebase-admin";
import fs from "fs";
import dayjs from "dayjs";

// Import service account using ES6 modules
import serviceAccount from "./service-credentials.json" assert { type: "json" };
import sellsJsonData from "./sells.json" assert { type: "json" };
import { create } from "domain";

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

const sells = await getDailySells();

// Create a new collection with the total of sells per day
const dayOfSellsTable = createBestDayOfSellsRanking(sells);

dayOfSellsTable.forEach(async (day, index) => {
  // Keep unique values
  const uniqueProducts = [...new Set(day.products)];

  const objectToSave = {
    date: admin.firestore.Timestamp.fromDate(day.date.toDate()),
    totalEarnings: day.totalEarnings,
    totalSelling: day.totalSelling,
    totalBuying: day.totalBuying,
    totalQuantity: day.totalQuantity,
    percentageEarnings: day.earningP,
    numProducts: uniqueProducts.length,
    bestProduct: day.bestProduct,
    createdAt: admin.firestore.FieldValue.serverTimestamp()
  };

  // Save daily sell in the database
  console.log("Daily sell added to the database");
  await addDailySell(objectToSave);
});

// Create weekly product cost and earnings
const weeklyProductCostAndEarnings = createWeeklyProductCostAndEarnings(sells);

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

// Add a new document value
async function addDailySell(totals) {
  try {
    // Create a ref to the new collection "dailySells"
    // Handle recurrent payments
    const newProduct = await db.collection("dailySellTotals").add(totals);

    console.log("newProduct.id: ", newProduct.id);
  } catch (error) {
    console.error("Error adding document: ", error);
  }
}

function createBestDayOfSellsRanking(sells) {
  // Clean day of sells table
  const dayOfSellsTable = [];
  // Filter sells in week
  sells.forEach((sell) => {
    const sellDate = dayjs(sell.date, { format: "YYYY-MM-DD" });

    // Check if day exist in the dayOfSellsTable variable
    const dayIndex = dayOfSellsTable.findIndex((day) => day.date.isSame(sellDate, "day"));

    // Get total selling price and buying price
    const totalSellingPrice = sell.sellingPrice * sell.quantity;
    const totalBuyingPrice = sell.buyingPrice * sell.quantity;

    // Earnings per selling
    const earningsPerSelling = totalSellingPrice - totalBuyingPrice;

    // If day does not exist, add it
    if (dayIndex == -1) {
      dayOfSellsTable.push({
        date: sellDate,
        dateFormatted: sellDate.format("DD/MM/YYYY"),
        totalEarnings: earningsPerSelling,
        totalSelling: totalSellingPrice,
        totalBuying: totalBuyingPrice,
        totalQuantity: parseFloat(sell.quantity),
        products: [sell.product.name],
        bestProduct: { earnings: earningsPerSelling, name: sell.product.name, quantity: parseFloat(sell.quantity) },
        earningP: (earningsPerSelling * 100) / totalBuyingPrice
      });
    } else {
      dayOfSellsTable[dayIndex].totalEarnings += earningsPerSelling;
      dayOfSellsTable[dayIndex].totalSelling += totalSellingPrice;
      dayOfSellsTable[dayIndex].totalBuying += totalBuyingPrice;
      dayOfSellsTable[dayIndex].totalQuantity += parseFloat(sell.quantity);
      dayOfSellsTable[dayIndex].earningP =
        (dayOfSellsTable[dayIndex].totalEarnings * 100) / dayOfSellsTable[dayIndex].totalBuying;
      dayOfSellsTable[dayIndex].products.push(sell.product.name);

      // Check if the current product is the best product of the day
      if (earningsPerSelling > dayOfSellsTable[dayIndex].bestProduct.earnings) {
        dayOfSellsTable[dayIndex].bestProduct = {
          earnings: earningsPerSelling,
          name: sell.product.name,
          quantity: parseFloat(sell.quantity)
        };
      }
    }
  });

  // Sort products table
  dayOfSellsTable.sort((a, b) => b.totalEarnings - a.totalEarnings);

  return dayOfSellsTable;
}

function createWeeklyProductCostAndEarnings(sells) {}
