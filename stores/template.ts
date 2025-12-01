import { defineStore } from 'pinia';
import {
  collection, query, where, getDocs, getDoc, doc,
  addDoc, updateDoc, deleteDoc, orderBy, serverTimestamp,
  Timestamp, increment
} from 'firebase/firestore';
import { useCurrentUser } from 'vuefire';

interface PaymentTemplate {
  id: string;
  name: string;
  category: string;
  description?: string;
  userId: string;
  createdAt: any;
  usageCount: number;
}

interface TemplateState {
  templates: PaymentTemplate[];
  isLoading: boolean;
  error: string | null;
}

export const useTemplateStore = defineStore('template', {
  state: (): TemplateState => ({
    templates: [],
    isLoading: false,
    error: null
  }),

  getters: {
    getTemplates: (state) => state.templates,
    // Sort by usage count (most used first)
    getTemplatesSorted: (state) => {
      return [...state.templates].sort((a, b) => b.usageCount - a.usageCount);
    }
  },

  actions: {
    // Fetch all templates for current user
    async fetchTemplates() {
      const user = useCurrentUser();
      const db = useFirestore();

      if (!user || !user.value) {
        this.error = 'User not authenticated';
        return false;
      }

      this.isLoading = true;

      try {
        const templatesQuery = query(
          collection(db, 'paymentTemplates'),
          where('userId', '==', user.value.uid),
          orderBy('usageCount', 'desc')
        );

        const snapshot = await getDocs(templatesQuery);

        const templates: PaymentTemplate[] = [];
        snapshot.forEach(doc => {
          templates.push({
            id: doc.id,
            ...doc.data()
          } as PaymentTemplate);
        });

        this.templates = templates;
        return true;
      } catch (error) {
        console.error('Error fetching templates:', error);
        this.error = 'Failed to fetch templates';
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Create new template
    async createTemplate(templateData: Omit<PaymentTemplate, 'id' | 'createdAt' | 'usageCount'>) {
      const db = useFirestore();
      const user = useCurrentUser();

      if (!user || !user.value) {
        this.error = 'User not authenticated';
        return false;
      }

      this.isLoading = true;

      try {
        // Remove undefined fields
        const cleanData: any = {
          name: templateData.name,
          category: templateData.category,
          userId: user.value.uid,
          createdAt: serverTimestamp(),
          usageCount: 0
        };

        // Only add description if it has a value
        if (templateData.description) {
          cleanData.description = templateData.description;
        }

        const docRef = await addDoc(collection(db, 'paymentTemplates'), cleanData);

        // Add to local state
        const createdTemplate = {
          id: docRef.id,
          ...templateData,
          userId: user.value.uid,
          createdAt: Timestamp.now(),
          usageCount: 0
        } as PaymentTemplate;

        this.templates = [...this.templates, createdTemplate];

        return {
          success: true,
          templateId: docRef.id
        };
      } catch (error) {
        console.error('Error creating template:', error);
        this.error = 'Failed to create template';
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Delete template
    async deleteTemplate(templateId: string) {
      const db = useFirestore();
      this.isLoading = true;

      try {
        await deleteDoc(doc(db, 'paymentTemplates', templateId));

        // Update local state
        this.templates = this.templates.filter(t => t.id !== templateId);

        return true;
      } catch (error) {
        console.error('Error deleting template:', error);
        this.error = 'Failed to delete template';
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    // Increment usage count when template is used
    async incrementUsage(templateId: string) {
      const db = useFirestore();

      try {
        await updateDoc(doc(db, 'paymentTemplates', templateId), {
          usageCount: increment(1)
        });

        // Update local state
        const index = this.templates.findIndex(t => t.id === templateId);
        if (index !== -1) {
          this.templates[index].usageCount++;
        }

        return true;
      } catch (error) {
        console.error('Error incrementing usage:', error);
        return false;
      }
    },

    // Clear store state
    clearState() {
      this.templates = [];
      this.error = null;
    }
  }
});
