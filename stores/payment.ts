import { defineStore } from 'pinia';
import { 
  collection, query, where, getDocs, getDoc, doc, 
  addDoc, updateDoc, deleteDoc, orderBy, serverTimestamp, 
  Timestamp, limit, startAfter, endBefore 
} from 'firebase/firestore';
import { useCurrentUser } from 'vuefire';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';

dayjs.extend(customParseFormat);

interface Payment {
  id: string;
  title: string;
  description: string;
  amount: number;
  category: string;
  isPaid: boolean;
  paidDate: any;
  recurrentId: string | null;
  paymentType: 'recurrent' | 'one-time';
  userId: string;
  createdAt: any;
  dueDate: any; // Make this a required field
}

interface PaymentFilters {
  startDate?: Date;
  endDate?: Date;
  category?: string;
  isPaid?: boolean;
  searchTerm?: string;
  paymentType?: 'recurrent' | 'one-time' | 'all';
}

interface PaymentState {
  payments: Payment[];
  totalPayments: number;
  isLoading: boolean;
  error: string | null;
  currentPayment: Payment | null;
}

export const usePaymentStore = defineStore('payment', {
  state: (): PaymentState => ({
    payments: [],
    totalPayments: 0,
    isLoading: false,
    error: null,
    currentPayment: null
  }),
  
  getters: {
    getPayments: (state) => state.payments,
    getTotalPayments: (state) => state.totalPayments,
    getCurrentPayment: (state) => state.currentPayment,
    getMonthlyTotals: (state) => {
      const totals = {
        paid: 0,
        unpaid: 0
      };
      
      // Calculate only for current month
      const currentMonth = dayjs().format('MM');
      const currentYear = dayjs().format('YYYY');
      
      state.payments.forEach(payment => {
        const paymentDate = payment.createdAt 
          ? dayjs(payment.createdAt.toDate()) 
          : null;
          
        if (paymentDate && 
            paymentDate.format('MM') === currentMonth && 
            paymentDate.format('YYYY') === currentYear) {
          if (payment.isPaid) {
            totals.paid += payment.amount;
          } else {
            totals.unpaid += payment.amount;
          }
        }
      });
      
      return totals;
    }
  },
  
  actions: {
    // Fetch payments with pagination and filtering
    async fetchPayments(filters: PaymentFilters = {}) {
      const user = useCurrentUser();
      const db = useFirestore();
      
      if (!user || !user.value) {
        this.error = 'User not authenticated';
        return false;
      }
      
      this.isLoading = true;
      
      try {
        // Build query constraints
        const constraints: any[] = [
          where('userId', '==', user.value.uid),
          orderBy('createdAt', 'desc')
        ];
        
        // Add payment type filter
        if (filters.paymentType && filters.paymentType !== 'all') {
          constraints.push(where('paymentType', '==', filters.paymentType));
        }
        
        // Add date range filter
        if (filters.startDate) {
          constraints.push(where('createdAt', '>=', Timestamp.fromDate(filters.startDate)));
        }
        
        if (filters.endDate) {
          constraints.push(where('createdAt', '<=', Timestamp.fromDate(filters.endDate)));
        }
        
        // Add paid/unpaid filter
        if (filters.isPaid !== undefined) {
          constraints.push(where('isPaid', '==', filters.isPaid));
        }
        
        // Add category filter
        if (filters.category) {
          constraints.push(where('category', '==', filters.category));
        }
        
        // Execute query
        const paymentsQuery = query(collection(db, 'payment2'), ...constraints);
        const snapshot = await getDocs(paymentsQuery);
        
        // Process results
        const payments: Payment[] = [];
        snapshot.forEach(doc => {
          payments.push({
            id: doc.id,
            ...doc.data()
          } as Payment);
        });
        
        this.payments = payments;
        
        return true;
      } catch (error) {
        console.error('Error fetching payments:', error);
        this.error = 'Failed to fetch payments';
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    
    // Get payment by ID
    async getPaymentById(paymentId: string) {
      const db = useFirestore();
      this.isLoading = true;
      
      try {
        const paymentDoc = await getDoc(doc(db, 'payment2', paymentId));
        
        if (paymentDoc.exists()) {
          this.currentPayment = {
            id: paymentDoc.id,
            ...paymentDoc.data()
          } as Payment;
          return this.currentPayment;
        } else {
          this.error = 'Payment not found';
          return null;
        }
      } catch (error) {
        console.error('Error fetching payment:', error);
        this.error = 'Failed to fetch payment details';
        return null;
      } finally {
        this.isLoading = false;
      }
    },
    
    // Create a new payment
    async createPayment(paymentData: Omit<Payment, 'id'>) {
      const db = useFirestore();
      const user = useCurrentUser();
      
      if (!user || !user.value) {
        this.error = 'User not authenticated';
        return false;
      }
      
      this.isLoading = true;
      
      try {
        // Ensure payment has required fields
        const newPayment = {
          ...paymentData,
          userId: user.value.uid,
          createdAt: serverTimestamp()
        };
        
        const docRef = await addDoc(collection(db, 'payment2'), newPayment);
        
        // Add to local state
        const createdPayment = {
          id: docRef.id,
          ...newPayment
        } as Payment;
        
        this.payments.unshift(createdPayment);
        this.totalPayments++;
        
        return {
          success: true,
          paymentId: docRef.id
        };
      } catch (error) {
        console.error('Error creating payment:', error);
        this.error = 'Failed to create payment';
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    
    // Update an existing payment
    async updatePayment(paymentId: string, updates: Partial<Payment>) {
      const db = useFirestore();
      this.isLoading = true;
      
      try {
        await updateDoc(doc(db, 'payment2', paymentId), updates);
        
        // Update local state
        const index = this.payments.findIndex(p => p.id === paymentId);
        if (index !== -1) {
          this.payments[index] = {
            ...this.payments[index],
            ...updates
          };
        }
        
        // Update current payment if it's the same one
        if (this.currentPayment && this.currentPayment.id === paymentId) {
          this.currentPayment = {
            ...this.currentPayment,
            ...updates
          };
        }
        
        return true;
      } catch (error) {
        console.error('Error updating payment:', error);
        this.error = 'Failed to update payment';
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    
    // Delete a payment
    async deletePayment(paymentId: string) {
      const db = useFirestore();
      this.isLoading = true;
      
      try {
        await deleteDoc(doc(db, 'payment2', paymentId));
        
        // Update local state
        this.payments = this.payments.filter(p => p.id !== paymentId);
        this.totalPayments--;
        
        // Clear current payment if it's the same one
        if (this.currentPayment && this.currentPayment.id === paymentId) {
          this.currentPayment = null;
        }
        
        return true;
      } catch (error) {
        console.error('Error deleting payment:', error);
        this.error = 'Failed to delete payment';
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    
    // Toggle payment status (paid/unpaid)
    async togglePaymentStatus(paymentId: string, isPaid: boolean) {
      const db = useFirestore();
      this.isLoading = true;
      
      try {
        await updateDoc(doc(db, 'payment2', paymentId), {
          isPaid: isPaid,
          paidDate: isPaid ? serverTimestamp() : null
        });
        
        // Update local state
        const index = this.payments.findIndex(p => p.id === paymentId);
        if (index !== -1) {
          this.payments[index].isPaid = isPaid;
          this.payments[index].paidDate = isPaid ? new Date() : null;
        }
        
        // Update current payment if it's the same one
        if (this.currentPayment && this.currentPayment.id === paymentId) {
          this.currentPayment.isPaid = isPaid;
          this.currentPayment.paidDate = isPaid ? new Date() : null;
        }
        
        return true;
      } catch (error) {
        console.error('Error toggling payment status:', error);
        this.error = 'Failed to update payment status';
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    
    // Clear store state
    clearState() {
      this.payments = [];
      this.totalPayments = 0;
      this.error = null;
      this.currentPayment = null;
    }
  }
});