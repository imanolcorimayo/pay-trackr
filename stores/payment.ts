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
  isLoaded: boolean;
  error: string | null;
  currentPayment: Payment | null;
  lastFilters: PaymentFilters | null;
}

export const usePaymentStore = defineStore('payment', {
  state: (): PaymentState => ({
    payments: [],
    totalPayments: 0,
    isLoading: false,
    isLoaded: false,
    error: null,
    currentPayment: null,
    lastFilters: null
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
    async fetchPayments(filters: PaymentFilters = {}, forceRefresh = false) {
      const user = useCurrentUser();
      const db = useFirestore();

      if (!user || !user.value) {
        this.error = 'Usuario no autenticado';
        return false;
      }

      // Check if we can use cached data (same filters and already loaded)
      const filtersMatch = this.lastFilters &&
        JSON.stringify(this.lastFilters) === JSON.stringify(filters);

      if (!forceRefresh && this.isLoaded && filtersMatch) {
        return true;
      }

      this.isLoading = true;

      try {
        // Build query constraints
        const constraints: any[] = [
          where('userId', '==', user.value.uid),
          orderBy('dueDate', 'desc')
        ];

        // Add payment type filter
        if (filters.paymentType && filters.paymentType !== 'all') {
          constraints.push(where('paymentType', '==', filters.paymentType));
        }

        // Add date range filter
        if (filters.startDate) {
          constraints.push(where('dueDate', '>=', Timestamp.fromDate(filters.startDate)));
        }

        if (filters.endDate) {
          constraints.push(where('dueDate', '<=', Timestamp.fromDate(filters.endDate)));
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
        this.isLoaded = true;
        this.lastFilters = { ...filters };

        return true;
      } catch (error) {
        console.error('Error fetching payments:', error);
        this.error = 'Error al obtener los pagos';
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
          this.error = 'Pago no encontrado';
          return null;
        }
      } catch (error) {
        console.error('Error fetching payment:', error);
        this.error = 'Error al obtener los detalles del pago';
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
        this.error = 'Usuario no autenticado';
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

        // Add to local state with actual timestamp
        const createdPayment = {
          id: docRef.id,
          ...paymentData, // Use original data instead of newPayment (which has serverTimestamp)
          userId: user.value.uid,
          createdAt: Timestamp.now() // Use actual timestamp for local state
        } as Payment;

        // Trigger reactivity by replacing the array
        this.payments = [createdPayment, ...this.payments];
        this.totalPayments++;
        
        return {
          success: true,
          paymentId: docRef.id
        };
      } catch (error) {
        console.error('Error creating payment:', error);
        this.error = 'Error al crear el pago';
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
        this.error = 'Error al actualizar el pago';
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
        this.error = 'Error al eliminar el pago';
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
        this.error = 'Error al actualizar el estado del pago';
        return false;
      } finally {
        this.isLoading = false;
      }
    },
    
    // Clear store state
    clearState() {
      this.payments = [];
      this.totalPayments = 0;
      this.isLoaded = false;
      this.error = null;
      this.currentPayment = null;
      this.lastFilters = null;
    },

    // Force refresh data
    async refetchPayments(filters: PaymentFilters = {}) {
      return this.fetchPayments(filters, true);
    }
  }
});