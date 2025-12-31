import { defineStore } from "pinia";
import {
  collection,
  query,
  where,
  getDocs,
  getDoc,
  addDoc,
  serverTimestamp,
  doc,
  updateDoc,
  deleteDoc,
  orderBy,
  Timestamp
} from "firebase/firestore";
import type { User } from "firebase/auth";
import customParseFormat from "dayjs/plugin/customParseFormat";

interface RecurrentPayment {
  id: string;
  title: string;
  description: string;
  amount: number;
  startDate: string;
  dueDateDay: string;
  endDate: string | null;
  timePeriod: string;
  category: string;
  isCreditCard: boolean;
  creditCardId: string | null;
  userId: string;
  createdAt: any;
}

interface PaymentInstance {
  id: string;
  title: string;
  description: string;
  amount: number;
  category: string;
  isPaid: boolean;
  paidDate: any;
  recurrentId: string;
  paymentType: string;
  userId: string;
  createdAt: any;
}

interface MonthPaymentStatus {
  amount: number;
  dueDate: string;
  paymentId: string;
  isPaid: boolean;
}

interface RecurrentWithMonths {
  id: string;
  title: string;
  description: string;
  amount: number;
  dueDateDay: string;
  category: string;
  months: {
    [month: string]: MonthPaymentStatus;
  };
}

interface RecurrentState {
  recurrentPayments: RecurrentPayment[];
  paymentInstances: PaymentInstance[];
  processedRecurrents: RecurrentWithMonths[];
  isLoaded: boolean;
  isLoading: boolean;
  error: string | null;
}

export const useRecurrentStore = defineStore("recurrent", {
  state: (): RecurrentState => ({
    recurrentPayments: [],
    paymentInstances: [],
    processedRecurrents: [],
    isLoaded: false,
    isLoading: false,
    error: null
  }),

  getters: {
    getRecurrentPayments: (state) => state.recurrentPayments,
    getPaymentInstances: (state) => state.paymentInstances,
    getProcessedRecurrents: (state) => state.processedRecurrents,
    isDataLoaded: (state) => state.isLoaded,
    getMonthlyTotals: (state) => {
      const totals: { [month: string]: { paid: number; unpaid: number } } = {};

      state.processedRecurrents.forEach((recurrent) => {
        Object.entries(recurrent.months).forEach(([month, data]) => {
          if (!totals[month]) {
            totals[month] = { paid: 0, unpaid: 0 };
          }

          if (data.isPaid) {
            totals[month].paid += data.amount;
          } else {
            totals[month].unpaid += data.amount;
          }
        });
      });

      return totals;
    }
  },

  actions: {
    async fetchRecurrentPayments() {
      const user = useCurrentUser();
      const db = useFirestore();

      if (!user || !user.value) {
        this.$state.error = "User not authenticated";
        return false;
      }

      this.isLoading = true;

      try {
        // Fetch all recurrent payments
        const recurrentSnapshot = await getDocs(
          query(collection(db, "recurrent"), where("userId", "==", user.value.uid))
        );

        const recurrents: RecurrentPayment[] = [];
        recurrentSnapshot.forEach((doc) => {
          recurrents.push({
            id: doc.id,
            ...doc.data()
          } as RecurrentPayment);
        });

        this.recurrentPayments = recurrents;
        return true;
      } catch (error) {
        console.error("Error fetching recurrent payments:", error);
        this.$state.error = "Failed to fetch recurrent payments";
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    async fetchPaymentInstances(monthsBack = 6) {
      const user = useCurrentUser();
      const db = useFirestore();
      const { $dayjs } = useNuxtApp();

      if (!user || !user.value) {
        this.$state.error = "User not authenticated";
        return false;
      }

      this.isLoading = true;

      try {
        // Calculate date range - from X months ago to current date
        const startDate = $dayjs().subtract(monthsBack, "month").startOf("month").toDate();
        const endDate = $dayjs().endOf("month").toDate();

        console.log("Date range:", $dayjs(startDate).format("YYYY-MM-DD"), "to", $dayjs(endDate).format("YYYY-MM-DD"));

        // Fetch all payment instances within date range
        const paymentsSnapshot = await getDocs(
          query(
            collection(db, "payment2"),
            where("userId", "==", user.value.uid),
            where("paymentType", "==", "recurrent"),
            where("createdAt", ">=", Timestamp.fromDate(startDate)),
            where("createdAt", "<=", Timestamp.fromDate(endDate)), // Added upper bound
            orderBy("createdAt", "desc")
          )
        );

        const payments: PaymentInstance[] = [];
        paymentsSnapshot.forEach((doc) => {
          payments.push({
            id: doc.id,
            ...doc.data()
          } as PaymentInstance);
        });

        this.paymentInstances = payments;
        this.processData(monthsBack);
        this.isLoaded = true;
        return true;
      } catch (error) {
        console.error("Error fetching payment instances:", error);
        this.$state.error = "Failed to fetch payment instances";
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    async createRecurrentPayment(payment: Omit<RecurrentPayment, "id">) {
      const db = useFirestore();
      this.isLoading = true;

      try {
        // Create the new recurrent payment
        const docRef = await addDoc(collection(db, "recurrent"), payment);

        // Add to local state
        const newPayment = {
          id: docRef.id,
          ...payment
        } as RecurrentPayment;

        this.recurrentPayments.push(newPayment);

        // Reprocess data
        this.processData();
        return true;
      } catch (error) {
        console.error("Error creating recurrent payment:", error);
        this.$state.error = "Failed to create recurrent payment";
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    processData(monthsBack = 6) {
      const { $dayjs } = useNuxtApp();
      const processedData: RecurrentWithMonths[] = [];

      // Create months array with year context
      const monthsWithYear = Array.from({ length: monthsBack }, (_, i) => {
        const date = $dayjs().subtract(i, "month");
        return {
          key: date.format("MMM"),
          display: date.format("MMM"),
          year: date.format("YYYY"),
          fullDate: date
        };
      }).reverse();

      // Group by recurrent payment ID
      this.recurrentPayments.forEach((recurrent) => {
        const recurrentWithMonths: RecurrentWithMonths = {
          id: recurrent.id,
          title: recurrent.title,
          description: recurrent.description,
          amount: recurrent.amount,
          dueDateDay: recurrent.dueDateDay,
          category: recurrent.category,
          months: {}
        };

        // Initialize months with empty data
        monthsWithYear.forEach((month) => {
          recurrentWithMonths.months[month.key] = {
            amount: recurrent.amount,
            dueDate: this.generateDueDate(recurrent.dueDateDay, month.fullDate),
            paymentId: "",
            isPaid: false
          };
        });

        // Fill in actual payment data where available
        this.paymentInstances.forEach((payment) => {
          if (payment.recurrentId === recurrent.id) {
            const paymentDate = $dayjs(payment.createdAt.toDate());
            const paymentMonth = paymentDate.format("MMM");

            // Match payment to the correct month and year
            const matchingMonth = monthsWithYear.find(
              (m) => m.key === paymentMonth && m.year === paymentDate.format("YYYY")
            );

            if (matchingMonth) {
              recurrentWithMonths.months[matchingMonth.key] = {
                amount: payment.amount,
                dueDate: paymentDate.format("MM/DD/YYYY"),
                paymentId: payment.id,
                isPaid: payment.isPaid
              };
            }
          }
        });

        processedData.push(recurrentWithMonths);
      });

      this.processedRecurrents = processedData;
    },

    generateDueDate(day: string, date: any) {
      return date.date(parseInt(day)).format("MM/DD/YYYY");
    },

    async togglePaymentStatus(paymentId: string, isPaid: boolean) {
      const db = useFirestore();
      this.isLoading = true;

      try {
        await updateDoc(doc(db, "payment2", paymentId), {
          isPaid: isPaid,
          paidDate: isPaid ? serverTimestamp() : null
        });

        // Update local state
        const paymentIndex = this.paymentInstances.findIndex((p) => p.id === paymentId);
        if (paymentIndex !== -1) {
          this.paymentInstances[paymentIndex].isPaid = isPaid;
        }

        // Reprocess data to reflect changes
        this.processData();
        return true;
      } catch (error) {
        console.error("Error toggling payment status:", error);
        this.$state.error = "Failed to update payment status";
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    async addNewPaymentInstance(recurrentId: string, month: string, isPaid = false) {
      const user = useCurrentUser();
      const db = useFirestore();
      const { $dayjs } = useNuxtApp();
      $dayjs.extend(customParseFormat);

      if (!user || !user.value) {
        this.$state.error = "User not authenticated";
        return false;
      }

      this.isLoading = true;

      try {
        // Find the recurrent payment
        const recurrent = this.recurrentPayments.find((r) => r.id === recurrentId);
        if (!recurrent) {
          this.$state.error = "Recurrent payment not found";
          return false;
        }

        // Create payment date based on the month and day
        const monthIndex = $dayjs(month, "MMM").month();
        const paymentDate = $dayjs()
          .year($dayjs().year())
          .month(monthIndex)
          .date(parseInt(recurrent.dueDateDay))
          .toDate();

        console.log("Payment date:", $dayjs(paymentDate).format("MM/DD/YYYY"));
        console.log("Is paid?", isPaid);
        console.log("Month:", month);
        console.log("Month Index:", monthIndex);
        console.log("Recurrent:", recurrent);

        // Create the new payment instance
        const newPayment = {
          title: recurrent.title,
          description: recurrent.description,
          amount: recurrent.amount,
          category: recurrent.category,
          isPaid: isPaid,
          paidDate: isPaid ? serverTimestamp() : null,
          recurrentId: recurrentId,
          paymentType: "recurrent",
          userId: user.value.uid,
          createdAt: Timestamp.fromDate(paymentDate)
        };

        const docRef = await addDoc(collection(db, "payment2"), newPayment);

        // Add to local state
        this.paymentInstances.push({
          id: docRef.id,
          ...newPayment
        } as PaymentInstance);

        // Reprocess data
        this.processData();
        return true;
      } catch (error) {
        console.error("Error creating payment instance:", error);
        this.$state.error = "Failed to create payment instance";
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    async updateRecurrentPayment(recurrentId: string, updates: Partial<RecurrentPayment>) {
      const db = useFirestore();
      this.isLoading = true;

      try {
        await updateDoc(doc(db, "recurrent", recurrentId), updates);

        // Update local state
        const index = this.recurrentPayments.findIndex((r) => r.id === recurrentId);
        if (index !== -1) {
          this.recurrentPayments[index] = {
            ...this.recurrentPayments[index],
            ...updates
          };
        }

        // Reprocess data
        this.processData();
        return true;
      } catch (error) {
        console.error("Error updating recurrent payment:", error);
        this.$state.error = "Failed to update recurrent payment";
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    async deleteRecurrentPayment(recurrentId: string) {
      const db = useFirestore();
      this.isLoading = true;
      let deletedInstances = 0;

      try {
        // Step 1: Find all payment instances associated with this recurrent payment
        const user = useCurrentUser();
        if (!user || !user.value) {
          this.$state.error = "User not authenticated";
          return false;
        }

        const paymentInstancesQuery = query(
          collection(db, "payment2"),
          where("recurrentId", "==", recurrentId),
          where("userId", "==", user.value.uid)
        );

        const instancesSnapshot = await getDocs(paymentInstancesQuery);

        // Step 2: Delete all payment instances in a batch
        const deletePromises = instancesSnapshot.docs.map(async (doc) => {
          await deleteDoc(doc.ref);
          deletedInstances++;
        });

        // Execute all delete operations
        await Promise.all(deletePromises);

        // Step 3: Delete the recurrent payment itself
        await deleteDoc(doc(db, "recurrent", recurrentId));

        // Step 4: Update local state
        this.paymentInstances = this.paymentInstances.filter((p) => p.recurrentId !== recurrentId);
        this.recurrentPayments = this.recurrentPayments.filter((r) => r.id !== recurrentId);
        this.processedRecurrents = this.processedRecurrents.filter((r) => r.id !== recurrentId);

        console.log(`Deleted recurrent payment and ${deletedInstances} payment instances`);
        return true;
      } catch (error: any) {
        console.error("Error deleting recurrent payment:", error);
        this.$state.error = `Failed to delete payment: ${error.message || "Unknown error"}`;
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Additional utility methods as needed
    sortRecurrents(orderBy: string, direction: "asc" | "desc") {
      this.processedRecurrents.sort((a, b) => {
        let comparison = 0;

        switch (orderBy) {
          case "title":
            comparison = a.title.localeCompare(b.title);
            break;
          case "amount":
            comparison = a.amount - b.amount;
            break;
          case "date":
            comparison = parseInt(a.dueDateDay) - parseInt(b.dueDateDay);
            break;
          default:
            comparison = a.title.localeCompare(b.title);
        }

        return direction === "asc" ? comparison : -comparison;
      });
    },

    searchRecurrents(query: string) {
      if (!query) {
        this.processData();
        return;
      }

      const searchTerm = query.toLowerCase();
      const filteredData = this.processedRecurrents.filter(
        (recurrent) =>
          recurrent.title.toLowerCase().includes(searchTerm) ||
          recurrent.description.toLowerCase().includes(searchTerm) ||
          recurrent.amount.toString().includes(searchTerm) ||
          recurrent.category.toLowerCase().includes(searchTerm)
      );

      this.processedRecurrents = filteredData;
    }
  }
});
