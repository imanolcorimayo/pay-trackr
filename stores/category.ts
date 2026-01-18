import { defineStore } from 'pinia';
import {
  collection, query, where, getDocs,
  addDoc, updateDoc, doc, serverTimestamp,
  Timestamp
} from 'firebase/firestore';
import { useCurrentUser } from 'vuefire';
import type { ExpenseCategory, CategoryState } from '~/interfaces/category';
import { DEFAULT_CATEGORIES } from '~/interfaces/category';

export const useCategoryStore = defineStore('category', {
  state: (): CategoryState => ({
    categories: [],
    isLoading: false,
    isLoaded: false,
    error: null
  }),

  getters: {
    // Returns only active (non-deleted) categories
    getCategories: (state): ExpenseCategory[] => {
      return state.categories.filter(cat => !cat.deletedAt);
    },

    // Find category by ID
    getCategoryById: (state) => {
      return (id: string): ExpenseCategory | undefined => {
        return state.categories.find(cat => cat.id === id && !cat.deletedAt);
      };
    },

    // Get category color by ID (useful for display components)
    getCategoryColor: (state) => {
      return (id: string): string => {
        const category = state.categories.find(cat => cat.id === id);
        return category?.color || '#808080';
      };
    },

    // Get category name by ID
    getCategoryName: (state) => {
      return (id: string): string => {
        const category = state.categories.find(cat => cat.id === id);
        return category?.name || 'Otros';
      };
    }
  },

  actions: {
    // Fetch categories from Firestore, auto-seed if empty
    async fetchCategories() {
      const user = useCurrentUser();
      const db = useFirestore();

      if (!user || !user.value) {
        this.error = 'Usuario no autenticado';
        return false;
      }

      // Don't refetch if already loaded
      if (this.isLoaded) {
        return true;
      }

      this.isLoading = true;

      try {
        const categoriesQuery = query(
          collection(db, 'expenseCategories'),
          where('userId', '==', user.value.uid)
        );

        const snapshot = await getDocs(categoriesQuery);
        const categories: ExpenseCategory[] = [];

        snapshot.forEach(doc => {
          categories.push({
            id: doc.id,
            ...doc.data()
          } as ExpenseCategory);
        });

        // Filter out deleted categories for display
        this.categories = categories;
        this.isLoaded = true;

        // Auto-seed default categories if no categories exist
        if (categories.length === 0) {
          await this.seedDefaultCategories();
        }

        return true;
      } catch (error) {
        console.error('Error fetching categories:', error);
        this.error = 'Error al obtener las categorías';
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Seed default categories for new users
    async seedDefaultCategories() {
      const user = useCurrentUser();
      const db = useFirestore();

      if (!user || !user.value) {
        this.error = 'Usuario no autenticado';
        return false;
      }

      this.isLoading = true;

      try {
        const createdCategories: ExpenseCategory[] = [];

        for (const category of DEFAULT_CATEGORIES) {
          const newCategory = {
            name: category.name,
            color: category.color,
            userId: user.value.uid,
            createdAt: serverTimestamp(),
            deletedAt: null
          };

          const docRef = await addDoc(collection(db, 'expenseCategories'), newCategory);

          createdCategories.push({
            id: docRef.id,
            name: category.name,
            color: category.color,
            userId: user.value.uid,
            createdAt: Timestamp.now(),
            deletedAt: null
          });
        }

        this.categories = createdCategories;
        return true;
      } catch (error) {
        console.error('Error seeding default categories:', error);
        this.error = 'Error al crear las categorías predeterminadas';
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Create a new category
    async createCategory(name: string, color: string) {
      const user = useCurrentUser();
      const db = useFirestore();

      if (!user || !user.value) {
        this.error = 'Usuario no autenticado';
        return false;
      }

      this.isLoading = true;

      try {
        const newCategory = {
          name,
          color,
          userId: user.value.uid,
          createdAt: serverTimestamp(),
          deletedAt: null
        };

        const docRef = await addDoc(collection(db, 'expenseCategories'), newCategory);

        // Add to local state
        const createdCategory: ExpenseCategory = {
          id: docRef.id,
          name,
          color,
          userId: user.value.uid,
          createdAt: Timestamp.now(),
          deletedAt: null
        };

        this.categories = [...this.categories, createdCategory];

        return {
          success: true,
          categoryId: docRef.id
        };
      } catch (error) {
        console.error('Error creating category:', error);
        this.error = 'Error al crear la categoría';
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Update an existing category
    async updateCategory(id: string, updates: { name?: string; color?: string }) {
      const db = useFirestore();
      this.isLoading = true;

      try {
        await updateDoc(doc(db, 'expenseCategories', id), updates);

        // Update local state
        const index = this.categories.findIndex(cat => cat.id === id);
        if (index !== -1) {
          this.categories[index] = {
            ...this.categories[index],
            ...updates
          };
          // Trigger reactivity
          this.categories = [...this.categories];
        }

        return true;
      } catch (error) {
        console.error('Error updating category:', error);
        this.error = 'Error al actualizar la categoría';
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Soft delete a category
    async deleteCategory(id: string) {
      const db = useFirestore();
      this.isLoading = true;

      try {
        await updateDoc(doc(db, 'expenseCategories', id), {
          deletedAt: serverTimestamp()
        });

        // Update local state with soft delete
        const index = this.categories.findIndex(cat => cat.id === id);
        if (index !== -1) {
          this.categories[index] = {
            ...this.categories[index],
            deletedAt: Timestamp.now()
          };
          // Trigger reactivity
          this.categories = [...this.categories];
        }

        return true;
      } catch (error) {
        console.error('Error deleting category:', error);
        this.error = 'Error al eliminar la categoría';
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Force refetch categories (clears cache)
    async refetchCategories() {
      this.isLoaded = false;
      return this.fetchCategories();
    },

    // Clear store state
    clearState() {
      this.categories = [];
      this.isLoading = false;
      this.isLoaded = false;
      this.error = null;
    }
  }
});
